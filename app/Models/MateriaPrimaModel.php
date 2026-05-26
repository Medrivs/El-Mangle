<?php

namespace App\Models;

use CodeIgniter\Model;

class MateriaPrimaModel extends Model
{
    protected $table      = 'materia_prima';
    protected $primaryKey = 'id_materia_prima'; 

    protected $useAutoIncrement = true;

    // Agregamos 'estado_materia' al final de la lista
    protected $allowedFields = [
        'nombre_producto', 
        'stock_actual', 
        'precio_compra', 
        'unidad_medida', 
        'stock_minimo', 
        'fecha_ultima_entrada', 
        'id_usuario',
        'estado_materia' 
    ];
}