<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CajaSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    public function testFlujoMaestroDeCajaYReportes()
    {
        $db = \Config\Database::connect();
        
        // fase 0 preparacion del entorno
        // forzamos el update para liberar mesas y evitar bloqueos de mysql
        $db->table('Mesa')->where('id_mesa >', 0)->update(['estado_mesa' => 'Libre']); 
        
        // borramos ingresos viejos para que la suma de hoy sea exacta
        $db->table('Cuenta_Pago')->where('id_pago >', 0)->delete();
        $db->table('Reporte_Ventas')->where('id_reporte >', 0)->delete();
        
        $db->table('Metodo_Pago')->ignore(true)->insert(['id_metodo' => 1, 'nombre' => 'Efectivo']);
        $db->table('Metodo_Pago')->ignore(true)->insert(['id_metodo' => 2, 'nombre' => 'Tarjeta']);
        
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 2, 'nombre_rol' => 'Capitán']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 5, 'nombre_rol' => 'Caja']);

        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 500, 'nombre_completo' => 'Cajero Test', 'id_rol' => 5, 'estado_usuario' => 1]);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 200, 'nombre_completo' => 'Capitán Test', 'id_rol' => 2, 'estado_usuario' => 1]);

        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 99, 'nombre_categoria' => 'Dummy Cat']);
        $db->table('Platillo')->ignore(true)->insert(['id_platillo' => 99, 'nombre_platillo' => 'Dummy Platillo', 'id_categoria' => 99, 'precio_venta' => 100, 'disponible' => 1]);

        // fase 1 la escena simulando que el capitan dividio la mesa
        $db->table('Mesa')->insert(['numero_mesa' => 501, 'estado_mesa' => 'Por Pagar', 'activa' => 1]);
        $id_mesaA = $db->insertID();
        $db->table('Comanda')->insert(['id_mesa' => $id_mesaA, 'id_usuario' => 200, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comandaA = $db->insertID();
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comandaA, 'id_platillo' => 99, 'cantidad' => 1, 'precio_unitario' => 200, 'estado' => 'Listo']);

        $db->table('Mesa')->insert(['numero_mesa' => 502, 'estado_mesa' => 'Por Pagar', 'activa' => 1]);
        $id_mesaB = $db->insertID();
        $db->table('Comanda')->insert(['id_mesa' => $id_mesaB, 'id_usuario' => 200, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comandaB = $db->insertID();
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comandaB, 'id_platillo' => 99, 'cantidad' => 1, 'precio_unitario' => 300, 'estado' => 'Listo']);

        // fase 2 ejecucion de cobros multiples
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 500];

        $this->withSession($sesionCajero)->post('caja/liquidar', [
            'id_mesa'        => $id_mesaA,
            'monto_efectivo' => 250,
            'metodo_pago'    => 'efectivo'
        ]);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesaA, 'estado_mesa' => 'Libre']);

        $this->withSession($sesionCajero)->post('caja/liquidar', [
            'id_mesa'        => $id_mesaB,
            'monto_tarjeta'  => 330,
            'metodo_pago'    => 'tarjeta'
        ]);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesaB, 'estado_mesa' => 'Libre']);

        // fase 3 cierre financiero y validacion del reporte
        $corte = $this->withSession($sesionCajero)->post('caja/corte_caja');
        
        // validacion de seguridad para atrapar errores del controlador
        if (session()->has('error')) {
            $this->fail('el corte fue rechazado por el controlador error ' . session('error'));
        }

        $corte->assertRedirectTo(base_url('caja'));

        $this->seeInDatabase('Reporte_Ventas', [
            'total_efectivo'   => 250.00,
            'total_tarjeta'    => 330.00,
            'total_propinas'   => 80.00,
            'venta_neta'       => 500.00,
            'id_usuario_admin' => 500
        ]);
    }
}