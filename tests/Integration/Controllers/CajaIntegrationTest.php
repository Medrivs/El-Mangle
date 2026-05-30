<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CajaIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    // ===================================================================
    // ⚙️ CONFIGURACIÓN DE BASE DE DATOS PARA TESTING
    // ===================================================================
    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     
    // ===================================================================

    // Comprueba que el panel principal extraiga y muestre las mesas pendientes de la BD
    public function testVistaIndexCargaMesasPorPagar()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert([
            'numero_mesa' => 999,
            'estado_mesa' => 'Por Pagar',
            'activa'      => 1
        ]);
        $id_mesa = $db->insertID();

        $db->table('Comanda')->insert([
            'id_mesa' => $id_mesa, 
            'id_usuario' => 1, 
            'fecha_hora' => date('Y-m-d H:i:s')
        ]);

        // Inyectamos sesión y disparamos el GET
        $resultado = $this->withSession([
            'isLoggedIn' => true,
            'id_usuario' => 5
        ])->get('caja');

        $resultado->assertOK(); 
        $resultado->assertSee('999');
    }

    // Simula recibir efectivo, verifica que se registre el cobro y que la mesa quede libre
    public function testLiquidarEfectivoRegistraPagoYLiberaMesa()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert(['numero_mesa' => 888, 'estado_mesa' => 'Por Pagar', 'activa' => 1]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 1, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        $db->table('Detalle_Comanda')->insert([
            'id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 1, 'precio_unitario' => 500, 'estado' => 'Listo'
        ]);

        $datosPago = [
            'id_mesa'        => $id_mesa,
            'monto_efectivo' => 500,
            'metodo_pago'    => 'efectivo'
        ];

        // Inyectamos sesión y disparamos el POST usando ->post()
        $resultado = $this->withSession([
            'isLoggedIn' => true,
            'id_usuario' => 5
        ])->post('caja/liquidar', $datosPago);

        $resultado->assertRedirectTo(base_url('caja'));
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Libre']);
        $this->seeInDatabase('Cuenta_Pago', ['id_comanda' => $id_comanda, 'total' => 500]);
    }

// Protege el negocio: Intenta forzar un cierre de caja mientras hay clientes y lo aborta
    public function testCorteCajaBloqueadoSiHayMesasOcupadas()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert([
            'numero_mesa' => 777,
            'estado_mesa' => 'Ocupada',
            'activa'      => 1
        ]);

        $resultado = $this->withSession([
            'isLoggedIn' => true,
            'id_usuario' => 5
        ])->post('caja/corte_caja');

        $resultado->assertRedirectTo(base_url('caja'));
        
        $this->dontSeeInDatabase('Reporte_Ventas', [
            'fecha_cierre' => date('Y-m-d')
        ]);
    }
}

//vendor/bin/phpunit --filter testCorteCajaBloqueadoSiHayMesasOcupadas --no-coverage
//vendor/bin/phpunit --filter testLiquidarEfectivoRegistraPagoYLiberaMesa --no-coverage
//vendor/bin/phpunit --filter testVistaIndexCargaMesasPorPagar --no-coverage
