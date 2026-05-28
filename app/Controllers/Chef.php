<?php
namespace App\Controllers;

class Chef extends BaseController
{
    public function dashboard()
    {
        if (!session()->get('isLoggedIn') || (session()->get('id_rol') != 6 && session()->get('id_rol') != 4)) {
            // Protección de rol de Cocina
            // return redirect()->to(base_url('/')); 
        }

        $db = \Config\Database::connect();

        // 1. FILTRADO REVOLUCIONARIO POR ESTACIÓN DE TRABAJO (?estacion=caliente|fria|bebidas)
        $estacion = $this->request->getGet('estacion') ?? 'caliente';
        $data['estacion_activa'] = $estacion;

        $categorias_filtradas = [];
        if ($estacion === 'fria') {
            $categorias_filtradas = [3]; // Barra Fría
        } elseif ($estacion === 'bebidas') {
            $categorias_filtradas = [1]; // Bebidas
        } else {
            $categorias_filtradas = [2, 4]; // Cocina Caliente e incluye Postres
        }

        // 2. GESTIÓN DE DISPONIBILIDAD (Barra lateral derecha)
        $builderInv = $db->table('Materia_Prima mp');
        $builderInv->select('mp.*');
        $builderInv->join('Receta r', 'r.id_materia_prima = mp.id_materia_prima');
        $builderInv->join('Platillo p', 'p.id_platillo = r.id_platillo');
        // Filtramos para que solo salgan los insumos de los platillos de esta estación
        $builderInv->whereIn('p.id_categoria', $categorias_filtradas);
        // Agrupamos para que no se repitan los ingredientes si se usan en varios platillos
        $builderInv->groupBy('mp.id_materia_prima');
        $builderInv->orderBy('mp.nombre_producto', 'ASC');

        $data['inventario'] = $builderInv->get()->getResultArray();

        // 3. TABLERO KANBAN (Agrupado y ordenado por flujo FIFO)
        $builder = $db->table('Detalle_Comanda dc');
        $builder->select('dc.*, c.fecha_hora, m.numero_mesa, p.nombre_platillo, p.id_categoria');
        $builder->join('Comanda c', 'c.id_comanda = dc.id_comanda');
        $builder->join('Mesa m', 'm.id_mesa = c.id_mesa');
        $builder->join('Platillo p', 'p.id_platillo = dc.id_platillo');
        $builder->whereIn('dc.estado', ['Pendiente', 'Preparando', 'Listo']);
        $builder->whereIn('p.id_categoria', $categorias_filtradas); 
        $builder->orderBy('c.fecha_hora', 'ASC'); 

        $todos = $builder->get()->getResultArray();

        $nuevas = [];
        $preparando = [];
        $listas = [];

        foreach ($todos as $item) {
            if ($item['estado'] == 'Pendiente') $nuevas[] = $item;
            elseif ($item['estado'] == 'Preparando') $preparando[] = $item;
            elseif ($item['estado'] == 'Listo') $listas[] = $item;
        }

        $data['nuevas'] = $this->agruparPorMesa($nuevas);
        $data['preparando'] = $this->agruparPorMesa($preparando);
        $data['listas'] = array_reverse($listas); // PILA ESTRICTA (LIFO) para platos terminados

        return view('chef/dashboard', $data);
    }

    private function agruparPorMesa($items) {
        $agrupado = [];
        foreach($items as $i) {
            $id = $i['id_comanda'];
            if(!isset($agrupado[$id])) {
                $agrupado[$id] = ['id_comanda' => $id, 'mesa' => $i['numero_mesa'], 'fecha' => $i['fecha_hora'], 'items' => []];
            }
            $agrupado[$id]['items'][] = $i;
        }
        return $agrupado;
    }

    public function cambiar_estado($id_detalle, $nuevo_estado)
    {
        $db = \Config\Database::connect();
        $db->table('Detalle_Comanda')->where('id_detalle_comanda', $id_detalle)->update(['estado' => $nuevo_estado]);
        return redirect()->to(base_url('chef/dashboard?estacion='.$this->request->getGet('estacion')));
    }

    public function toggle_advertencia($id_materia)
    {
        $db = \Config\Database::connect();
        $db->query("UPDATE Materia_Prima SET alerta_manual = NOT alerta_manual WHERE id_materia_prima = ?", [$id_materia]);
        return redirect()->to(base_url('chef/dashboard?estacion='.$this->request->getGet('estacion')));
    }

    public function toggle_bloqueo($id_materia)
    {
        $db = \Config\Database::connect();
        $db->query("UPDATE Materia_Prima SET bloqueado_manual = NOT bloqueado_manual WHERE id_materia_prima = ?", [$id_materia]);
        return redirect()->to(base_url('chef/dashboard?estacion='.$this->request->getGet('estacion')));
    }
}