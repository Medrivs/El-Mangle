<?php
namespace App\Controllers;
use App\Models\MesaModel;
use App\Models\PlatilloModel; // Agregamos el modelo de los platillos
use App\Models\CategoriaModel;

class Pos extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to(base_url('/'));
        }

        $mesaModel = new MesaModel();
        $id_usuario = session()->get('id_usuario');

        if (session()->get('id_rol') == 1 || session()->get('id_rol') == 2) {
            $data['mesas'] = $mesaModel->where('activa', 1)->findAll();
        } else {
            $data['mesas'] = $mesaModel->where('id_usuario_mesero', $id_usuario)
                                       ->where('activa', 1)->findAll();
        }

        // --- CALCULO DE MÉTRICAS OPERATIVAS EN TIEMPO REAL ---
        $db = \Config\Database::connect();
        
        // 1. Total de comandas/mesas que ha abierto este mesero en su turno
        $queryMesas = $db->query("SELECT COUNT(id_comanda) as total FROM Comanda WHERE id_usuario = ?", [$id_usuario]);
        $data['total_mesas_atendidas'] = $queryMesas->getRow()->total ?? 0;

        // 2. Consumo total acumulado (Vendido) por este mesero
        $queryConsumo = $db->query("
            SELECT SUM(dc.cantidad * dc.precio_unitario) as total 
            FROM Detalle_Comanda dc
            JOIN Comanda c ON c.id_comanda = dc.id_comanda
            WHERE c.id_usuario = ?
        ", [$id_usuario]);
        $data['consumo_total_mesero'] = $queryConsumo->getRow()->total ?? 0;

        return view('pos/mesas', $data);
    }

    // NUEVA FUNCIÓN: Abre la pantalla para tomar pedido
   public function mesa($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new MesaModel();
        $catModel  = new CategoriaModel();

        $data['mesa'] = $mesaModel->find($id_mesa);
        $data['categorias'] = $catModel->findAll(); // Traemos Barra Fría, Bebidas, etc.

        return view('pos/categorias', $data);
    }
    // Paso 2: Ver platillos de la categoría seleccionada
