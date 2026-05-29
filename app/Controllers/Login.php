<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Login extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to($this->rutaPorRol(session()->get('id_rol')));
        }
        return view('login');
    }

    public function ingresar()
    {
        $model = new UsuarioModel();
        $pin = $this->request->getPost('pin');

        // Traemos a todos los usuarios que están dados de alta
        $usuarios = $model->where('estado_usuario', 1)->findAll();
        $usuarioAutenticado = null;

        // Buscamos de quién es el PIN que acaban de teclear
        foreach ($usuarios as $usuario) {
            // Verifica si el PIN coincide (ya sea texto plano o encriptado)
            if ($pin === $usuario['password'] || password_verify($pin, $usuario['password'])) {
                $usuarioAutenticado = $usuario;
                break; // Lo encontramos, detenemos la búsqueda
            }
        }

        // Si encontró al dueño del PIN, lo deja entrar
        if ($usuarioAutenticado) {
            $sessionData = [
                'id_usuario' => $usuarioAutenticado['id_usuario'],
                'id_rol'     => $usuarioAutenticado['id_rol'],
                'nombre'     => $usuarioAutenticado['nombre_completo'],
                'username'   => $usuarioAutenticado['username'],
                'isLoggedIn' => true
            ];
            session()->set($sessionData);

            return redirect()->to($this->rutaPorRol($usuarioAutenticado['id_rol']));
        }

        // Si el PIN no es de nadie, marca error
        return redirect()->to(base_url('/'))->with('error', 'PIN incorrecto o no autorizado.');
    }

    public function salir()
    {
        session()->destroy();
        return redirect()->to(base_url('/'));
    }

private function rutaPorRol($id_rol)
    {
        if ($id_rol == 1) {
            // Rol 1: Administrador -> Back-Office
            return base_url('usuarios'); 
            
        } elseif ($id_rol == 4) {
            // Rol 4: Chef -> Pantalla de Cocina (KDS)
            return base_url('chef/dashboard'); 
            
        } elseif ($id_rol == 5) {
            // Rol 5: Cajero -> Módulo de Caja
            return base_url('caja');
            
        } else {
            // Rol 2 (Capitán) y Rol 3 (Mesero) caen aquí -> Punto de Venta (POS)
            return base_url('pos'); 
        }
    }
    
    // =======================================================
    // CERRAR SESIÓN Y DESTRUIR COMODINES DE MEMORIA
    // =======================================================
    public function logout()
    {
        // 1. Destruimos toda la sesion de PHP en el servidor
        session()->destroy();

        // 2. Redirigimos a la pantalla de login principal con un mensaje de éxito
        return redirect()->to(base_url('/'))->with('success', 'Sesion cerrada de forma segura.');
    }
}