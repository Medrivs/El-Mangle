<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Login extends BaseController
{
    protected $usuarioModel;

    // inicializa dependencias para facilitar la inyeccion de mocks en pruebas
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->usuarioModel = new UsuarioModel();
    }

    // carga la vista de login o redirige a la ruta correspondiente si ya hay sesion
    public function index()
    {
        if (session()->get('isLoggedIn')) return redirect()->to($this->rutaPorRol(session()->get('id_rol')));
        
        return view('login');
    }

    // valida el pin ingresado y levanta la sesion si encuentra al usuario
    public function ingresar()
    {
        $pin = $this->request->getPost('pin') ?? '';
        
        // extrae unicamente a los usuarios activos
        $usuarios = $this->usuarioModel->where('estado_usuario', 1)->findAll();

        foreach ($usuarios as $usuario) {
            if ($pin === $usuario['password'] || password_verify($pin, $usuario['password'])) {
                
                session()->set([
                    'id_usuario' => $usuario['id_usuario'],
                    'id_rol'     => $usuario['id_rol'],
                    'nombre'     => $usuario['nombre_completo'],
                    'username'   => $usuario['username'],
                    'isLoggedIn' => true
                ]);

                return redirect()->to($this->rutaPorRol($usuario['id_rol']));
            }
        }

        return redirect()->to(base_url('/'))->with('error', 'PIN incorrecto o no autorizado.');
    }

    // cierra sesion silenciosamente (metodo heredado/antiguo)
    public function salir()
    {
        session()->destroy();
        return redirect()->to(base_url('/'));
    }

    // destruye la sesion y muestra mensaje de exito en pantalla
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('/'))->with('success', 'Sesion cerrada de forma segura.');
    }

    // devuelve la url de destino dependiendo del nivel de acceso del empleado
    private function rutaPorRol($id_rol)
    {
        if ($id_rol == 1) return base_url('usuarios');
        if ($id_rol == 2) return base_url('capitan');
        if ($id_rol == 4) return base_url('chef/dashboard');
        if ($id_rol == 5) return base_url('caja');
        
        return base_url('pos');
    }
}