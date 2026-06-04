<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Usuarios extends BaseController
{
    // paso final del rf 4 1 y rf 4 4 actualiza la lista general
    public function index()
    {
        $model = new UsuarioModel();
        
        // extrae todos los usuarios de la base de datos sin importar su estado
        $data['usuarios'] = $model->findAll();
        
        return view('admin/usuarios', $data);
    }

    // paso 1 y 2 del rf 4 1 y rf 4 2 selecciona anadir y despliega formulario
    public function agregar()
    {
        return view('admin/usuarios_agregar');
    }

    // paso 3 del rf 4 1 y rf 4 2 ingresa datos y selecciona guardar
    public function guardar()
    {
        $model = new UsuarioModel();
        $post = $this->request->getPost();

        // excepcion 2 del rf 4 1 y rf 4 2 si se intenta guardar con campos vacios
        if (empty($post['nombre_completo']) || empty($post['id_rol']) || empty($post['username']) || empty($post['password']) || empty($post['telefono']) || empty($post['fecha_ingreso'])) {
            return redirect()->back()->with('error', 'campos obligatorios incompletos');
        }

        $data = [
            'nombre_completo' => $post['nombre_completo'],
            'id_rol'          => $post['id_rol'],
            'username'        => $post['username'],
            'password'        => $post['password'], // se guarda la contrasena en texto plano
            'telefono'        => $post['telefono'],
            'fecha_ingreso'   => $post['fecha_ingreso'],
            'estado_usuario'  => isset($post['estado_usuario']) ? 1 : 0
        ];

        // paso 5 del rf 4 1 y rf 4 2 peticion de insercion a la base de datos
        $model->save($data);
        
        // notificar exito del registro y mostrar mensaje
        return redirect()->to(base_url('usuarios'))->with('success', 'exito del registro nuevo trabajador agregado');
    }

    // paso 1 y 2 del rf 4 3 solicita datos y despliega el formulario precargando
    public function editar($id)
    {
        $model = new UsuarioModel();
        $data['usuario'] = $model->find($id);
        
        return view('admin/usuarios_editar', $data);
    }

    // paso 3 y 4 del rf 4 3 realiza modificaciones y selecciona guardar
    public function actualizar($id)
    {
        $model = new UsuarioModel();
        $post = $this->request->getPost();

        // excepcion 2 del rf 4 3 si se intenta guardar con campos vacios
        if (empty($post['nombre_completo']) || empty($post['id_rol']) || empty($post['username']) || empty($post['telefono']) || empty($post['fecha_ingreso'])) {
            return redirect()->back()->with('error', 'todos los campos son obligatorios');
        }

        $data = [
            'nombre_completo' => $post['nombre_completo'],
            'id_rol'          => $post['id_rol'],
            'username'        => $post['username'],
            'telefono'        => $post['telefono'],
            'fecha_ingreso'   => $post['fecha_ingreso'],
            'estado_usuario'  => isset($post['estado_usuario']) ? 1 : 0
        ];

        // si se escribio una contrasena nueva la guarda en texto plano
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }

        // paso 5 del rf 4 3 despliega peticion de actualizacion a la tabla usuarios
        $model->update($id, $data);
        
        // los cambios se almacenan permanentemente y se visualizan
        return redirect()->to(base_url('usuarios'))->with('success', 'los cambios se almacenan permanentemente');
    }

    // paso 1 y 3 del rf 4 4 selecciona eliminar y confirmar inhabilitacion
    public function eliminar($id)
    {
        $model = new UsuarioModel();
        
        $data = [
            'estado_usuario' => 0
        ];
        
        // paso 4 del rf 4 4 peticion de actualizacion para estado inactivo
        $model->update($id, $data);
        
        // paso 5 del rf 4 4 muestra un mensaje de exito
        return redirect()->to(base_url('usuarios'))->with('success', 'exito de la inhabilitacion de cuenta');
    }
}