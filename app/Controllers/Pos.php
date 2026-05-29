<?php
namespace App\Controllers;
use App\Models\MesaModel;
use App\Models\PlatilloModel;
use App\Models\CategoriaModel;

class Pos extends BaseController
{
    // =======================================================
    // FUNCIÓN MÁGICA PARA MANTENER LA INTERFAZ DEL CAPITÁN
    // =======================================================
    private function _getUIConfig() {
        $id_rol = session()->get('id_rol');
        return [
            // Si es Capitán (2), fondo muy oscuro. Si es mesero, azul normal.
            'bg_header'   => ($id_rol == 2) ? 'bg-[#0A1F3D]' : 'bg-[#15325A]',
            // Si es Capitán (2), el botón de volver lo manda a su mapa global
            'ruta_volver' => ($id_rol == 2) ? base_url('capitan') : base_url('pos'),
            'es_capitan'  => ($id_rol == 2)
        ];
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to(base_url('/'));
        }

        $mesaModel = new MesaModel();
        $id_usuario = session()->get('id_usuario');

        // ORDENAMIENTO INTELIGENTE: Ordena por la longitud primero (para que "10" vaya después del "9") 
        // y luego alfabéticamente (para que "2-B" vaya después de "2").
        if (session()->get('id_rol') == 1 || session()->get('id_rol') == 2) {
            $data['mesas'] = $mesaModel->where('activa', 1)
                                       ->orderBy('LENGTH(numero_mesa)', 'ASC')
                                       ->orderBy('numero_mesa', 'ASC')
                                       ->findAll();
        } else {
            $data['mesas'] = $mesaModel->where('id_usuario_mesero', $id_usuario)
                                       ->where('activa', 1)
                                       ->orderBy('LENGTH(numero_mesa)', 'ASC')
                                       ->orderBy('numero_mesa', 'ASC')
                                       ->findAll();
        }

        // --- CALCULO DE MÉTRICAS OPERATIVAS EN TIEMPO REAL ---
        $db = \Config\Database::connect();
        
        $queryMesas = $db->query("SELECT COUNT(id_comanda) as total FROM Comanda WHERE id_usuario = ?", [$id_usuario]);
        $data['total_mesas_atendidas'] = $queryMesas->getRow()->total ?? 0;

        $queryConsumo = $db->query("
            SELECT SUM(dc.cantidad * dc.precio_unitario) as total 
            FROM Detalle_Comanda dc
            JOIN Comanda c ON c.id_comanda = dc.id_comanda
            WHERE c.id_usuario = ?
        ", [$id_usuario]);
        $data['consumo_total_mesero'] = $queryConsumo->getRow()->total ?? 0;

        return view('pos/mesas', $data);
    }

    public function mesa($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new MesaModel();
        $catModel  = new CategoriaModel();

        $data['mesa'] = $mesaModel->find($id_mesa);
        $data['categorias'] = $catModel->findAll();
        
        // Inyectamos las variables de interfaz
        $data = array_merge($data, $this->_getUIConfig());

        return view('pos/categorias', $data);
    }

