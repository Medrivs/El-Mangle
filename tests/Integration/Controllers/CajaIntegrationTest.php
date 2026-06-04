<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CajaIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // Devuelve la sesión del cajero (Victor Hugo - ID 5)
    private function getSesionCaja() {
        return ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 5];
    }

    // NIVEL 1: INTEGRACIÓN DE LECTURA (Cargar Panel y Mesas Pendientes)
    public function testIntegracionLecturaCaja()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert(['numero_mesa' => 101, 'estado_mesa' => 'Por Pagar', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 1, 'precio_unitario' => 500, 'estado' => 'Listo']);

        $pantalla = $this->withSession($this->getSesionCaja())->get('caja');
        $pantalla->assertOK();
        $pantalla->assertSee('101'); 
        $pantalla->assertSee('500'); 
    }

    // NIVEL 2: INTEGRACIÓN DE LECTURA + LIQUIDACIÓN (Cobro)
    public function testIntegracionLecturaYLiquidacion()
    {
        $db = \Config\Database::connect();
        $db->table('Metodo_Pago')->ignore(true)->insert(['id_metodo' => 1, 'nombre' => 'Efectivo']);

        $db->table('Mesa')->insert(['numero_mesa' => 102, 'estado_mesa' => 'Por Pagar', 'activa' => 1, 'id_usuario_mesero' => 7]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 7, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 1, 'precio_unitario' => 300, 'estado' => 'Listo']);

        $pantalla = $this->withSession($this->getSesionCaja())->get('caja');
        $pantalla->assertSee('102');

        $pago = $this->withSession($this->getSesionCaja())->post('caja/liquidar', [
            'id_mesa'        => $id_mesa,
            'monto_efectivo' => 350, 
            'metodo_pago'    => 'efectivo'
        ]);
        $pago->assertRedirectTo(base_url('caja'));

        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Libre']);
        $this->seeInDatabase('Cuenta_Pago', ['id_comanda' => $id_comanda, 'total' => 350, 'propina' => 50]);
    }

    // NIVEL 3: INTEGRACION TOTAL (LECTURA + COBROS MuLTIPLES + CORTE CAJA)
    public function testIntegracionTotalDelModuloCaja()
    {
        $db = \Config\Database::connect();
        $db->table('Metodo_Pago')->ignore(true)->insert(['id_metodo' => 2, 'nombre' => 'Tarjeta']);


        $db->table('Mesa')->where('id_mesa >', 0)->update(['estado_mesa' => 'Libre']);
        
        $db->table('Cuenta_Pago')->where('id_pago >', 0)->delete();
        $db->table('Reporte_Ventas')->where('fecha_cierre', date('Y-m-d'))->delete();

        $db->table('Mesa')->insert(['numero_mesa' => 103, 'estado_mesa' => 'Por Pagar', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 1, 'precio_unitario' => 1000, 'estado' => 'Listo']);

        $this->withSession($this->getSesionCaja())->post('caja/liquidar', [
            'id_mesa'       => $id_mesa,
            'monto_tarjeta' => 1100, // 1000 consumo + 100 propina
            'metodo_pago'   => 'tarjeta'
        ]);

        $corte = $this->withSession($this->getSesionCaja())->post('caja/corte_caja');
        $corte->assertRedirectTo(base_url('caja'));

        $this->seeInDatabase('Reporte_Ventas', [
            'fecha_cierre'   => date('Y-m-d'),
            'total_tarjeta'  => 1100.00,
            'total_propinas' => 100.00,
            'venta_neta'     => 1000.00 
        ]);
    }
}

/* 

// Nivel 1:
vendor/bin/phpunit --filter testIntegracionLecturaCaja tests/Integration/Controllers/CajaIntegrationTest.php --no-coverage

// Nivel 2:
vendor/bin/phpunit --filter testIntegracionLecturaYLiquidacion tests/Integration/Controllers/CajaIntegrationTest.php --no-coverage

// Nivel 3 (Total):
vendor/bin/phpunit --filter testIntegracionTotalDelModuloCaja tests/Integration/Controllers/CajaIntegrationTest.php --no-coverage

// Todo el archivo junto:
vendor/bin/phpunit tests/Integration/Controllers/CajaIntegrationTest.php --no-coverage

*/