<?php

namespace App\Controllers;

use App\Models\MesaModel;
use App\Models\ComandaModel;

class Caja extends BaseController
{
    protected $db;
    protected $mesaModel;
    protected $comandaModel;

    // inicializa dependencias para facilitar inyeccion en pruebas
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->mesaModel = new MesaModel();
        $this->comandaModel = new ComandaModel();
    }

    // carga el panel con las mesas pendientes y totales historicos del dia
    public function index($id_mesa_sel = null)
    {
        if (!$this->esCajeroAutorizado()) return redirect()->to(base_url('/'));

        $data = [
            'mesas' => [], 
            'mesa_activa' => null, 
            // paso 4 del rf 18 mostrar balance esperado
            'corte' => $this->obtenerVentasDelDia()
        ];

        // paso 1 del rf 16 consultar tickets pendientes
        $mesasRaw = $this->db->table('Mesa m')
            ->select('m.*, u.nombre_completo as mesero')
            ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
            ->where(['m.activa' => 1, 'm.estado_mesa' => 'Por Pagar'])
            ->get()->getResultArray();

        foreach ($mesasRaw as $m) {
            // paso 2 del rf 16 cuenta pago muestra monto total
            $m = array_merge($m, $this->obtenerTotalesMesa($m['id_mesa']));
            $data['mesas'][] = $m;
            if ($id_mesa_sel == $m['id_mesa']) $data['mesa_activa'] = $m;
        }

        return view('caja/index', $data);
    }

    // procesa el cobro aislando propinas y cambia la mesa a libre
    public function liquidar()
    {
        if (!$this->esCajeroAutorizado()) return redirect()->to(base_url('/'));

        $post = $this->request->getPost();
        $id_mesa = $post['id_mesa'] ?? null;
        
        $comanda = $this->comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
        if (!$comanda) return redirect()->back()->with('error', 'comanda no encontrada');

        // paso 1 del rf 17 detalle comanda suma precio venta
        $consumo = $this->obtenerTotalesMesa($id_mesa)['total'];
        $monto_efectivo = (float)($post['monto_efectivo'] ?? 0);
        $monto_tarjeta = (float)($post['monto_tarjeta'] ?? 0);
        
        // paso 3 y 4 del rf 16 seleccionar metodo de pago y confirmar recepcion
        $metodo_pago = $post['metodo_pago'] ?? '';
        
        // excepcion del rf 16 y rf 17 si el monto no cuadra o es insuficiente pantalla caja bloquea el cierre
        if (($monto_efectivo + $monto_tarjeta) < $consumo) {
            return redirect()->back()->with('error', 'solicita completar pago monto insuficiente');
        }

        // calcula la propina global validando que no existan numeros negativos
        $propina_total = ($monto_efectivo + $monto_tarjeta) - $consumo;
        if ($propina_total < 0) $propina_total = 0;

        $this->db->transStart();

        // paso 5 del rf 16 y rf 17 cuenta pago registra total efectivo y total tarjeta liquida comanda
        if ($monto_efectivo > 0) {
            $propina = ($metodo_pago === 'efectivo') ? $propina_total : 0;
            $this->registrarPago($comanda['id_comanda'], $monto_efectivo, $propina, 1);
        }

        if ($monto_tarjeta > 0) {
            $propina = in_array($metodo_pago, ['tarjeta', 'mixto']) ? $propina_total : 0;
            $this->registrarPago($comanda['id_comanda'], $monto_tarjeta, $propina, 2);
        }

        $mesa = $this->mesaModel->find($id_mesa);
        
        // verifica si el numero de mesa tiene un guion
        if (strpos((string)$mesa['numero_mesa'], '-') !== false) {
            // paso 6 del rf 16 mesa cambia estado a libre y se inactiva para desaparecer sin romper la base de datos
            $this->mesaModel->update($id_mesa, ['estado_mesa' => 'Libre', 'activa' => 0]);
        } else {
            // paso 6 del rf 16 mesa cambia estado a libre
            $this->mesaModel->update($id_mesa, ['estado_mesa' => 'Libre']);
        }
        
        $this->db->transComplete();

        return $this->db->transStatus() 
            ? redirect()->to(base_url('caja'))->with('success', 'notificar exito y liberar vista')
            : redirect()->back()->with('error', 'error al procesar el pago en base de datos');
    }

    // bloquea el cierre si hay clientes y guarda la venta final
    public function corte_caja()
    {
        if (!$this->esCajeroAutorizado()) return redirect()->to(base_url('/'));
        
        // paso 1 del rf 18 validar mesas libres
        // excepcion rf 18 si hay mesas abiertas bloquear proceso
        if ($this->mesaModel->whereIn('estado_mesa', ['Ocupada', 'Por Pagar'])->where('activa', 1)->countAllResults() > 0) {
            return redirect()->to(base_url('caja'))->with('error', 'bloquear proceso avisar al capitan hay mesas abiertas');
        }

        // paso 5 del rf 18 ingresar monto fisico y confirmar
        $ventasDia = $this->obtenerVentasDelDia();

        $this->db->transStart();

        // paso 6 del rf 18 registrar venta y bloquear turno
        $this->db->table('Reporte_Ventas')->insert([
            'fecha_cierre'     => date('Y-m-d'),
            'total_efectivo'   => $ventasDia['efectivo'],
            'total_tarjeta'    => $ventasDia['tarjeta'],
            'total_propinas'   => $ventasDia['propinas'],
            'venta_neta'       => $ventasDia['venta_neta'],
            'id_usuario_admin' => session()->get('id_usuario')
        ]);

        $this->db->transComplete();

        // paso 7 del rf 18 generar reporte digital cierre finalizado con exito
        return $this->db->transStatus()
            ? redirect()->to(base_url('caja'))->with('success', 'cierre finalizado con exito')
            : redirect()->to(base_url('caja'))->with('error', 'error en base de datos al guardar el corte');
    }

    // centraliza validacion de sesion
    private function esCajeroAutorizado(): bool
    {
        $isLoggedIn = (bool) session()->get('isLoggedIn');
        $rol = (int) session()->get('id_rol');
        
        return $isLoggedIn && in_array($rol, [1, 5]); 
    }

    // devuelve la suma del costo de platillos de la bd aislando la logica
    private function obtenerTotalesMesa($id_mesa): array
    {
        $comanda = $this->comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
        if (!$comanda) return ['total' => 0, 'items' => 0, 'id_comanda' => null];

        // paso 1 del rf 17 detalle comanda suma precio venta
        $totales = $this->db->query("SELECT SUM(cantidad * precio_unitario) as total, SUM(cantidad) as items FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();
        
        return ['total' => $totales['total'] ?? 0, 'items' => $totales['items'] ?? 0, 'id_comanda' => $comanda['id_comanda']];
    }

    // calcula dinero real en caja aislando las propinas para la venta neta
    private function obtenerVentasDelDia(): array
    {
        // paso 2 y 3 del rf 18 consultar ids de tickets pagados y datos de montos efectivo tarjeta
        $pagos = $this->db->table('Cuenta_Pago cp')
            ->select('mp.nombre as metodo, SUM(cp.total) as suma_total, SUM(cp.propina) as suma_propinas')
            ->join('Metodo_Pago mp', 'mp.id_metodo = cp.id_metodo', 'left')
            ->where('DATE(cp.fecha_hora_pago)', date('Y-m-d'))
            ->groupBy('mp.id_metodo, mp.nombre')->get()->getResultArray();

        $corte = ['efectivo' => 0, 'tarjeta' => 0, 'propinas' => 0, 'descuentos' => 0, 'venta_neta' => 0];
        
        foreach ($pagos as $p) {
            $metodo = strtolower($p['metodo'] ?? '');
            if (strpos($metodo, 'efectivo') !== false) $corte['efectivo'] += $p['suma_total'];
            if (strpos($metodo, 'tarjeta') !== false) $corte['tarjeta'] += $p['suma_total'];
            $corte['propinas'] += $p['suma_propinas'];
        }
        
        // paso 6 del rf 17 entidad venta actualiza venta neta
        $corte['venta_neta'] = ($corte['efectivo'] + $corte['tarjeta']) - $corte['propinas'];
        return $corte;
    }

    // calcula el subtotal e iva unicamente sobre el consumo base sin tocar propinas
    private function registrarPago($id_comanda, $total_recibido, $propina, $id_metodo)
    {
        // paso 2 del rf 17 cuenta pago calcula subtotal e impuestos
        $consumo_restaurante = $total_recibido - $propina;
        $subtotal = $consumo_restaurante / 1.16;
        $iva = $consumo_restaurante - $subtotal;

        // paso 3 del rf 17 cuenta pago genera monto total
        $this->db->table('Cuenta_Pago')->insert([
            'id_comanda'      => $id_comanda,
            'id_usuario'      => session()->get('id_usuario'),
            'subtotal'        => $subtotal,
            'iva'             => $iva,
            'propina'         => $propina,
            'total'           => $total_recibido,
            'id_metodo'       => $id_metodo,
            'fecha_hora_pago' => date('Y-m-d H:i:s')
        ]);
    }
}