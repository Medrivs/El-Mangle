<?php

namespace App\Controllers;
use App\Models\MesaModel;

class Capitan extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('id_rol') != 2) {
            return redirect()->to(base_url('/'));
        }

        $db = \Config\Database::connect();

        // Traemos TODAS las mesas activas
        $mesasRaw = $db->table('Mesa m')
                       ->select('m.*, u.nombre_completo as mesero')
                       ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
                       ->where('m.activa', 1)
                       ->get()->getResultArray();

        $mesas = [];

        foreach ($mesasRaw as $m) {
            // Buscamos la comanda más reciente de esa mesa
            $comanda = $db->table('Comanda')->where('id_mesa', $m['id_mesa'])->orderBy('id_comanda', 'DESC')->get()->getRowArray();
            
            $total = 0;
            $items = 0;

            if ($comanda && in_array($m['estado_mesa'], ['Ocupada', 'Por Pagar'])) {
                $totales = $db->query("SELECT SUM(cantidad * precio_unitario) as total, SUM(cantidad) as items FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();
                $total = $totales['total'] ?? 0;
                $items = $totales['items'] ?? 0;
            }

            $m['total'] = $total;
            $m['items'] = $items;
            $mesas[] = $m;
        }

        $data['mesas'] = $mesas;
        return view('capitan/index', $data);
    }
    
    // --- ESTAS FUNCIONES LAS PROGRAMAREMOS LUEGO PARA LA LÓGICA TRAS BAMBALINAS ---
    public function transferir_mesa() { /* Lógica para mover de mesa */ }
    public function dividir_cuenta() { /* Lógica para crear Mesa 2-A, 2-B */ }
    public function cancelar_platillo() { /* Lógica para pedir motivo y eliminar */ }
}