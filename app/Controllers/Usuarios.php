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

    public function guardar()
    {
        $model = new UsuarioModel();
        $model->save($this->request->getPost());
        return redirect()->to('/usuarios');
    }

    public function eliminar($id)
    {
        $model = new UsuarioModel();
        $model->delete($id);
        return redirect()->to('/usuarios');
    }
}