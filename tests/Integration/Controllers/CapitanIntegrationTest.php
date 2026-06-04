<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CapitanIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // devuelve la sesion del capitan para las pruebas
    private function getSesionCapitan() {
        return ['isLoggedIn' => true, 'id_rol' => 2, 'id_usuario' => 2];
    }

    // nivel 1 integracion de lectura y reapertura de mesa
    public function testIntegracionLecturaYReapertura()
    {
        $db = \Config\Database::connect();
        
        // inserta mesa en estado por pagar
        $db->table('Mesa')->insert(['numero_mesa' => 201, 'estado_mesa' => 'Por Pagar', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        // verifica que la mesa aparezca en el panel principal del capitan
        $pantalla = $this->withSession($this->getSesionCapitan())->get('capitan');
        $pantalla->assertSee('201');

        // ejecuta la reapertura
        $reabrir = $this->withSession($this->getSesionCapitan())->get("capitan/reabrir/$id_mesa");
        $reabrir->assertRedirectTo(base_url('capitan'));

        // verifica el cambio de estado y la creacion de la auditoria
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa, 'tipo_movimiento' => 'Reapertura']);
    }

    // nivel 2 integracion de lectura y transferencia de mesa
    public function testIntegracionLecturaYTransferencia()
    {
        $db = \Config\Database::connect();

        // prepara mesa origen ocupada y mesa destino libre
        $db->table('Mesa')->insert(['numero_mesa' => 202, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_origen = $db->insertID();
        $db->table('Mesa')->insert(['numero_mesa' => 203, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_destino = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_origen, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // verifica que se lean las mesas en el sistema
        $pantalla = $this->withSession($this->getSesionCapitan())->get('capitan');
        $pantalla->assertSee('202');
        $pantalla->assertSee('203');

        // ejecuta la transferencia de mesa a traves de post
        $transferencia = $this->withSession($this->getSesionCapitan())->post('capitan/transferir', [
            'id_mesa_origen'  => $id_origen,
            'id_mesa_destino' => $id_destino
        ]);
        $transferencia->assertRedirectTo(base_url('capitan'));

        // verifica que los estados se intercambiaron y la comanda se movio
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_origen, 'estado_mesa' => 'Libre']);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_destino, 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Comanda', ['id_comanda' => $id_comanda, 'id_mesa' => $id_destino]);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_origen, 'tipo_movimiento' => 'Transferencia']);
    }

    // nivel 3 integracion total lectura cancelacion y division de cuenta
    public function testIntegracionTotalCancelacionYDivision()
    {
        $db = \Config\Database::connect();
        
        // inserta un platillo real temporal para evitar falla de llave foranea
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 1, 'nombre_categoria' => 'Test']);
        $db->table('Platillo')->ignore(true)->insert(['id_platillo' => 500, 'nombre_platillo' => 'Taco E2E', 'precio_venta' => 20, 'id_categoria' => 1, 'disponible' => 1]);

        $db->table('Mesa')->insert(['numero_mesa' => 204, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        
        // inserta dos detalles para cancelar uno y dividir el otro
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 500, 'cantidad' => 2, 'precio_unitario' => 20]);
        $id_detalle_cancelar = $db->insertID();
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => 500, 'cantidad' => 1, 'precio_unitario' => 20]);
        $id_detalle_dividir = $db->insertID();

        // verifica lectura del detalle
        $pantalla = $this->withSession($this->getSesionCapitan())->get("capitan/detalle_orden/$id_mesa/cancelar");
        $pantalla->assertSee('Taco E2E');

        // ejecuta cancelacion de 1 taco
        $this->withSession($this->getSesionCapitan())->post('capitan/cancelar_item', [
            'id_detalle' => $id_detalle_cancelar,
            'cantidad'   => 1,
            'id_mesa'    => $id_mesa,
            'motivo'     => 'el cliente no lo quiso'
        ]);

        // ejecuta division de cuenta mandando el otro articulo a la mesa b
        $this->withSession($this->getSesionCapitan())->post('capitan/ejecutar_division', [
            'id_mesa' => $id_mesa,
            'sufijo'  => 'b',
            'items'   => [$id_detalle_dividir]
        ]);

        // validaciones fisicas de integracion
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle_cancelar, 'cantidad' => 1]);
        $this->seeInDatabase('Mesa', ['numero_mesa' => '204-B', 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa, 'tipo_movimiento' => 'Cancelacion Platillo']);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa, 'tipo_movimiento' => 'Division de Cuenta']);
    }
}
// vendor/bin/phpunit --filter testIntegracionLecturaYReapertura tests/Integration/Controllers/CapitanIntegrationTest.php --no-coverage
// vendor/bin/phpunit --filter testIntegracionLecturaYTransferencia tests/Integration/Controllers/CapitanIntegrationTest.php --no-coverage
// vendor/bin/phpunit --filter testIntegracionTotalCancelacionYDivision tests/Integration/Controllers/CapitanIntegrationTest.php --no-coverage
