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

        // Si se seleccionó una subcategoría específica, cargamos sus platillos
        if ($subcategoria_activa !== null) {
            $data['pestaña_activa'] = urldecode($subcategoria_activa);
            $data['platillos'] = $platilloModel->where('id_categoria', $id_categoria)
                                               ->where('subcategoria', $data['pestaña_activa'])
                                               ->where('disponible', 1)
                                               ->findAll();
        } else {
            // Si es null, el mesero está en el menú principal de subcategorías
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
}