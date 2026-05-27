<?php

namespace App\Controllers;

use App\Models\PlatilloModel;
use App\Models\CategoriaModel;

class Platillos extends BaseController
{
    public function index()
    {
        $model = new PlatilloModel();
        $data['platillos'] = $model->select('platillo.*, categoria.nombre_categoria')
                                   ->join('categoria', 'categoria.id_categoria = platillo.id_categoria', 'left')
                                   ->findAll();
        
        return view('admin/platillos', $data);
    }

    public function agregar()
    {
        $catModel = new CategoriaModel();
        $data['categorias'] = $catModel->findAll();
        return view('admin/platillos_agregar', $data);
    }

    public function guardar()
    {
        $model = new PlatilloModel();
        $rutaImagen = ''; // Variable vacía por si no suben foto

        // 1. Procesamos la subida de la imagen
        $file = $this->request->getFile('imagen');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName(); // Nombre aleatorio seguro
            $file->move('uploads/platillos/', $newName); // La mueve a public/uploads/platillos
            $rutaImagen = 'uploads/platillos/' . $newName; // Guardamos la ruta para la base de datos
        }

        $data = [
            'nombre_platillo' => $this->request->getPost('nombre_platillo'),
            'descripcion'     => $this->request->getPost('descripcion'),
            'precio_venta'    => $this->request->getPost('precio_venta'),
            'id_categoria'    => $this->request->getPost('id_categoria'),
            'imagen_url'      => $rutaImagen, // Aquí va la ruta de la foto física
            'disponible'      => 1
        ];

        $model->save($data);
        return redirect()->to(base_url('platillos'));
    }

    public function editar($id)
    {
        $model = new PlatilloModel();
        $catModel = new CategoriaModel();

        $data['platillo']   = $model->find($id);
        $data['categorias'] = $catModel->findAll();
        
        return view('admin/platillos_editar', $data);
    }

    public function actualizar($id)
    {
        $model = new PlatilloModel();
        
        // Recuperamos la ruta de la imagen que ya tenía por si no sube una nueva
        $rutaImagen = $this->request->getPost('imagen_actual'); 

        $file = $this->request->getFile('imagen');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move('uploads/platillos/', $newName);
            $rutaImagen = 'uploads/platillos/' . $newName; // Si subió una nueva, reemplaza la ruta
        }

        $data = [
            'nombre_platillo' => $this->request->getPost('nombre_platillo'),
            'descripcion'     => $this->request->getPost('descripcion'),
            'precio_venta'    => $this->request->getPost('precio_venta'),
            'id_categoria'    => $this->request->getPost('id_categoria'),
            'imagen_url'      => $rutaImagen,
            'disponible'      => $this->request->getPost('disponible') ? 1 : 0
        ];

        $model->update($id, $data);
        return redirect()->to(base_url('platillos'));
    }

    public function eliminar($id)
    {
        $model = new PlatilloModel();
        $model->update($id, ['disponible' => 0]);
        return redirect()->to(base_url('platillos'));
    }
}