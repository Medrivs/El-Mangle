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

        // Traemos las mesas que ya pidieron la cuenta
        $mesasRaw = $db->table('Mesa m')
                       ->select('m.*, u.nombre_completo as mesero')
                       ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
                       ->where('m.activa', 1)
                       ->where('m.estado_mesa', 'Por Pagar')
                       ->get()->getResultArray();

        $mesasPorCobrar = [];
        $mesaActiva = null;

        // Calculamos el subtotal de cada cuenta
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

            if ($id_mesa_seleccionada == $m['id_mesa']) {
                $mesaActiva = $m;
            }
        }

        $data['mesas'] = $mesasPorCobrar;
        $data['mesa_activa'] = $mesaActiva;

        // DATOS PARA EL CORTE DE CAJA (Simulados para el diseño por ahora)
        $data['corte'] = [
            'efectivo'   => 0.00,
            'tarjeta'    => 2987.00,
            'propinas'   => 0.00,
            'descuentos' => 0.00,
            'venta_neta' => 2987.00
        ];

        return view('caja/index', $data);
    }

    // =======================================================
    // 2. ACCIÓN PARA COBRAR (LIQUIDAR)
    // =======================================================
    public function liquidar()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $id_mesa = $this->request->getPost('id_mesa');
        $metodo_pago = $this->request->getPost('metodo_pago'); 
        
        // Liberamos la mesa para el mesero
        $mesaModel = new MesaModel();
        $mesaModel->update($id_mesa, ['estado_mesa' => 'Libre']);
        
        return redirect()->to(base_url('caja'))->with('success', 'Mesa cobrada y liberada con éxito.');
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