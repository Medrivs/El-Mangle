<?php

namespace App\Controllers;

use App\Models\PlatilloModel;
use App\Models\CategoriaModel;

class Platillos extends BaseController
{
    protected $platilloModel;
    protected $categoriaModel;

    // inyecta los modelos para permitir pruebas unitarias con mocks
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->platilloModel = new PlatilloModel();
        $this->categoriaModel = new CategoriaModel();
    }

    public function index()
    {
        $data['platillos'] = $this->platilloModel->select('platillo.*, categoria.nombre_categoria')
            ->join('categoria', 'categoria.id_categoria = platillo.id_categoria', 'left')
            ->findAll();
        
        return view('admin/platillos', $data);
    }

    public function agregar()
    {
        return view('admin/platillos_agregar', ['categorias' => $this->categoriaModel->findAll()]);
    }

    public function guardar()
    {
        $post = $this->request->getPost();
        $data = [
            'nombre_platillo' => $post['nombre_platillo'] ?? '',
            'descripcion'     => $post['descripcion'] ?? '',
            'precio_venta'    => $post['precio_venta'] ?? 0,
            'id_categoria'    => $post['id_categoria'] ?? null,
            'imagen_url'      => $this->procesarImagen(),
            'disponible'      => 1
        ];

        $this->platilloModel->save($data);
        return redirect()->to(base_url('platillos'));
    }

    public function editar($id)
    {
        $data = [
            'platillo'   => $this->platilloModel->find($id),
            'categorias' => $this->categoriaModel->findAll()
        ];
        
        return view('admin/platillos_editar', $data);
    }

    public function actualizar($id)
    {
        $post = $this->request->getPost();
        $data = [
            'nombre_platillo' => $post['nombre_platillo'] ?? '',
            'descripcion'     => $post['descripcion'] ?? '',
            'precio_venta'    => $post['precio_venta'] ?? 0,
            'id_categoria'    => $post['id_categoria'] ?? null,
            'imagen_url'      => $this->procesarImagen($post['imagen_actual'] ?? ''),
            'disponible'      => isset($post['disponible']) ? 1 : 0
        ];

        $this->platilloModel->update($id, $data);
        return redirect()->to(base_url('platillos'));
    }

    public function eliminar($id)
    {
        $this->platilloModel->update($id, ['disponible' => 0]);
        return redirect()->to(base_url('platillos'));
    }

    // método privado para manejar la logica de subida de archivos (reutilizable)
    private function procesarImagen(string $ruta_actual = ''): string
    {
        $file = $this->request->getFile('imagen');
        
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move('uploads/platillos/', $newName);
            return 'uploads/platillos/' . $newName;
        }
        
        return $ruta_actual;
    }
}