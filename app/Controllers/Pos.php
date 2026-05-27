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
        
        // Buscamos qué pestañas (subcategorías) existen en esta categoría
        $db = \Config\Database::connect();
        $query = $db->query("SELECT DISTINCT subcategoria FROM platillo WHERE id_categoria = ? AND disponible = 1", [$id_categoria]);
        $data['pestañas'] = $query->getResultArray();

        // Si entramos por primera vez y no hemos tocado ninguna pestaña, seleccionamos la primera por defecto
        if ($subcategoria_activa == null && !empty($data['pestañas'])) {
            $subcategoria_activa = $data['pestañas'][0]['subcategoria'];
        }
        
        // Decodificamos por si la URL tiene espacios (ej. "Cazuelas Calientes")
        $data['pestaña_activa'] = urldecode($subcategoria_activa);

        // Traemos SOLO los platillos de la pestaña seleccionada
        $data['platillos'] = $platilloModel->where('id_categoria', $id_categoria)
                                           ->where('subcategoria', $data['pestaña_activa'])
                                           ->where('disponible', 1)
                                           ->findAll();

        return view('pos/platillos', $data);
    }
}