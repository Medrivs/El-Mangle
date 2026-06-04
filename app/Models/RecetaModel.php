<?php

namespace App\Models;

use CodeIgniter\Model;

class RecetaModel extends Model
{
    // modelo para conectar con la tabla receta
    protected $table = 'Receta';
    protected $primaryKey = 'id_receta';
    
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    // campos permitidos para insercion y actualizacion
    protected $allowedFields = [
        'id_platillo', 
        'id_materia_prima', 
        'cantidad_usada'
    ];
}