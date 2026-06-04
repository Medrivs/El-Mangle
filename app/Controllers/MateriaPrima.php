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

    // paso 1 del rf3.1 ingresa al modulo de materia prima
    public function index()
    {
        // paso 2 del rf3.1 peticion a la base de datos
        // traemos solo los activos para cumplir con ocultarlos al eliminarlos
        $materiasBrutas = $this->materiaPrimaModel->where('estado_materia', 1)->findAll();
        
        // excepcion 1 del rf3.1 si la tabla esta vacia se manda la variable para notificar
        $data['total_registros'] = count($materiasBrutas);

        // paso 3 del rf3.1 organiza la informacion
        usort($materiasBrutas, function($a, $b) {
            return strcmp($a['nombre_producto'], $b['nombre_producto']);
        });

        $data['materias'] = $materiasBrutas;
        
        // paso 4 del rf3.1 muestra la tabla de inventario completa
        return view('admin/materiaprima', $data);
    }

    // paso 1 y 2 del rf3.2 selecciona anadir insumo y despliega el formulario
    public function agregar()
    {
        return view('admin/materiaprima_agregar');
    }

    // evalua el paso 3 y 4 del rf3.2 recibiendo los datos
    public function guardar()
    {
        $post = $this->request->getPost();

        // excepcion 2 del rf3.2 si se intenta guardar con campos vacios
        if (empty($post['nombre_producto']) || empty($post['unidad_medida']) || $post['precio_compra'] == '' || $post['stock_actual'] == '' || $post['stock_minimo'] == '') {
            return redirect()->back()->with('error', 'todos los campos son obligatorios');
        }

        $data = [
            'nombre_producto'      => $post['nombre_producto'],
            'stock_actual'         => $post['stock_actual'],
            'precio_compra'        => $post['precio_compra'],
            'unidad_medida'        => $post['unidad_medida'],
            'stock_minimo'         => $post['stock_minimo'],
            'fecha_ultima_entrada' => $post['fecha_ultima_entrada'] ?? date('Y-m-d'),
            'estado_materia'       => 1,
            'id_usuario'           => session()->get('id_usuario') ?? 1
        ];

        // paso 5 del rf3.2 peticion de insercion a la base de datos
        $this->materiaPrimaModel->save($data);
        
        // notificar exito del registro y actualizar la tabla
        return redirect()->to(base_url('materiaprima'))->with('success', 'el nuevo insumo queda registrado');
    }

    // paso 1 y 2 del rf3.3 solicita datos actuales y despliega el formulario precargando datos
    public function editar($id)
    {
        $data['materia'] = $this->materiaPrimaModel->find($id); 
        
        return view('admin/materiaprima_editar', $data);
    }

    // evalua el paso 3 y 4 del rf3.3 recibiendo modificaciones
    public function actualizar($id)
    {
        $post = $this->request->getPost();

        // excepcion 2 del rf3.3 si se intenta guardar con campos vacios
        if (empty($post['nombre_producto']) || empty($post['unidad_medida']) || $post['precio_compra'] == '' || $post['stock_actual'] == '' || $post['stock_minimo'] == '') {
            return redirect()->back()->with('error', 'la informacion es obligatoria');
        }

        $data = [
            'nombre_producto'      => $post['nombre_producto'],
            'stock_actual'         => $post['stock_actual'],
            'precio_compra'        => $post['precio_compra'],
            'unidad_medida'        => $post['unidad_medida'],
            'stock_minimo'         => $post['stock_minimo'],
            'fecha_ultima_entrada' => $post['fecha_ultima_entrada'] ?? date('Y-m-d'),
            'estado_materia'       => isset($post['estado_materia']) ? 1 : 0
        ];

        // paso 5 del rf3.3 peticion de actualizacion a la tabla materia prima
        $this->materiaPrimaModel->update($id, $data);
        
        // la informacion se almacena permanentemente
        return redirect()->to(base_url('materiaprima'))->with('success', 'la informacion se almacena permanentemente y recalcula alerta');
    }

    // paso 3 del rf3.4 selecciona la opcion confirmar
    public function eliminar($id)
    {
        // paso 4 del rf3.4 peticion de actualizacion para cambiar el estado a inactivo
        $this->materiaPrimaModel->update($id, ['estado_materia' => 0]);
        
        // paso 5 del rf3.4 oculta el insumo de la lista y muestra un mensaje de exito
        return redirect()->to(base_url('materiaprima'))->with('success', 'exito de la operacion insumo inhabilitado');
    }
}