// Paso 2: Ver platillos divididos por pestañas (subcategorías)
public function filtrar($id_mesa, $id_categoria, $subcategoria_activa = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new MesaModel();
        $catModel  = new CategoriaModel();
        $platilloModel = new PlatilloModel();

        $data['mesa'] = $mesaModel->find($id_mesa);
        $data['categoria'] = $catModel->find($id_categoria);
        
        // Buscamos las subcategorías únicas que existen para esta categoría
        $db = \Config\Database::connect();
        $query = $db->query("SELECT DISTINCT subcategoria FROM Platillo WHERE id_categoria = ? AND disponible = 1", [$id_categoria]);
        $data['pestañas'] = $query->getResultArray();

        // Si se seleccionó una subcategoría específica, cargamos sus platillos cruzando insumos
        if ($subcategoria_activa !== null) {
            $data['pestaña_activa'] = urldecode($subcategoria_activa);
            
            $platillosRaw = $platilloModel->where('id_categoria', $id_categoria)
                                          ->where('subcategoria', $data['pestaña_activa'])
                                          ->where('disponible', 1)
                                          ->findAll();
                                          
            $data['platillos'] = [];
            foreach ($platillosRaw as $p) {
                // Buscamos todas las materias primas asociadas a la receta de este platillo
                $ingredientes = $db->query("
                    SELECT mp.bloqueado_manual, mp.alerta_manual, mp.stock_actual, mp.stock_minimo 
                    FROM Receta r
                    JOIN Materia_Prima mp ON mp.id_materia_prima = r.id_materia_prima
                    WHERE r.id_platillo = ?
                ", [$p['id_platillo']])->getResultArray();

                $bloqueado = false;
                $advertencia = false;

                foreach ($ingredientes as $ing) {
                    // Si el chef bloqueó el insumo, o el stock físico llegó a cero absoluto
                    if ($ing['bloqueado_manual'] == 1 || $ing['stock_actual'] <= 0) {
                        $bloqueado = true;
                    }
                    // Si el chef advirtió el insumo, o el stock cayó por debajo del mínimo de control
                    if ($ing['alerta_manual'] == 1 || $ing['stock_actual'] <= $ing['stock_minimo']) {
                        $advertencia = true;
                    }
                }

                $p['ingredientes_bloqueados'] = $bloqueado;
                $p['ingredientes_alerta'] = $advertencia;
                $data['platillos'][] = $p;
            }
        } else {
            $data['pestaña_activa'] = null;
            $data['platillos'] = [];
        }

        return view('pos/platillos', $data);
    }
    public function seleccionar_platillo($id_mesa, $id_platillo)
    {
        $platilloModel = new PlatilloModel();
        $platillo = $platilloModel->find($id_platillo);

        // Si el platillo tiene id_padre, es una variante
        // Si no, es el platillo principal que tiene opciones
        $data['platillo'] = $platillo;
        
        // Buscamos sus variantes o sus opciones de tamaño
        $data['opciones'] = $platilloModel->where('id_padre', $id_platillo)->findAll();

        return view('pos/personalizar', $data);
    }
    public function menu_principal($id_mesa) {
    $mesaModel = new MesaModel();
    // ¡Asegurate de pasar $mesa a la vista!
    $data['mesa'] = $mesaModel->find($id_mesa);
    
    return view('pos/menu_principal', $data); 
}
// =======================================================
    // 1. LÓGICA DE LA ORDEN Y BASE DE DATOS
    // =======================================================
    public function enviar_orden()
    {
        $db = \Config\Database::connect();
        $comandaModel = new \App\Models\ComandaModel();
        $detalleModel = new \App\Models\DetalleComandaModel();
        $mesaModel = new \App\Models\MesaModel();

        $db->transBegin();

        try {
            $id_mesa = $this->request->getPost('id_mesa');
            $datos_json = $this->request->getPost('datos_carrito');
            $carrito = json_decode($datos_json, true);

            $mesa = $mesaModel->find($id_mesa);

            // Si la mesa está libre, creamos una NUEVA comanda.
            // Si está ocupada, le sumamos los platillos a la comanda existente.
            if ($mesa['estado_mesa'] == 'Libre' || empty($mesa['estado_mesa'])) {
                $id_comanda = $comandaModel->insert([
                    'id_mesa'    => $id_mesa,
                    'id_usuario' => session()->get('usuario_id') ?? 1, 
                    'fecha_hora' => date('Y-m-d H:i:s')
                ]);
                // Cambiamos el estado a ROJO (Ocupada)
                $mesaModel->update($id_mesa, ['estado_mesa' => 'Ocupada']);
            } else {
                // Buscamos la comanda actual de esa mesa (la última)
                $comanda = $comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
                $id_comanda = $comanda['id_comanda'];
            }

            // Insertamos los detalles de los platillos
           // Insertamos los detalles de los platillos
            foreach ($carrito as $item) {
                $detalleModel->insert([
                    'id_comanda'            => $id_comanda,
                    'id_platillo'           => $item['id'],
                    'cantidad'              => $item['cant'],
                    'precio_unitario'       => $item['precio'],
                    'comentarios'           => $item['nota'],
                    'impresiones_realizadas'=> 0,
                    'estado'                => 'Pendiente' // El estado inicial para el KDS
                ]);

                // --- MAGIA DE INVENTARIO: DESCONTAMOS LAS RECETAS ---
                // Buscamos qué materias primas usa este platillo
                $recetas = $db->table('Receta')->where('id_platillo', $item['id'])->get()->getResultArray();
                
                foreach ($recetas as $r) {
                    $cantidad_a_descontar = $r['cantidad_usada'] * $item['cant'];
                    // Actualizamos el stock en la base de datos restando la cantidad
                    $db->query("UPDATE Materia_Prima SET stock_actual = stock_actual - ? WHERE id_materia_prima = ?", 
                        [$cantidad_a_descontar, $r['id_materia_prima']]
                    );
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Error al procesar la orden.');
            } else {
                $db->transCommit();
                // Redirigimos al historial de la cuenta en lugar de ir al mapa
                return redirect()->to(base_url('pos/ver_comanda/' . $id_mesa))->with('success', 'Orden enviada a cocina.');
            }

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // =======================================================
    // 2. VER HISTORIAL DE LA MESA (COMANDA)
    // =======================================================
    public function ver_comanda($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new \App\Models\MesaModel();
        $comandaModel = new \App\Models\ComandaModel();
        
        $data['mesa'] = $mesaModel->find($id_mesa);
        
        // Obtener la última comanda de esta mesa
        $comanda = $comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
        
        $data['detalles'] = [];
        $data['total'] = 0;

        if ($comanda) {
            // Hacemos un JOIN con la tabla de platillos para saber el nombre
            $db = \Config\Database::connect();
            $data['detalles'] = $db->table('Detalle_Comanda dc')
                                   ->select('dc.*, p.nombre_platillo')
                                   ->join('Platillo p', 'p.id_platillo = dc.id_platillo')
                                   ->where('dc.id_comanda', $comanda['id_comanda'])
                                   ->get()->getResultArray();
                                   
            foreach($data['detalles'] as $d) {
                $data['total'] += ($d['cantidad'] * $d['precio_unitario']);
            }
        }

        return view('pos/comanda', $data);
    }

// =======================================================
    // IMPRIMIR CUENTA (MÁXIMO 1 IMPRESIÓN POR SEGURIDAD)
    // =======================================================
    public function imprimir_cuenta($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new \App\Models\MesaModel();
        $mesa = $mesaModel->find($id_mesa);

        // CANDADO DE SEGURIDAD: Si el estado ya es 'Por Pagar', significa que ya se imprimió una vez
        if ($mesa['estado_mesa'] === 'Por Pagar') {
            return redirect()->to(base_url('pos/ver_comanda/'.$id_mesa))
                             ->with('error', '¡Accion denegada! La cuenta de esta mesa ya fue impresa una vez. No se permite re-imprimir por politicas de seguridad.');
        }

        // Si es la primera vez, cambiamos el estado a AMARILLO (Por Pagar)
        $mesaModel->update($id_mesa, ['estado_mesa' => 'Por Pagar']);

        // [Aquí se ejecuta la lógica de tu ticketera física]

        return redirect()->to(base_url('pos/ver_comanda/'.$id_mesa))
                         ->with('success', 'Cuenta impresa de forma exitosa. Mesa bloqueada para caja.');
    }

}