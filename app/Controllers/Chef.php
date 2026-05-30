<?php

namespace App\Controllers;

class Chef extends BaseController
{
    protected $db;

    // inicializa las dependencias para inyectar la bd durante las pruebas
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
    }

    // carga el tablero kanban filtrado por estacion y el inventario lateral
    public function dashboard()
    {
        if (!session()->get('isLoggedIn') || !in_array(session()->get('id_rol'), [4, 6])) {
            // Protección de rol de Cocina (comentado a peticion tuya en el original)
            // return redirect()->to(base_url('/')); 
        }

        $get = $this->request->getGet();
        $estacion = $get['estacion'] ?? 'caliente';
        
        $categorias = $this->obtenerCategoriasPorEstacion($estacion);

        $data = [
            'estacion_activa' => $estacion,
            'inventario'      => $this->obtenerInventarioPorCategorias($categorias),
            'nuevas'          => $this->agruparPorMesa($this->obtenerComandasPorEstado($categorias, 'Pendiente')),
            'preparando'      => $this->agruparPorMesa($this->obtenerComandasPorEstado($categorias, 'Preparando')),
            'listas'          => array_reverse($this->obtenerComandasPorEstado($categorias, 'Listo'))
        ];

        return view('chef/dashboard', $data);
    }

    // actualiza el estado de preparacion de un platillo y recarga la estacion actual
    public function cambiar_estado($id_detalle, $nuevo_estado)
    {
        $get = $this->request->getGet();
        $estacion = $get['estacion'] ?? 'caliente';

        $this->db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->update(['estado' => $nuevo_estado]);
        
        return redirect()->to(base_url("chef/dashboard?estacion=$estacion"));
    }

    // alterna el switch de advertencia de stock de un ingrediente
    public function toggle_advertencia($id_materia)
    {
        $get = $this->request->getGet();
        $estacion = $get['estacion'] ?? 'caliente';

        $this->db->query("UPDATE Materia_Prima SET alerta_manual = NOT alerta_manual WHERE id_materia_prima = ?", [$id_materia]);
        
        return redirect()->to(base_url("chef/dashboard?estacion=$estacion"));
    }

    // alterna el switch de bloqueo total de un ingrediente
    public function toggle_bloqueo($id_materia)
    {
        $get = $this->request->getGet();
        $estacion = $get['estacion'] ?? 'caliente';

        $this->db->query("UPDATE Materia_Prima SET bloqueado_manual = NOT bloqueado_manual WHERE id_materia_prima = ?", [$id_materia]);
        
        return redirect()->to(base_url("chef/dashboard?estacion=$estacion"));
    }

    // mapea la estacion seleccionada en la url a los IDs de las categorias de platillos
    private function obtenerCategoriasPorEstacion(string $estacion): array
    {
        if ($estacion === 'fria') return [3];
        if ($estacion === 'bebidas') return [1];
        return [2, 4]; // caliente incluye postres por defecto
    }

    // consulta el inventario de insumos requeridos unicamente para la estacion actual
    private function obtenerInventarioPorCategorias(array $categorias): array
    {
        return $this->db->table('Materia_Prima mp')
            ->select('mp.*')
            ->join('Receta r', 'r.id_materia_prima = mp.id_materia_prima')
            ->join('Platillo p', 'p.id_platillo = r.id_platillo')
            ->whereIn('p.id_categoria', $categorias)
            ->groupBy('mp.id_materia_prima')
            ->orderBy('mp.nombre_producto', 'ASC')
            ->get()->getResultArray();
    }

    // consulta las comandas filtradas por categoria y estado de preparacion
    private function obtenerComandasPorEstado(array $categorias, string $estado): array
    {
        return $this->db->table('Detalle_Comanda dc')
            ->select('dc.*, c.fecha_hora, m.numero_mesa, p.nombre_platillo, p.id_categoria')
            ->join('Comanda c', 'c.id_comanda = dc.id_comanda')
            ->join('Mesa m', 'm.id_mesa = c.id_mesa')
            ->join('Platillo p', 'p.id_platillo = dc.id_platillo')
            ->where('dc.estado', $estado)
            ->whereIn('p.id_categoria', $categorias)
            ->orderBy('c.fecha_hora', 'ASC')
            ->get()->getResultArray();
    }

    // formatea un listado de items agrupandolos jerarquicamente bajo la mesa y comanda
    private function agruparPorMesa(array $items): array
    {
        $agrupado = [];
        foreach ($items as $i) {
            $id = $i['id_comanda'];
            if (!isset($agrupado[$id])) {
                $agrupado[$id] = [
                    'id_comanda' => $id, 
                    'mesa'       => $i['numero_mesa'], 
                    'fecha'      => $i['fecha_hora'], 
                    'items'      => []
                ];
            }
            $agrupado[$id]['items'][] = $i;
        }
        return $agrupado;
    }
}