    public function filtrar($id_mesa, $id_categoria, $subcategoria_activa = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new MesaModel();
        $catModel  = new CategoriaModel();
        $platilloModel = new PlatilloModel();

        $data['mesa'] = $mesaModel->find($id_mesa);
        $data['categoria'] = $catModel->find($id_categoria);
        
        // Inyectamos las variables de interfaz
        $data = array_merge($data, $this->_getUIConfig());
        
        $db = \Config\Database::connect();
        $query = $db->query("SELECT DISTINCT subcategoria FROM Platillo WHERE id_categoria = ? AND disponible = 1", [$id_categoria]);
        $data['pestañas'] = $query->getResultArray();

        if ($subcategoria_activa !== null) {
            $data['pestaña_activa'] = urldecode($subcategoria_activa);
            
            $platillosRaw = $platilloModel->where('id_categoria', $id_categoria)
                                          ->where('subcategoria', $data['pestaña_activa'])
                                          ->where('disponible', 1)
                                          ->findAll();
                                          
            $data['platillos'] = [];
            foreach ($platillosRaw as $p) {
                $ingredientes = $db->query("
                    SELECT mp.nombre_producto, mp.bloqueado_manual, mp.alerta_manual, mp.stock_actual, mp.stock_minimo 
                    FROM Receta r
                    JOIN Materia_Prima mp ON mp.id_materia_prima = r.id_materia_prima
                    WHERE r.id_platillo = ?
                ", [$p['id_platillo']])->getResultArray();

                $nombres_bloqueados = [];
                $nombres_alerta = [];

                foreach ($ingredientes as $ing) {
                    $nombre_corto = trim(explode('(', $ing['nombre_producto'])[0]);

                    if ($ing['bloqueado_manual'] == 1 || $ing['stock_actual'] <= 0) {
                        $nombres_bloqueados[] = $nombre_corto;
                    } elseif ($ing['alerta_manual'] == 1 || $ing['stock_actual'] <= $ing['stock_minimo']) {
                        $nombres_alerta[] = $nombre_corto;
                    }
                }

                $p['nombres_bloqueados'] = $nombres_bloqueados;
                $p['nombres_alerta'] = $nombres_alerta;
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
        $data['platillo'] = $platilloModel->find($id_platillo);
        $data['opciones'] = $platilloModel->where('id_padre', $id_platillo)->findAll();

        // Inyectamos las variables de interfaz
        $data = array_merge($data, $this->_getUIConfig());

        return view('pos/personalizar', $data);
    }

    public function menu_principal($id_mesa) {
        $mesaModel = new MesaModel();
        $data['mesa'] = $mesaModel->find($id_mesa);
        
        // Inyectamos las variables de interfaz
        $data = array_merge($data, $this->_getUIConfig());

        return view('pos/menu_principal', $data); 
    }

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

            if ($mesa['estado_mesa'] == 'Libre' || empty($mesa['estado_mesa'])) {
                $id_comanda = $comandaModel->insert([
                    'id_mesa'    => $id_mesa,
                    'id_usuario' => session()->get('id_usuario') ?? 1, // Guardará si fue el Capitán o Mesero
                    'fecha_hora' => date('Y-m-d H:i:s')
                ]);
                $mesaModel->update($id_mesa, ['estado_mesa' => 'Ocupada']);
            } else {
                $comanda = $comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
                $id_comanda = $comanda['id_comanda'];
            }

            foreach ($carrito as $item) {
                $detalleModel->insert([
                    'id_comanda'            => $id_comanda,
                    'id_platillo'           => $item['id'],
                    'cantidad'              => $item['cant'],
                    'precio_unitario'       => $item['precio'],
                    'comentarios'           => $item['nota'],
                    'impresiones_realizadas'=> 0,
                    'estado'                => 'Pendiente' 
                ]);

                // Descuento en inventario
                $recetas = $db->table('Receta')->where('id_platillo', $item['id'])->get()->getResultArray();
                foreach ($recetas as $r) {
                    $cantidad_a_descontar = $r['cantidad_usada'] * $item['cant'];
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
                
                // Si es capitán, lo mandamos directo a su mapa general tras enviar la orden a cocina
                if (session()->get('id_rol') == 2) {
                    return redirect()->to(base_url('capitan'))->with('success', 'Orden enviada a cocina por el Capitán.');
                }
                
                return redirect()->to(base_url('pos/ver_comanda/' . $id_mesa))->with('success', 'Orden enviada a cocina.');
            }

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function ver_comanda($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new \App\Models\MesaModel();
        $comandaModel = new \App\Models\ComandaModel();
        
        $data['mesa'] = $mesaModel->find($id_mesa);
        $data = array_merge($data, $this->_getUIConfig()); // Inyectar UI
        
        $comanda = $comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
        
        $data['detalles'] = [];
        $data['total'] = 0;

        if ($comanda) {
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

    public function imprimir_cuenta($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesaModel = new \App\Models\MesaModel();
        $mesa = $mesaModel->find($id_mesa);

        if ($mesa['estado_mesa'] === 'Por Pagar') {
            return redirect()->to(base_url('pos/ver_comanda/'.$id_mesa))
                             ->with('error', '¡Acción denegada! La cuenta ya fue impresa una vez.');
        }

        $mesaModel->update($id_mesa, ['estado_mesa' => 'Por Pagar']);

        // [Lógica ticketera]

        return redirect()->to(base_url('pos/ver_comanda/'.$id_mesa))
                         ->with('success', 'Cuenta impresa. Mesa bloqueada para caja.');
    }
}