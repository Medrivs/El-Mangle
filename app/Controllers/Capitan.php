<?php

namespace App\Controllers;
use App\Models\MesaModel;

class Capitan extends BaseController
{
    // =======================================================
    // 1. PANTALLA PRINCIPAL DEL CAPITÁN
    // =======================================================
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('id_rol') != 2) {
            return redirect()->to(base_url('/'));
        }

        $db = \Config\Database::connect();

        // Mesas ordenadas para el mapa
        // Traemos TODAS las mesas activas con ORDENAMIENTO MATEMÁTICO ESTRICTO
        $mesasRaw = $db->table('Mesa m')
                       ->select('m.*, u.nombre_completo as mesero')
                       ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
                       ->where('m.activa', 1)
                       // 1. Extrae el número base (Ej. de "1-A" saca el "1") y lo ordena
                       ->orderBy('CAST(m.numero_mesa AS UNSIGNED)', 'ASC')
                       // 2. Si hay empate (Ej. "1" y "1-A"), los ordena alfabéticamente
                       ->orderBy('m.numero_mesa', 'ASC') 
                       ->get()->getResultArray();

        $mesas = [];
        foreach ($mesasRaw as $m) {
            $comanda = $db->table('Comanda')->where('id_mesa', $m['id_mesa'])->orderBy('id_comanda', 'DESC')->get()->getRowArray();
            $total = 0; $items = 0;

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

        // Extraemos solo las mesas libres para el Modal de Transferencia
        $data['mesas_libres'] = $db->table('Mesa')->where('estado_mesa', 'Libre')->where('activa', 1)->orderBy('numero_mesa', 'ASC')->get()->getResultArray();

        return view('capitan/index', $data);
    }

    // =======================================================
    // 2. LÓGICA PARA TRANSFERIR MESA
    // =======================================================
    public function transferir()
    {
        $db = \Config\Database::connect();
        $id_origen = $this->request->getPost('id_mesa_origen');
        $id_destino = $this->request->getPost('id_mesa_destino');

        $comanda = $db->table('Comanda')->where('id_mesa', $id_origen)->orderBy('id_comanda', 'DESC')->get()->getRowArray();
        $mesaOrigen = $db->table('Mesa')->where('id_mesa', $id_origen)->get()->getRowArray();
        $mesaDestino = $db->table('Mesa')->where('id_mesa', $id_destino)->get()->getRowArray();

        if ($comanda && $mesaOrigen && $mesaDestino) {
            // 1. Movemos la comanda a la nueva mesa
            $db->table('Comanda')->where('id_comanda', $comanda['id_comanda'])->update(['id_mesa' => $id_destino]);
            
            // 2. La mesa destino adquiere el estado de la original (Ocupada o Por Pagar)
            $db->table('Mesa')->where('id_mesa', $id_destino)->update(['estado_mesa' => $mesaOrigen['estado_mesa']]);
            
            // 3. La mesa origen queda libre
            $db->table('Mesa')->where('id_mesa', $id_origen)->update(['estado_mesa' => 'Libre']);

            // 4. Registramos el movimiento para auditoría
            $db->table('Movimientos')->insert([
                'id_mesa' => $id_origen,
                'id_usuario_capitan' => session()->get('id_usuario'),
                'tipo_movimiento' => 'Transferencia',
                'motivo' => "Traslado de clientes de Mesa {$mesaOrigen['numero_mesa']} a Mesa {$mesaDestino['numero_mesa']}",
                'hora_autorizacion' => date('Y-m-d H:i:s')
            ]);
        }

        return redirect()->to(base_url('capitan'))->with('success', 'Mesa transferida exitosamente.');
    }

    // =======================================================
    // 3. LÓGICA PARA REIMPRIMIR CUENTA
    // =======================================================
    // =======================================================
    // 3. LÓGICA PARA REABRIR CUENTA (Regresar a Ocupada)
    // =======================================================
    public function reabrir($id_mesa)
    {
        $db = \Config\Database::connect();
        
        // 1. Regresamos la mesa a estado "Ocupada" para que el mesero pueda pedir más cosas
        $db->table('Mesa')->where('id_mesa', $id_mesa)->update(['estado_mesa' => 'Ocupada']);
        
        // 2. Registramos el movimiento para auditoría
        $db->table('Movimientos')->insert([
            'id_mesa' => $id_mesa,
            'id_usuario_capitan' => session()->get('id_usuario'),
            'tipo_movimiento' => 'Reapertura',
            'motivo' => "Reapertura de cuenta solicitada por el Capitán",
            'hora_autorizacion' => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('capitan'))->with('success', 'Cuenta reabierta. El mesero ya puede agregar más platillos.');
    }
    // =======================================================
    // 4. VER DETALLE DE LA ORDEN (Para Cancelar o Dividir)
    // =======================================================
// =======================================================
    // 4. VER DETALLE DE LA ORDEN (Para Cancelar o Dividir)
    // =======================================================
    public function detalle_orden($id_mesa, $modo)
    {
        if (!session()->get('isLoggedIn') || session()->get('id_rol') != 2) return redirect()->to(base_url('/'));
        
        $db = \Config\Database::connect();
        
        // Traemos los datos de la mesa y el modo (cancelar o dividir)
        $data['mesa'] = $db->table('Mesa m')->select('m.*, u.nombre_completo as mesero')->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')->where('id_mesa', $id_mesa)->get()->getRowArray();
        $data['modo'] = $modo;
        
        // Traemos la comanda activa
        $comanda = $db->table('Comanda')->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->get()->getRowArray();
        
        $data['detalles'] = [];
        if($comanda){
            // CORRECCIÓN AQUÍ: Cambiamos p.nombre por p.nombre_platillo
            $data['detalles'] = $db->table('Detalle_Comanda dc')
                ->select('dc.*, p.nombre_platillo as platillo')
                ->join('Platillo p', 'p.id_platillo = dc.id_platillo')
                ->where('dc.id_comanda', $comanda['id_comanda'])
                ->get()->getResultArray();
        }
        
        return view('capitan/detalle_orden', $data);
    }

    // =======================================================
    // 5. LÓGICA PARA CANCELAR UN PLATILLO CON MOTIVO OBLIGATORIO
    // =======================================================
    public function cancelar_item()
    {
        $db = \Config\Database::connect();
        
        // Recibimos los datos del formulario (Modal)
        $id_detalle = $this->request->getPost('id_detalle');
        $cantidad_cancelar = (int)$this->request->getPost('cantidad');
        $motivo = $this->request->getPost('motivo');
        $id_mesa = $this->request->getPost('id_mesa');
        
        $detalle = $db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->get()->getRowArray();
        
        if($detalle && $cantidad_cancelar > 0) {
            $platillo = $db->table('Platillo')->where('id_platillo', $detalle['id_platillo'])->get()->getRowArray();
            
            if($cantidad_cancelar >= $detalle['cantidad']) {
                // Si cancela todos los que pidió, borramos la fila por completo
                $db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->delete();
            } else {
                // Si pidió 3 y cancela 1, le restamos la cantidad y actualizamos
                $nueva_cantidad = $detalle['cantidad'] - $cantidad_cancelar;
                $db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->update(['cantidad' => $nueva_cantidad]);
            }
            
            // CORRECCIÓN AQUÍ: Cambiamos $platillo['nombre'] por $platillo['nombre_platillo']
            $db->table('Movimientos')->insert([
                'id_mesa' => $id_mesa,
                'id_usuario_capitan' => session()->get('id_usuario'),
                'tipo_movimiento' => 'Cancelacion Platillo',
                'motivo' => "Se eliminaron $cantidad_cancelar x {$platillo['nombre_platillo']}. Motivo del Capitán: $motivo",
                'hora_autorizacion' => date('Y-m-d H:i:s')
            ]);
        }
        
        return redirect()->to(base_url("capitan/detalle_orden/$id_mesa/cancelar"))->with('success', 'Platillo cancelado correctamente.');
    }
    // =======================================================
    // 6. LÓGICA PARA DIVIDIR CUENTA (Mesa 2-A, 2-B, etc.)
    // =======================================================
    public function ejecutar_division()
    {
        $db = \Config\Database::connect();
        
        $id_mesa_origen = $this->request->getPost('id_mesa');
        $items_seleccionados = $this->request->getPost('items'); // Array con los IDs a mover
        $sufijo = strtoupper($this->request->getPost('sufijo')); // Letra (B, C, D...)

        if (empty($items_seleccionados)) {
            return redirect()->back()->with('error', '⚠️ Debes seleccionar al menos un platillo para separar la cuenta.');
        }

        $mesaOrigen = $db->table('Mesa')->where('id_mesa', $id_mesa_origen)->get()->getRowArray();
        $nuevo_numero = $mesaOrigen['numero_mesa'] . '-' . $sufijo; // Ej: "2-B"

        // 1. Creamos la nueva mesa virtual
        $db->table('Mesa')->insert([
            'numero_mesa' => $nuevo_numero,
            'estado_mesa' => $mesaOrigen['estado_mesa'], // Conserva el estado original
            'activa' => 1,
            'id_usuario_mesero' => $mesaOrigen['id_usuario_mesero'] // Sigue siendo del mismo mesero
        ]);
        $id_mesa_nueva = $db->insertID();

        // 2. Le creamos una nueva comanda a esta mesa virtual
        $db->table('Comanda')->insert([
            'id_mesa' => $id_mesa_nueva,
            'id_usuario' => session()->get('id_usuario'),
            'fecha_hora' => date('Y-m-d H:i:s')
        ]);
        $id_comanda_nueva = $db->insertID();

        // 3. Mudamos los platillos seleccionados a la nueva comanda
        $db->table('Detalle_Comanda')
           ->whereIn('id_detalle_comanda', $items_seleccionados)
           ->update(['id_comanda' => $id_comanda_nueva]);

        // 4. Registramos la auditoría
        $db->table('Movimientos')->insert([
            'id_mesa' => $id_mesa_origen,
            'id_usuario_capitan' => session()->get('id_usuario'),
            'tipo_movimiento' => 'Division de Cuenta',
            'motivo' => "El Capitán separó artículos hacia la nueva cuenta $nuevo_numero",
            'hora_autorizacion' => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('capitan'))->with('success', "✅ Cuenta dividida exitosamente. Se generó la Mesa $nuevo_numero.");
    }
}