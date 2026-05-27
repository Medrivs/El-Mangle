<?php

namespace App\Models;

use CodeIgniter\Model;

class PlatilloModel extends Model
{
    protected $table      = 'platillo';
    protected $primaryKey = 'id_platillo'; 

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'nombre_platillo', 
        'descripcion', 
        'precio_venta', 
        'id_categoria', 
        'imagen_url', 
        'disponible'
    ];
}