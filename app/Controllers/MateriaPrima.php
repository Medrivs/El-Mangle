<?php

namespace App\Controllers;

use App\Models\MateriaPrimaModel;

class MateriaPrima extends BaseController
{
    protected $materiaPrimaModel;

    // inyecta el modelo al inicio para facilitar los mocks en las pruebas
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->materiaPrimaModel = new MateriaPrimaModel();
    }

    // muestra la tabla del inventario
    public function index()
    {
        $data['materias'] = $this->materiaPrimaModel->findAll();
        
        return view('admin/materiaprima', $data);
    }

    // muestra el formulario para registrar producto
    public function agregar()
    {
        return view('admin/materiaprima_agregar');
    }

    // recibe los datos centralizados y los guarda en la base de datos
    public function guardar()
    {
        $post = $this->request->getPost();

        $data = [
            'nombre_producto'      => $post['nombre_producto'] ?? null,
            'stock_actual'         => $post['stock_actual'] ?? null,
            'precio_compra'        => $post['precio_compra'] ?? null,
            'unidad_medida'        => $post['unidad_medida'] ?? null,
            'stock_minimo'         => $post['stock_minimo'] ?? null,
            'fecha_ultima_entrada' => $post['fecha_ultima_entrada'] ?? null,
            'estado_materia'       => 1, // al crearlo por primera vez entra como activo
            'id_usuario'           => session()->get('id_usuario') ?? 1 // automatiza el ID del usuario en turno
        ];

        $this->materiaPrimaModel->save($data);
        
        return redirect()->to(base_url('materiaprima'));
    }

    // carga el formulario con los datos actuales del producto
    public function editar($id)
    {
        $data['materia'] = $this->materiaPrimaModel->find($id); 
        
        return view('admin/materiaprima_editar', $data);
    }

    // procesa y guarda los cambios de la edicion mediante el arreglo post
    public function actualizar($id)
    {
        $post = $this->request->getPost();

        $data = [
            'nombre_producto'      => $post['nombre_producto'] ?? null,
            'stock_actual'         => $post['stock_actual'] ?? null,
            'precio_compra'        => $post['precio_compra'] ?? null,
            'unidad_medida'        => $post['unidad_medida'] ?? null,
            'stock_minimo'         => $post['stock_minimo'] ?? null,
            'fecha_ultima_entrada' => $post['fecha_ultima_entrada'] ?? null,
            'estado_materia'       => isset($post['estado_materia']) ? 1 : 0
        ];

        $this->materiaPrimaModel->update($id, $data);
        
        return redirect()->to(base_url('materiaprima'));
    }

    // elimina un producto mediante borrado logico
    public function eliminar($id)
    {
        $this->materiaPrimaModel->update($id, ['estado_materia' => 0]);
        
        return redirect()->to(base_url('materiaprima'));
    }
}