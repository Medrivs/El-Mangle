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
        // validacion de privilegios
        if (!$this->esCapitanAutorizado()) return redirect()->to(base_url('/'));

        // paso 1 del rf 10 consulta informacion de las mesas
        $mesasRaw = $this->db->table('Mesa m')
            ->select('m.*, u.nombre_completo as mesero')
            ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
            ->where('m.activa', 1)
            ->orderBy('CAST(m.numero_mesa AS UNSIGNED)', 'ASC')
            ->orderBy('m.numero_mesa', 'ASC')
            ->get()->getResultArray();

        $data = ['mesas' => []];

        // paso 2 y 3 del rf 10 visualiza e identifica estado de mesa
        foreach ($mesasRaw as $m) {
            $totales = $this->calcularTotalesMesa($m['id_mesa'], $m['estado_mesa']);
            $m['total'] = $totales['total'];
            $m['items'] = $totales['items'];
            $data['mesas'][] = $m;
        }

        // extrae solo las mesas libres para el modal de transferencia del rf 12
        $data['mesas_libres'] = $this->db->table('Mesa')->where(['estado_mesa' => 'Libre', 'activa' => 1])->orderBy('numero_mesa', 'ASC')->get()->getResultArray();
        
        // extrae la lista de meseros activos para poder asignarlos
        $data['meseros'] = $this->db->table('Usuario')->where('id_rol', 3)->where('estado_usuario', 1)->get()->getResultArray();
        
        return view('capitan/index', $data);
    }

    // transfiere el consumo de una mesa a otra
    public function transferir()
    {
        $post = $this->request->getPost();
        
        // paso 1 del rf 12 seleccionar mesa origen
        $id_origen = $post['id_mesa_origen'] ?? null;
        
        // paso 3 del rf 12 seleccionar mesa destino
        $id_destino = $post['id_mesa_destino'] ?? null;

        $comanda = $this->db->table('Comanda')->where('id_mesa', $id_origen)->orderBy('id_comanda', 'DESC')->get()->getRowArray();
        $mesaOrigen = $this->db->table('Mesa')->where('id_mesa', $id_origen)->get()->getRowArray();
        
        // validacion de estado de mesa destino rf 12
        $mesaDestino = $this->db->table('Mesa')->where('id_mesa', $id_destino)->get()->getRowArray();

        // retorno temprano si faltan datos o si la mesa destino esta ocupada
        if (!$comanda || !$mesaOrigen || !$mesaDestino) return redirect()->back()->with('error', 'mesas o comanda no encontradas');
        if ($mesaDestino['estado_mesa'] !== 'Libre') return redirect()->back()->with('error', 'elegir otra mesa libre la mesa destino esta ocupada');

        $this->db->transStart();

        // paso 4 del rf 12 comanda actualiza ubicacion
        $this->db->table('Comanda')->where('id_comanda', $comanda['id_comanda'])->update(['id_mesa' => $id_destino]);
        
        // actualiza estados fisicos
        $this->db->table('Mesa')->where('id_mesa', $id_destino)->update(['estado_mesa' => $mesaOrigen['estado_mesa']]);
        $this->db->table('Mesa')->where('id_mesa', $id_origen)->update(['estado_mesa' => 'Libre']);

        $this->registrarAuditoria($id_origen, 'Transferencia', "traslado de clientes de mesa {$mesaOrigen['numero_mesa']} a mesa {$mesaDestino['numero_mesa']}");

        $this->db->transComplete();

        // paso 5 del rf 12 refresca vista con nueva mesa
        return $this->db->transStatus() ? redirect()->to(base_url('capitan'))->with('success', 'mesa transferida exitosamente') : redirect()->back()->with('error', 'error al transferir');
    }

    // regresa la mesa a estado ocupada para pedir mas platillos se usa en rf 15
    public function reabrir($id_mesa)
    {
        $this->db->transStart();

        // excepcion rf 15 retornar la mesa al estado abierto
        $this->db->table('Mesa')->where('id_mesa', $id_mesa)->update(['estado_mesa' => 'Ocupada']);
        $this->registrarAuditoria($id_mesa, 'Reapertura', 'reapertura de cuenta solicitada por el capitan');

        $this->db->transComplete();

        return redirect()->to(base_url('capitan'))->with('success', 'cuenta reabierta el mesero ya puede agregar platillos');
    }

    // carga la vista con el detalle de la orden para cancelacion division o reimpresion
    public function detalle_orden($id_mesa, $modo)
    {
        if (!$this->esCapitanAutorizado()) return redirect()->to(base_url('/'));

        $data = [
            'mesa' => $this->db->table('Mesa m')->select('m.*, u.nombre_completo as mesero')->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')->where('id_mesa', $id_mesa)->get()->getRowArray(),
            'modo' => $modo,
            'detalles' => []
        ];

        // extrae la comanda actual
        $comanda = $this->db->table('Comanda')->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->get()->getRowArray();

        if ($comanda) {
            // paso 5 del rf 10 y paso 2 del rf 11 consultar platillos de la mesa y muestra lista
            // paso 2 del rf 14 interfaz comanda despliega lista de platillos
            $data['detalles'] = $this->db->table('Detalle_Comanda dc')
                ->select('dc.*, p.nombre_platillo as platillo')
                ->join('Platillo p', 'p.id_platillo = dc.id_platillo')
                ->where('dc.id_comanda', $comanda['id_comanda'])
                ->get()->getResultArray();
        }

        // paso 6 del rf 10 pantalla comanda muestra los platillos
        return view('capitan/detalle_orden', $data);
    }

    // cancela platillos de la comanda con registro obligatorio
    public function cancelar_item()
    {
        $post = $this->request->getPost();
        
        // paso 3 del rf 14 seleccionar platillo para cancelar
        $id_detalle = $post['id_detalle'] ?? null;
        $cantidad_cancelar = (int)($post['cantidad'] ?? 0);
        $id_mesa = $post['id_mesa'] ?? null;

        $detalle = $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->get()->getRowArray();

        // evita manipulacion en el frontend con cantidad cero o negativa
        if (!$detalle || $cantidad_cancelar <= 0) return redirect()->back()->with('error', 'datos invalidos para cancelar');

        $platillo = $this->db->table('Platillo')->where('id_platillo', $detalle['id_platillo'])->get()->getRowArray();

        $this->db->transStart();

        // paso 5 del rf 14 detalle comanda elimina el producto y actualiza total
        if ($cantidad_cancelar >= $detalle['cantidad']) {
            $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->delete();
        } else {
            $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->update(['cantidad' => $detalle['cantidad'] - $cantidad_cancelar]);
        }

        // paso 6 del rf 14 entidad capitan registra el movimiento
        $this->registrarAuditoria($id_mesa, 'Cancelacion Platillo', "se eliminaron $cantidad_cancelar x {$platillo['nombre_platillo']} motivo del capitan {$post['motivo']}");

        $this->db->transComplete();

        return $this->db->transStatus() ? redirect()->to(base_url("capitan/detalle_orden/$id_mesa/cancelar"))->with('success', 'platillo cancelado correctamente') : redirect()->back()->with('error', 'fallo al cancelar platillo');
    }

    // divide la cuenta creando una mesa virtual nueva con sufijo
    public function ejecutar_division()
    {
        $post = $this->request->getPost();
        
        // excepcion rf 11 si cancela o manda un array vacio mantiene productos originales
        if (empty($post['items'])) return redirect()->back()->with('error', 'debes seleccionar al menos un platillo');

        $mesaOrigen = $this->db->table('Mesa')->where('id_mesa', $post['id_mesa'])->get()->getRowArray();
        $nuevo_numero = $mesaOrigen['numero_mesa'] . '-' . strtoupper($post['sufijo']);

        $this->db->transStart();

        // paso 4 del rf 11 genera nuevo id para la misma mesa simulado mediante sufijo virtual
        $this->db->table('Mesa')->insert([
            'numero_mesa' => $nuevo_numero,
            'estado_mesa' => $mesaOrigen['estado_mesa'],
            'activa' => 1,
            'id_usuario_mesero' => $mesaOrigen['id_usuario_mesero']
        ]);
        $id_mesa_nueva = $this->db->insertID();

        // crea la comanda de la nueva cuenta
        $this->db->table('Comanda')->insert([
            'id_mesa' => $id_mesa_nueva,
            'id_usuario' => session()->get('id_usuario'),
            'fecha_hora' => date('Y-m-d H:i:s')
        ]);
        $id_comanda_nueva = $this->db->insertID();

        // paso 5 del rf 11 mueve los platillos al nuevo id de comanda
        $this->db->table('Detalle_Comanda')->whereIn('id_detalle_comanda', $post['items'])->update(['id_comanda' => $id_comanda_nueva]);

        $this->registrarAuditoria($post['id_mesa'], 'Division de Cuenta', "el capitan separo articulos hacia la nueva cuenta $nuevo_numero");

        $this->db->transComplete();

        // paso 6 del rf 11 guarda cambios y muestra cuentas por separado refrescando mapa
        return $this->db->transStatus() ? redirect()->to(base_url('capitan'))->with('success', "cuenta dividida exitosamente se genero la mesa $nuevo_numero") : redirect()->back()->with('error', 'error al dividir cuenta');
    }

    // reimprime la cuenta saltando el bloqueo del rf 13
    public function reimprimir_cuenta($id_mesa)
    {
        // paso 4 del rf 15 validar privilegios de sesion activos
        if (!$this->esCapitanAutorizado()) return redirect()->to(base_url('/'));

        $comanda = $this->db->table('Comanda')->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->get()->getRowArray();

        // paso 5 del rf 15 incrementar contador en la entidad detalle comanda
        if ($comanda) {
            $this->db->query("UPDATE Detalle_Comanda SET impresiones_realizadas = impresiones_realizadas + 1 WHERE id_comanda = ?", [$comanda['id_comanda']]);
        }

        // paso 6 del rf 15 enviar orden a la impresora fisica
        $this->registrarAuditoria($id_mesa, 'Reimpresion', 'el capitan forzo la reimpresion de un ticket de cuenta');

        // paso 7 y 8 del rf 15 notificar reimpresion exitosa y retornar al mapa
        return redirect()->to(base_url('capitan'))->with('success', 'reimpresion autorizada ticket enviado a impresora');
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

        // el agrupador de sql recalcula los totales independientemente
        $totales = $this->db->query("SELECT SUM(cantidad * precio_unitario) as total, SUM(cantidad) as items FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();

        return [
            'total' => $totales['total'] ?? 0,
            'items' => $totales['items'] ?? 0
        ];
    }

    // consolida la insercion de auditoria obligatoria para el capitan
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
    
    // paso 1 recibe los datos del formulario
    public function asignar_mesero()
    {
        $post = $this->request->getPost();
        $id_mesa = $post['id_mesa'] ?? null;
        $id_mesero = $post['id_usuario_mesero'] ?? null;

        // validacion de datos vacios
        if (empty($id_mesa) || empty($id_mesero)) {
            return redirect()->back()->with('error', 'datos incompletos para asignar mesa');
        }

        // paso 2 actualiza el id del mesero en la tabla mesa
        $this->db->table('Mesa')->where('id_mesa', $id_mesa)->update(['id_usuario_mesero' => $id_mesero]);

        // registra el movimiento en auditoria
        $mesa = $this->db->table('Mesa')->where('id_mesa', $id_mesa)->get()->getRowArray();
        $this->registrarAuditoria($id_mesa, 'Asignacion', "el capitan asigno la mesa {$mesa['numero_mesa']} a un nuevo mesero");

        // paso 3 recarga la pagina con mensaje de exito
        return redirect()->to(base_url('capitan'))->with('success', 'mesa asignada al mesero correctamente');
    }
}