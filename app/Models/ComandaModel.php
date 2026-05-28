<?php
namespace App\Models;
use CodeIgniter\Model;

class ComandaModel extends Model
{
    protected $table = 'Comanda';
    protected $primaryKey = 'id_comanda';
    protected $allowedFields = ['id_mesa', 'id_usuario', 'fecha_hora'];
}