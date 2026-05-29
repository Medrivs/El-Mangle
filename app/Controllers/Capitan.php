<?php

namespace App\Controllers;
use App\Models\MesaModel;

class Capitan extends BaseController
{
    protected $db;

    // inyecta dependencias para facilitar mocks en testing
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
    }

    // muestra el panel principal del capitan con mesas activas y totales
    public function index()
    {
        if (!$this->esCapitanAutorizado()) return redirect()->to(base_url('/'));

        // obtiene todas las mesas con ordenamiento matematico estricto
        $mesasRaw = $this->db->table('Mesa m')
            ->select('m.*, u.nombre_completo as mesero')
            ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
            ->where('m.activa', 1)
            ->orderBy('CAST(m.numero_mesa AS UNSIGNED)', 'ASC')
            ->orderBy('m.numero_mesa', 'ASC')
            ->get()->getResultArray();

        $data = ['mesas' => []];

        // procesa los totales de cada mesa extrayendo la logica
        foreach ($mesasRaw as $m) {
            $totales = $this->calcularTotalesMesa($m['id_mesa'], $m['estado_mesa']);
            $m['total'] = $totales['total'];
            $m['items'] = $totales['items'];
            $data['mesas'][] = $m;
        }

        // extrae solo las mesas libres para el modal de transferencia
        $data['mesas_libres'] = $this->db->table('Mesa')->where(['estado_mesa' => 'Libre', 'activa' => 1])->orderBy('numero_mesa', 'ASC')->get()->getResultArray();

        return view('capitan/index', $data);
    }

    // transfiere el consumo de una mesa a otra
    public function transferir()
    {
        $post = $this->request->getPost();
        $id_origen = $post['id_mesa_origen'] ?? null;
        $id_destino = $post['id_mesa_destino'] ?? null;

        $comanda = $this->db->table('Comanda')->where('id_mesa', $id_origen)->orderBy('id_comanda', 'DESC')->get()->getRowArray();
        $mesaOrigen = $this->db->table('Mesa')->where('id_mesa', $id_origen)->get()->getRowArray();
        $mesaDestino = $this->db->table('Mesa')->where('id_mesa', $id_destino)->get()->getRowArray();

        // retorno temprano si faltan datos
        if (!$comanda || !$mesaOrigen || !$mesaDestino) return redirect()->back()->with('error', 'Mesas o comanda no encontradas.');

        $this->db->transStart();

        $this->db->table('Comanda')->where('id_comanda', $comanda['id_comanda'])->update(['id_mesa' => $id_destino]);
        $this->db->table('Mesa')->where('id_mesa', $id_destino)->update(['estado_mesa' => $mesaOrigen['estado_mesa']]);
        $this->db->table('Mesa')->where('id_mesa', $id_origen)->update(['estado_mesa' => 'Libre']);

        $this->registrarAuditoria($id_origen, 'Transferencia', "Traslado de clientes de Mesa {$mesaOrigen['numero_mesa']} a Mesa {$mesaDestino['numero_mesa']}");

        $this->db->transComplete();

        return $this->db->transStatus() ? redirect()->to(base_url('capitan'))->with('success', 'Mesa transferida exitosamente.') : redirect()->back()->with('error', 'Error al transferir.');
    }

    // regresa la mesa a estado ocupada para pedir mas platillos
    public function reabrir($id_mesa)
    {
        $this->db->transStart();

        $this->db->table('Mesa')->where('id_mesa', $id_mesa)->update(['estado_mesa' => 'Ocupada']);
        $this->registrarAuditoria($id_mesa, 'Reapertura', 'Reapertura de cuenta solicitada por el Capitán');

        $this->db->transComplete();

        return redirect()->to(base_url('capitan'))->with('success', 'Cuenta reabierta. El mesero ya puede agregar más platillos.');
    }

    // carga la vista con el detalle de la orden para cancelacion o division
    public function detalle_orden($id_mesa, $modo)
    {
        if (!$this->esCapitanAutorizado()) return redirect()->to(base_url('/'));

        $data = [
            'mesa' => $this->db->table('Mesa m')->select('m.*, u.nombre_completo as mesero')->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')->where('id_mesa', $id_mesa)->get()->getRowArray(),
            'modo' => $modo,
            'detalles' => []
        ];

        $comanda = $this->db->table('Comanda')->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->get()->getRowArray();

        if ($comanda) {
            $data['detalles'] = $this->db->table('Detalle_Comanda dc')
                ->select('dc.*, p.nombre_platillo as platillo')
                ->join('Platillo p', 'p.id_platillo = dc.id_platillo')
                ->where('dc.id_comanda', $comanda['id_comanda'])
                ->get()->getResultArray();
        }

        return view('capitan/detalle_orden', $data);
    }

    // cancela platillos de la comanda con registro obligatorio
    public function cancelar_item()
    {
        $post = $this->request->getPost();
        $id_detalle = $post['id_detalle'] ?? null;
        $cantidad_cancelar = (int)($post['cantidad'] ?? 0);
        $id_mesa = $post['id_mesa'] ?? null;

        $detalle = $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->get()->getRowArray();

        // evita manipulacion en el frontend con cantidad cero o negativa
        if (!$detalle || $cantidad_cancelar <= 0) return redirect()->back()->with('error', 'Datos inválidos para cancelar.');

        $platillo = $this->db->table('Platillo')->where('id_platillo', $detalle['id_platillo'])->get()->getRowArray();

        $this->db->transStart();

        if ($cantidad_cancelar >= $detalle['cantidad']) {
            $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->delete();
        } else {
            $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->update(['cantidad' => $detalle['cantidad'] - $cantidad_cancelar]);
        }

        $this->registrarAuditoria($id_mesa, 'Cancelacion Platillo', "Se eliminaron $cantidad_cancelar x {$platillo['nombre_platillo']}. Motivo del Capitán: {$post['motivo']}");

        $this->db->transComplete();

        return $this->db->transStatus() ? redirect()->to(base_url("capitan/detalle_orden/$id_mesa/cancelar"))->with('success', 'Platillo cancelado correctamente.') : redirect()->back()->with('error', 'Fallo al cancelar platillo.');
    }

    // divide la cuenta creando una mesa virtual nueva con sufijo
    public function ejecutar_division()
    {
        $post = $this->request->getPost();
        
        // seguridad si manda un array vacio
        if (empty($post['items'])) return redirect()->back()->with('error', '⚠️ Debes seleccionar al menos un platillo.');

        $mesaOrigen = $this->db->table('Mesa')->where('id_mesa', $post['id_mesa'])->get()->getRowArray();
        $nuevo_numero = $mesaOrigen['numero_mesa'] . '-' . strtoupper($post['sufijo']);

        $this->db->transStart();

        $this->db->table('Mesa')->insert([
            'numero_mesa' => $nuevo_numero,
            'estado_mesa' => $mesaOrigen['estado_mesa'],
            'activa' => 1,
            'id_usuario_mesero' => $mesaOrigen['id_usuario_mesero']
        ]);
        $id_mesa_nueva = $this->db->insertID();

        $this->db->table('Comanda')->insert([
            'id_mesa' => $id_mesa_nueva,
            'id_usuario' => session()->get('id_usuario'),
            'fecha_hora' => date('Y-m-d H:i:s')
        ]);
        $id_comanda_nueva = $this->db->insertID();

        $this->db->table('Detalle_Comanda')->whereIn('id_detalle_comanda', $post['items'])->update(['id_comanda' => $id_comanda_nueva]);

        $this->registrarAuditoria($post['id_mesa'], 'Division de Cuenta', "El Capitán separó artículos hacia la nueva cuenta $nuevo_numero");

        $this->db->transComplete();

        return $this->db->transStatus() ? redirect()->to(base_url('capitan'))->with('success', "✅ Cuenta dividida exitosamente. Se generó la Mesa $nuevo_numero.") : redirect()->back()->with('error', 'Error al dividir cuenta.');
    }

    // centraliza la verificacion de rol
    private function esCapitanAutorizado(): bool
    {
        return session()->get('isLoggedIn') && session()->get('id_rol') == 2;
    }

    // devuelve el total y numero de items matematicos de una mesa
    private function calcularTotalesMesa($id_mesa, $estado_mesa): array
    {
        if (!in_array($estado_mesa, ['Ocupada', 'Por Pagar'])) return ['total' => 0, 'items' => 0];

        $comanda = $this->db->table('Comanda')->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->get()->getRowArray();
        if (!$comanda) return ['total' => 0, 'items' => 0];

        $totales = $this->db->query("SELECT SUM(cantidad * precio_unitario) as total, SUM(cantidad) as items FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();

        return [
            'total' => $totales['total'] ?? 0,
            'items' => $totales['items'] ?? 0
        ];
    }

    // consolida la insercion de auditoria obligatoria
    private function registrarAuditoria($id_mesa, $tipo_movimiento, $motivo)
    {
        $this->db->table('Movimientos')->insert([
            'id_mesa' => $id_mesa,
            'id_usuario_capitan' => session()->get('id_usuario'),
            'tipo_movimiento' => $tipo_movimiento,
            'motivo' => $motivo,
            'hora_autorizacion' => date('Y-m-d H:i:s')
        ]);
    }
}