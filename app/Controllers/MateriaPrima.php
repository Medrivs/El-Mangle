<?php

namespace App\Controllers;

use App\Models\MateriaPrimaModel;

class MateriaPrima extends BaseController
{
    // Muestra la tabla del inventario
    public function index()
    {
        $model = new MateriaPrimaModel();
        $data['materias'] = $model->findAll();
        
        return view('admin/materiaprima', $data);
    }

    // Muestra el formulario para registrar producto
    public function agregar()
    {
        return view('admin/materiaprima_agregar');
    }

    // Recibe los datos y los guarda en la base de datos
    public function guardar()
    {
        $model = new MateriaPrimaModel();

        $data = [
            'nombre_producto'      => $this->request->getPost('nombre_producto'),
            'stock_actual'         => $this->request->getPost('stock_actual'),
            'precio_compra'        => $this->request->getPost('precio_compra'),
            'unidad_medida'        => $this->request->getPost('unidad_medida'),
            'stock_minimo'         => $this->request->getPost('stock_minimo'),
            'fecha_ultima_entrada' => $this->request->getPost('fecha_ultima_entrada'),
            'estado_materia'       => 1, // Al crearlo por primera vez entra como Activo
            'id_usuario'           => 1
        ];

        $model->save($data);
        return redirect()->to(base_url('materiaprima'));
    }

    // Carga el formulario con los datos actuales del producto
    public function editar($id)
    {
        $model = new MateriaPrimaModel();
        $data['materia'] = $model->find($id); // Busca el producto por su ID primario
        
        return view('admin/materiaprima_editar', $data);
    }

    // Procesa y guarda los cambios de la edición
    public function actualizar($id)
    {
        $model = new MateriaPrimaModel();

        // Recogemos todos los campos incluyendo el estado para permitir la reactivación
        $data = [
            'nombre_producto'      => $this->request->getPost('nombre_producto'),
            'stock_actual'         => $this->request->getPost('stock_actual'),
            'precio_compra'        => $this->request->getPost('precio_compra'),
            'unidad_medida'        => $this->request->getPost('unidad_medida'),
            'stock_minimo'         => $this->request->getPost('stock_minimo'),
            'fecha_ultima_entrada' => $this->request->getPost('fecha_ultima_entrada'),
            // Si el checkbox está marcado se guarda 1 (Activo/Alta), si no, se queda en 0 (Inactivo/Baja)
            'estado_materia'       => $this->request->getPost('estado_materia') ? 1 : 0
        ];

        $model->update($id, $data);
        return redirect()->to(base_url('materiaprima'));
    }

    // Elimina un producto (Borrado Lógico)
    public function eliminar($id)
    {
        $model = new MateriaPrimaModel();
        
        // Cambiamos el estado a 0 (Inactivo) en lugar de borrar la fila
        $data = [
            'estado_materia' => 0
        ];
        
        $model->update($id, $data);
        return redirect()->to(base_url('materiaprima'));
    }
}