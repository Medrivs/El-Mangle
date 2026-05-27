<?php
namespace App\Models;
use CodeIgniter\Model;

class MesaModel extends Model
{
    protected $table      = 'mesa';
    protected $primaryKey = 'id_mesa'; 
    protected $allowedFields = ['numero_mesa', 'estado_mesa', 'capacidad', 'activa', 'id_usuario_mesero'];
}
