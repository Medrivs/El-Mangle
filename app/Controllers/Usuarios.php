<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Usuarios extends BaseController
{
    public function index()
    {
        $model = new UsuarioModel();
        $data['usuarios'] = $model->findAll();
        return view('admin/usuarios', $data);
    }

    public function agregar()
    {
        return view('admin/usuarios_agregar');
    }

    public function guardar()
    {
        $model = new UsuarioModel();
        $data = [
            'nombre_completo' => $this->request->getPost('nombre_completo'),
            'id_rol'          => $this->request->getPost('id_rol'),
            'username'        => $this->request->getPost('username'),
            'password'        => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'telefono'        => $this->request->getPost('telefono'),
            'fecha_ingreso'   => $this->request->getPost('fecha_ingreso'),
            'estado_usuario'  => $this->request->getPost('estado_usuario') ? 1 : 0
        ];
        $model->save($data);
        return redirect()->to(base_url('usuarios'));
    }

    public function editar($id)
    {
        $model = new UsuarioModel();
        $data['usuario'] = $model->find($id);
        return view('admin/usuarios_editar', $data);
    }

    public function actualizar($id)
    {
        $model = new UsuarioModel();
        $data = [
            'nombre_completo' => $this->request->getPost('nombre_completo'),
            'id_rol'          => $this->request->getPost('id_rol'),
            'username'        => $this->request->getPost('username'),
            'telefono'        => $this->request->getPost('telefono'),
            'fecha_ingreso'   => $this->request->getPost('fecha_ingreso'),
            'estado_usuario'  => $this->request->getPost('estado_usuario') ? 1 : 0
        ];

        if (!empty($this->request->getPost('password'))) {
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        $model->update($id, $data);
        return redirect()->to(base_url('usuarios'));
    }

    // FUNCIÓN ELIMINAR: Verifica que el nombre del ID coincida con tu base de datos
// FUNCIÓN DAR DE BAJA (Borrado Lógico)
    public function eliminar($id)
    {
        $model = new UsuarioModel();
        
        // En lugar de borrarlo de la base de datos, 
        // simplemente actualizamos su estado a 0 (Inactivo)
        $data = [
            'estado_usuario' => 0
        ];
        
        $model->update($id, $data);
        
        return redirect()->to(base_url('usuarios'));
    }
}