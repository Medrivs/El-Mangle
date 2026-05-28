<?php
namespace App\Models;
use CodeIgniter\Model;

class DetalleComandaModel extends Model
{
    protected $table = 'Detalle_Comanda';
    protected $primaryKey = 'id_detalle_comanda';
    protected $allowedFields = [
        'id_comanda', 'id_platillo', 'cantidad', 
        'precio_unitario', 'comentarios', 'impresiones_realizadas'
    ];
}