<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class M03_SupervisionSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    public function testFlujoMaestroDeSupervisionYControl()
    {
        $db = \Config\Database::connect();
        
        // fase 0 aislamiento y blindaje de catalogos
        $db->table('Mesa')->where('id_mesa >', 0)->update(['estado_mesa' => 'Libre']);
        $db->table('Movimientos')->where('id_movimiento >', 0)->delete();
        
        // catalogos base obligatorios
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 2, 'nombre_rol' => 'Capitán']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 201, 'nombre_completo' => 'Capi Test', 'id_rol' => 2, 'estado_usuario' => 1]);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 301, 'nombre_completo' => 'Mese Test', 'id_rol' => 3, 'estado_usuario' => 1]);

        // usamos los ids reales de tu base de datos para los postres
        // id 88 postre nutella y id 89 yogurth con frambuesa

        // fase 1 el escenario base mesa ocupada con consumo
        // mesa origen
        $db->table('Mesa')->insert(['numero_mesa' => 600, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 301]);
        $id_mesa_origen = $db->insertID();
        
        // mesa destino libre y esperando
        $db->table('Mesa')->insert(['numero_mesa' => 700, 'estado_mesa' => 'Libre', 'activa' => 1]);
        $id_mesa_destino = $db->insertID();

        // creamos la comanda y le metemos 3 postres nutella y 1 yogurth
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa_origen, 'id_usuario' => 301, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 88, 'cantidad' => 3, 'precio_unitario' => 99, 'estado' => 'Pendiente']);
        $id_detalle_nutella = $db->insertID();
        
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 89, 'cantidad' => 1, 'precio_unitario' => 99, 'estado' => 'Pendiente']);
        $id_detalle_yogurth = $db->insertID();

        // sesion maestra
        $sesionCapitan = ['isLoggedIn' => true, 'id_rol' => 2, 'id_usuario' => 201];

        // fase 2 transferencia de mesa los clientes se cambian de lugar
        $transferencia = $this->withSession($sesionCapitan)->post('capitan/transferir', [
            'id_mesa_origen'  => $id_mesa_origen,
            'id_mesa_destino' => $id_mesa_destino
        ]);
        
        $transferencia->assertRedirectTo(base_url('capitan'));
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa_origen, 'estado_mesa' => 'Libre']);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa_destino, 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa_origen, 'tipo_movimiento' => 'Transferencia']);

        // fase 3 cancelacion parcial devuelven 1 de los 3 postres nutella
        $cancelacion = $this->withSession($sesionCapitan)->post('capitan/cancelar_item', [
            'id_mesa'    => $id_mesa_destino, 
            'id_detalle' => $id_detalle_nutella,
            'cantidad'   => 1, 
            'motivo'     => 'el cliente se arrepintio'
        ]);

        $cancelacion->assertRedirectTo(base_url("capitan/detalle_orden/$id_mesa_destino/cancelar"));
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle_nutella, 'cantidad' => 2]); 
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa_destino, 'tipo_movimiento' => 'Cancelacion Platillo']);

        // fase 4 division de cuenta separan el yogurth
        $division = $this->withSession($sesionCapitan)->post('capitan/ejecutar_division', [
            'id_mesa' => $id_mesa_destino,
            'sufijo'  => 'B',
            'items'   => [$id_detalle_yogurth] 
        ]);

        $division->assertRedirectTo(base_url('capitan'));
        
        // verificamos que se haya clonado una mesa virtual con el sufijo b
        $this->seeInDatabase('Mesa', ['numero_mesa' => '700-B', 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa_destino, 'tipo_movimiento' => 'Division de Cuenta']);
    }
}