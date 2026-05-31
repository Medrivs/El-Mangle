<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class M03_SupervisionSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    // ===================================================================
    // ⚙️ CONFIGURACIÓN DE BASE DE DATOS
    // ===================================================================
    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     
    // ===================================================================

    public function testFlujoMaestroDeSupervisionYControl()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: AISLAMIENTO Y BLINDAJE DE CATÁLOGOS
        // ===================================================================
        $db->table('Mesa')->where('id_mesa >', 0)->update(['estado_mesa' => 'Libre']);
        $db->table('Movimientos')->where('id_movimiento >', 0)->delete();
        
        // Catálogos base obligatorios
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 2, 'nombre_rol' => 'Capitán']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 201, 'nombre_completo' => 'Capi Test', 'id_rol' => 2, 'estado_usuario' => 1]);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 301, 'nombre_completo' => 'Mese Test', 'id_rol' => 3, 'estado_usuario' => 1]);
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 88, 'nombre_categoria' => 'Bebidas']);
        
        // Creamos dos platillos para poder jugar con ellos
        $db->table('Platillo')->ignore(true)->insert(['id_platillo' => 88, 'nombre_platillo' => 'Limonada', 'id_categoria' => 88, 'precio_venta' => 50, 'disponible' => 1]);
        $db->table('Platillo')->ignore(true)->insert(['id_platillo' => 89, 'nombre_platillo' => 'Naranjada', 'id_categoria' => 88, 'precio_venta' => 50, 'disponible' => 1]);

        // ===================================================================
        // FASE 1: EL ESCENARIO BASE (Mesa Ocupada con Consumo)
        // ===================================================================
        // Mesa Origen
        $db->table('Mesa')->insert(['numero_mesa' => 600, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 301]);
        $id_mesa_origen = $db->insertID();
        
        // Mesa Destino (Libre y esperando)
        $db->table('Mesa')->insert(['numero_mesa' => 700, 'estado_mesa' => 'Libre', 'activa' => 1]);
        $id_mesa_destino = $db->insertID();

        // Creamos la comanda y le metemos 3 limonadas y 1 naranjada
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa_origen, 'id_usuario' => 301, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 88, 'cantidad' => 3, 'precio_unitario' => 50, 'estado' => 'Pendiente']);
        $id_detalle_limonada = $db->insertID();
        
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 89, 'cantidad' => 1, 'precio_unitario' => 50, 'estado' => 'Pendiente']);
        $id_detalle_naranjada = $db->insertID();

        // Sesión Maestra
        $sesionCapitan = ['isLoggedIn' => true, 'id_rol' => 2, 'id_usuario' => 201];

        // ===================================================================
        // FASE 2: TRANSFERENCIA DE MESA (Los clientes se cambian de lugar)
        // ===================================================================
        $transferencia = $this->withSession($sesionCapitan)->post('capitan/transferir', [
            'id_mesa_origen'  => $id_mesa_origen,
            'id_mesa_destino' => $id_mesa_destino
        ]);
        
        $transferencia->assertRedirectTo(base_url('capitan'));
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa_origen, 'estado_mesa' => 'Libre']);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa_destino, 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa_origen, 'tipo_movimiento' => 'Transferencia']);

        // ===================================================================
        // FASE 3: CANCELACIÓN PARCIAL (Devuelven 1 de las 3 limonadas)
        // ===================================================================
        $cancelacion = $this->withSession($sesionCapitan)->post('capitan/cancelar_item', [
            'id_mesa'    => $id_mesa_destino, // Trabajamos sobre la nueva mesa
            'id_detalle' => $id_detalle_limonada,
            'cantidad'   => 1, // Restamos solo 1
            'motivo'     => 'Derrame accidental'
        ]);

        $cancelacion->assertRedirectTo(base_url("capitan/detalle_orden/$id_mesa_destino/cancelar"));
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle_limonada, 'cantidad' => 2]); // Eran 3, quedan 2
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa_destino, 'tipo_movimiento' => 'Cancelacion Platillo']);

        // ===================================================================
        // FASE 4: DIVISIÓN DE CUENTA (Separan la naranjada)
        // ===================================================================
        $division = $this->withSession($sesionCapitan)->post('capitan/ejecutar_division', [
            'id_mesa' => $id_mesa_destino,
            'sufijo'  => 'B',
            'items'   => [$id_detalle_naranjada] // Mandamos el ID de la naranjada a la nueva mesa
        ]);

        $division->assertRedirectTo(base_url('capitan'));
        
        // Verificamos que se haya clonado una mesa virtual con el sufijo -B
        $this->seeInDatabase('Mesa', ['numero_mesa' => '700-B', 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa_destino, 'tipo_movimiento' => 'Division de Cuenta']);
    }
}