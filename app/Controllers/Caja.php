<?php
namespace App\Controllers;
use App\Models\MesaModel;
use App\Models\ComandaModel;

class Caja extends BaseController
{
    // =======================================================
    // 1. PANTALLA PRINCIPAL DE CAJA
    // =======================================================
    public function index($id_mesa_seleccionada = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $db = \Config\Database::connect();

        // 1. Cargar mesas pendientes
        $mesasRaw = $db->table('Mesa m')
                       ->select('m.*, u.nombre_completo as mesero')
                       ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
                       ->where('m.activa', 1)
                       ->where('m.estado_mesa', 'Por Pagar')
                       ->get()->getResultArray();

        $mesasPorCobrar = [];
        $mesaActiva = null;

        foreach ($mesasRaw as $m) {
            $comanda = $db->table('Comanda')->where('id_mesa', $m['id_mesa'])->orderBy('id_comanda', 'DESC')->get()->getRowArray();
            $total = 0;
            $items = 0;

            if ($comanda) {
                $totales = $db->query("SELECT SUM(cantidad * precio_unitario) as total, SUM(cantidad) as items FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();
                $total = $totales['total'] ?? 0;
                $items = $totales['items'] ?? 0;
            }

            $m['total'] = $total;
            $m['items'] = $items;
            $m['id_comanda'] = $comanda ? $comanda['id_comanda'] : null;

            $mesasPorCobrar[] = $m;
            if ($id_mesa_seleccionada == $m['id_mesa']) { $mesaActiva = $m; }
        }

        $data['mesas'] = $mesasPorCobrar;
        $data['mesa_activa'] = $mesaActiva;

        // ==========================================================
        // 2. CÁLCULO REAL DEL CORTE DE CAJA (Ventas del día)
        // ==========================================================
        $fecha_hoy = date('Y-m-d');
        
        $pagosHoy = $db->table('Cuenta_Pago cp')
                       ->select('mp.nombre as metodo, SUM(cp.total) as suma_total, SUM(cp.propina) as suma_propinas')
                       ->join('Metodo_Pago mp', 'mp.id_metodo = cp.id_metodo', 'left')
                       ->where('DATE(cp.fecha_hora_pago)', $fecha_hoy)
                       ->groupBy('mp.id_metodo, mp.nombre')
                       ->get()->getResultArray();

        $efectivo = 0.00;
        $tarjeta = 0.00;
        $propinas = 0.00;

        foreach ($pagosHoy as $p) {
            // Validamos contra los nombres en tu tabla Metodo_Pago
            if (strtolower($p['metodo']) == 'efectivo') {
                $efectivo = $p['suma_total'];
            } elseif (strtolower($p['metodo']) == 'tarjeta') {
                $tarjeta = $p['suma_total'];
            }
            $propinas += $p['suma_propinas'];
        }

        $data['corte'] = [
            'efectivo'   => $efectivo,
            'tarjeta'    => $tarjeta,
            'propinas'   => $propinas,
            'descuentos' => 0.00,
            'venta_neta' => $efectivo + $tarjeta
        ];

        return view('caja/index', $data);
    }

    // =======================================================
    // 2. ACCIÓN PARA COBRAR (LIQUIDAR)
    // =======================================================
   public function liquidar()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $db = \Config\Database::connect();
        $id_usuario = session()->get('id_usuario');

        // Recibir datos de la calculadora JS
        $id_mesa = $this->request->getPost('id_mesa');
        $metodo_pago = $this->request->getPost('metodo_pago'); 
        $monto_efectivo = (float)$this->request->getPost('monto_efectivo');
        $monto_tarjeta = (float)$this->request->getPost('monto_tarjeta');

        // 1. Obtener la comanda activa
        $comanda = $db->table('Comanda')->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->get()->getRowArray();

        if ($comanda) {
            $totales = $db->query("SELECT SUM(cantidad * precio_unitario) as total FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();
            $consumo_total = $totales['total'] ?? 0;
            
            $total_cobrado = $monto_efectivo + $monto_tarjeta;
            $propina_total = $total_cobrado - $consumo_total;

            // Si hay propina y el pago fue mixto, registramos toda la propina a la terminal bancaria por lógica contable
            $propina_efectivo = ($metodo_pago == 'efectivo') ? $propina_total : 0;
            $propina_tarjeta = ($metodo_pago == 'tarjeta' || $metodo_pago == 'mixto') ? $propina_total : 0;

            // 2. Registrar pago en EFECTIVO
            if ($monto_efectivo > 0) {
                $db->table('Cuenta_Pago')->insert([
                    'id_comanda'      => $comanda['id_comanda'],
                    'id_usuario'      => $id_usuario,
                    'subtotal'        => ($monto_efectivo / 1.16), // Calculamos subtotal sin IVA para contabilidad
                    'iva'             => $monto_efectivo - ($monto_efectivo / 1.16),
                    'propina'         => $propina_efectivo,
                    'total'           => $monto_efectivo,
                    'id_metodo'       => 1, // ID 1 = Efectivo
                    'fecha_hora_pago' => date('Y-m-d H:i:s')
                ]);
            }

            // 3. Registrar pago en TARJETA
            if ($monto_tarjeta > 0) {
                $db->table('Cuenta_Pago')->insert([
                    'id_comanda'      => $comanda['id_comanda'],
                    'id_usuario'      => $id_usuario,
                    'subtotal'        => ($monto_tarjeta / 1.16),
                    'iva'             => $monto_tarjeta - ($monto_tarjeta / 1.16),
                    'propina'         => $propina_tarjeta,
                    'total'           => $monto_tarjeta,
                    'id_metodo'       => 2, // ID 2 = Tarjeta
                    'fecha_hora_pago' => date('Y-m-d H:i:s')
                ]);
            }
        }

        // 4. Liberar la mesa
        $db->table('Mesa')->where('id_mesa', $id_mesa)->update(['estado_mesa' => 'Libre']);

        return redirect()->to(base_url('caja'))->with('success', 'Cuenta pagada exitosamente. Dinero ingresado a caja.');
    }

    // =======================================================
    // 3. ACCIÓN PARA EL CORTE DE CAJA
    // =======================================================
    public function corte_caja()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));
        
        $mesaModel = new MesaModel();
        
        // Verificamos si hay alguna mesa ocupada o por cobrar
        $mesasPendientes = $mesaModel->whereIn('estado_mesa', ['Ocupada', 'Por Pagar'])->countAllResults();

        if ($mesasPendientes > 0) {
            return redirect()->to(base_url('caja'))->with('error', '⚠️ No puedes hacer el Corte de Caja. Todavía hay mesas consumiendo o por pagar en el restaurante.');
        }

        // Si todas están libres, el corte es exitoso
        return redirect()->to(base_url('caja'))->with('success', '✅ Corte de Caja realizado correctamente. Se registraron las ventas del turno.');
    }
}