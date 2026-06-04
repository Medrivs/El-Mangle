<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Login extends BaseController
{
    protected $usuarioModel;

    // inicializa dependencias
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->usuarioModel = new UsuarioModel();
    }

    // 1 solicitar inicio de sesion y 2 mostrar campos de login
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to($this->rutaPorRol(session()->get('id_rol')));
        }
        
        return view('login');
    }

    // 3 ingresar pin y entrar
    public function ingresar()
    {
        $pin = $this->request->getPost('pin') ?? '';
        
        // 4 validar pin
        $usuario = $this->buscarUsuarioPorPin($pin);

        if (!$usuario) {
            // error de acceso datos incorrectos
            return redirect()->to(base_url('/'))->with('error', 'pin incorrecto o no autorizado');
        }

        // 5 verificar estado de usuario activo
        if ($usuario['estado_usuario'] != 1) {
            // cuenta inactiva prohibir acceso
            return redirect()->to(base_url('/'))->with('error', 'cuenta inactiva');
        }

        // 6 identificar rol asignado
        $id_rol = $usuario['id_rol'];

        // 7 abrir modulo y notificar exito
        $this->crearSesion($usuario, $id_rol);
        
        return redirect()->to($this->rutaPorRol($id_rol));
    }

    // cierra sesion silenciosamente
    public function salir()
    {
        session()->destroy();
        return redirect()->to(base_url('/'));
    }

    // destruye la sesion y muestra mensaje de exito
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('/'))->with('success', 'sesion cerrada de forma segura');
    }

    // iteramos todos los usuarios para validar el pin extraido
    private function buscarUsuarioPorPin($pin)
    {
        $usuarios = $this->usuarioModel->findAll();
        
        foreach ($usuarios as $u) {
            if ($pin === $u['password'] || password_verify($pin, $u['password'])) {
                return $u;
            }
        }
        
        return null;
    }

    // guarda los datos del usuario en la sesion del servidor
    private function crearSesion($usuario, $id_rol)
    {
        session()->set([
            'id_usuario' => $usuario['id_usuario'],
            'id_rol'     => $id_rol,
            'nombre'     => $usuario['nombre_completo'],
            'username'   => $usuario['username'],
            'isLoggedIn' => true
        ]);
    }

    // enruta segun el rol asignado
    private function rutaPorRol($id_rol)
    {
        if ($id_rol == 1) return base_url('usuarios');
        if ($id_rol == 2) return base_url('capitan');
        if ($id_rol == 4) return base_url('chef/dashboard');
        if ($id_rol == 5) return base_url('caja');
        
        return base_url('pos');
    }
}