<?php

namespace App\Controllers;

use App\Models\MesaModel;
use App\Models\PlatilloModel;
use App\Models\CategoriaModel;
use App\Models\ComandaModel;
use App\Models\DetalleComandaModel;

class Pos extends BaseController
{
    protected $db;
    protected $mesaModel;
    protected $platilloModel;
    protected $categoriaModel;
    protected $comandaModel;
    protected $detalleModel;

    // inyecta dependencias para facilitar mocks en testing
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->mesaModel = new MesaModel();
        $this->platilloModel = new PlatilloModel();
        $this->categoriaModel = new CategoriaModel();
        $this->comandaModel = new ComandaModel();
        $this->detalleModel = new DetalleComandaModel();
    }

    // consolida la configuracion de interfaz
    private function getUIConfig(): array
    {
        $id_rol = session()->get('id_rol');
        $es_capitan = ($id_rol == 2);
        
        return [
            'bg_header'   => $es_capitan ? 'bg-[#0A1F3D]' : 'bg-[#15325A]',
            'ruta_volver' => $es_capitan ? base_url('capitan') : base_url('pos'),
            'es_capitan'  => $es_capitan
        ];
    }

    // carga el mapa de mesas
    public function index()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $id_usuario = session()->get('id_usuario');
        $id_rol = session()->get('id_rol');

        $builder = $this->mesaModel->where('activa', 1)
                                   ->orderBy('LENGTH(numero_mesa)', 'ASC')
                                   ->orderBy('numero_mesa', 'ASC');

        if (!in_array($id_rol, [1, 2])) {
            $builder->where('id_usuario_mesero', $id_usuario);
        }

        $data = [
            'mesas'                 => $builder->findAll(),
            'total_mesas_atendidas' => $this->calcularTotalMesasAtendidas($id_usuario),
            'consumo_total_mesero'  => $this->calcularConsumoTotalMesero($id_usuario)
        ];

        return view('pos/mesas', $data);
    }

    // muestra las categorias disponibles
    public function mesa($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $data = [
            'mesa'       => $this->mesaModel->find($id_mesa),
            'categorias' => $this->categoriaModel->findAll()
        ];
        
        $data = array_merge($data, $this->getUIConfig());

        return view('pos/categorias', $data);
    }

    // paso 1 del rf 8 muestra productos filtrando por categoria
    public function filtrar($id_mesa, $id_categoria, $subcategoria_activa = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $data = [
            'mesa'      => $this->mesaModel->find($id_mesa),
            'categoria' => $this->categoriaModel->find($id_categoria),
            'pestañas'  => $this->db->query("SELECT DISTINCT subcategoria FROM Platillo WHERE id_categoria = ? AND disponible = 1", [$id_categoria])->getResultArray()
        ];
        
        $data = array_merge($data, $this->getUIConfig());

        if ($subcategoria_activa !== null) {
            $data['pestaña_activa'] = urldecode($subcategoria_activa);
            $platillosRaw = $this->platilloModel->where(['id_categoria' => $id_categoria, 'subcategoria' => $data['pestaña_activa'], 'disponible' => 1])->findAll();
            
            // paso 4 del rf 8 valida stock actual redirigiendo a la funcion privada
            $data['platillos'] = $this->procesarAlertasInventario($platillosRaw);
        } else {
            $data['pestaña_activa'] = null;
            $data['platillos'] = [];
        }

        return view('pos/platillos', $data);
    }

    // paso 1 y 2 del rf 9 seleccionar platillo de la lista temporal y abrir ventana de comentarios
    public function seleccionar_platillo($id_mesa, $id_platillo)
    {
        $data = [
            'platillo' => $this->platilloModel->find($id_platillo),
            'opciones' => $this->platilloModel->where('id_padre', $id_platillo)->findAll()
        ];

        $data = array_merge($data, $this->getUIConfig());

        return view('pos/personalizar', $data);
    }

    // renderiza el menu principal
    public function menu_principal($id_mesa) 
    {
        $data = ['mesa' => $this->mesaModel->find($id_mesa)];
        $data = array_merge($data, $this->getUIConfig());

        return view('pos/menu_principal', $data); 
    }

    // paso 7 del rf 8 guardar comanda y registrar en base de datos
    public function enviar_orden()
    {
        $post = $this->request->getPost();
        $id_mesa = $post['id_mesa'] ?? null;
        $carrito = json_decode($post['datos_carrito'] ?? '[]', true);

        if (empty($carrito)) return redirect()->back()->with('error', 'el carrito esta vacio');

        $this->db->transStart();

        $id_comanda = $this->gestionarComandaMesa($id_mesa);
        
        // paso 5 del rf 9 guarda los datos en base de datos mandando el carrito a funcion privada
        $this->procesarItemsCarrito($id_comanda, $carrito);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'error al procesar la orden en la base de datos');
        }

        if (session()->get('id_rol') == 2) {
            return redirect()->to(base_url('capitan'))->with('success', 'orden enviada a cocina por el capitan');
        }
        
        return redirect()->to(base_url('pos/ver_comanda/' . $id_mesa))->with('success', 'orden enviada a cocina');
    }

    // muestra el resumen total de la comanda activa
    public function ver_comanda($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $data = [
            'mesa'     => $this->mesaModel->find($id_mesa),
            'detalles' => [],
            'total'    => 0
        ];
        
        $data = array_merge($data, $this->getUIConfig());
        $comanda = $this->comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();

        if ($comanda) {
            $data['detalles'] = $this->db->table('Detalle_Comanda dc')
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

    // paso 1 del rf 13 solicitar impresion de precuenta
    public function imprimir_cuenta($id_mesa)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to(base_url('/'));

        $mesa = $this->mesaModel->find($id_mesa);
        
        // paso 2 del rf 13 consultar impresiones realizadas extrayendo el registro
        $comanda = $this->comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();

        // paso 3 del rf 13 validar que el contador sea igual a cero
        if ($mesa['estado_mesa'] === 'Por Pagar') {
            return redirect()->to(base_url('pos/ver_comanda/'.$id_mesa))->with('error', 'accion denegada la cuenta ya fue impresa');
        }

        // paso 4 del rf 13 enviar orden a la impresora fisica simulacion
        
        // paso 5 del rf 13 actualizar contador a uno en la bd a traves de update
        if ($comanda) {
            $this->db->table('Detalle_Comanda')->where('id_comanda', $comanda['id_comanda'])->update(['impresiones_realizadas' => 1]);
        }

        // paso 6 del rf 13 bloquear edicion y agregar platillos
        $this->mesaModel->update($id_mesa, ['estado_mesa' => 'Por Pagar']);

        // paso 7 del rf 13 notificar cuenta impresa en pantalla
        return redirect()->to(base_url('pos/ver_comanda/'.$id_mesa))->with('success', 'cuenta impresa mesa bloqueada para caja');
    }

    // metodos privados para aislar la logica de negocio

    // consulta la cantidad de comandas atendidas
    private function calcularTotalMesasAtendidas($id_usuario): int
    {
        $query = $this->db->query("SELECT COUNT(id_comanda) as total FROM Comanda WHERE id_usuario = ?", [$id_usuario]);
        return (int) ($query->getRow()->total ?? 0);
    }

    // suma el importe total vendido
    private function calcularConsumoTotalMesero($id_usuario): float
    {
        $query = $this->db->query("SELECT SUM(dc.cantidad * dc.precio_unitario) as total FROM Detalle_Comanda dc JOIN Comanda c ON c.id_comanda = dc.id_comanda WHERE c.id_usuario = ?", [$id_usuario]);
        return (float) ($query->getRow()->total ?? 0);
    }

    // consultar existencias reales
    private function procesarAlertasInventario(array $platillosRaw): array
    {
        $platillos_procesados = [];
        
        foreach ($platillosRaw as $p) {
            $ingredientes = $this->db->query("SELECT mp.nombre_producto, mp.bloqueado_manual, mp.alerta_manual, mp.stock_actual, mp.stock_minimo FROM Receta r JOIN Materia_Prima mp ON mp.id_materia_prima = r.id_materia_prima WHERE r.id_platillo = ?", [$p['id_platillo']])->getResultArray();

            $p['nombres_bloqueados'] = [];
            $p['nombres_alerta'] = [];

            foreach ($ingredientes as $ing) {
                $nombre_corto = trim(explode('(', $ing['nombre_producto'])[0]);

                // excepcion del rf 8 producto agotado mensaje agotado y platillo bloqueado
                if ($ing['bloqueado_manual'] == 1 || $ing['stock_actual'] <= 0) {
                    $p['nombres_bloqueados'][] = $nombre_corto;
                } elseif ($ing['alerta_manual'] == 1 || $ing['stock_actual'] <= $ing['stock_minimo']) {
                    $p['nombres_alerta'][] = $nombre_corto;
                }
            }
            $platillos_procesados[] = $p;
        }
        
        return $platillos_procesados;
    }

    // crea una nueva comanda si la mesa esta libre
    private function gestionarComandaMesa($id_mesa): int
    {
        $mesa = $this->mesaModel->find($id_mesa);

        if ($mesa['estado_mesa'] == 'Libre' || empty($mesa['estado_mesa'])) {
            $id_comanda = $this->comandaModel->insert([
                'id_mesa'    => $id_mesa,
                'id_usuario' => session()->get('id_usuario') ?? 1,
                'fecha_hora' => date('Y-m-d H:i:s')
            ]);
            $this->mesaModel->update($id_mesa, ['estado_mesa' => 'Ocupada']);
            return $id_comanda;
        } 

        $comanda = $this->comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
        return (int) $comanda['id_comanda'];
    }

    // procesa el carrito descontando de inventario
    private function procesarItemsCarrito($id_comanda, array $carrito): void
    {
        foreach ($carrito as $item) {
            $this->detalleModel->insert([
                'id_comanda'            => $id_comanda,
                'id_platillo'           => $item['id'],
                'cantidad'              => $item['cant'],
                'precio_unitario'       => $item['precio'],
                // paso 5 del rf 9 guardar nota de preparacion extrayendolo del arreglo
                'comentarios'           => $item['nota'],
                'impresiones_realizadas'=> 0,
                'estado'                => 'Pendiente' 
            ]);

            $recetas = $this->db->table('Receta')->where('id_platillo', $item['id'])->get()->getResultArray();
            foreach ($recetas as $r) {
                $cantidad_a_descontar = $r['cantidad_usada'] * $item['cant'];
                $this->db->query("UPDATE Materia_Prima SET stock_actual = stock_actual - ? WHERE id_materia_prima = ?", [$cantidad_a_descontar, $r['id_materia_prima']]);
            }
        }
    }
}