<?php
namespace App\Models;
//Inserta la clase Modelo de CogeIgniter para que podamos heredar
use CodeIgniter\Model; 

 
class UsuarioModel extends Model 
{
    // "Avisa que la tabla en MySQL se llama Usuario"
    protected $table = 'Usuario';
    
    // "Lo mismo avisa que la llave primaria se llama id_usuario"
    protected $primaryKey = 'id_usuario';
    
    // "Si mandan datos de un formulario SOLO deja pasar los que coincidan con estos nombres"
    protected $allowedFields = ['nombre_completo', 'id_rol', 
                                'username', 'password', 'telefono',
                                'fecha_ingreso', 'estado_usuario'];
}