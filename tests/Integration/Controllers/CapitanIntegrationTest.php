<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CapitanIntegrationTest extends CIUnitTestCase
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

    // Retorna las variables de sesión del Capitán para no repetir código
    private function getSesionCapitan()
    {
        return [
            'isLoggedIn' => true,
            'id_rol'     => 2, // 2 es Capitán según tu esquema
            'id_usuario' => 2
        ];
    }

    public function testIndexCargaMesasYCalculaTotales()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert(['numero_mesa' => 505, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 2, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // Le metemos un platillo de $800
        $db->table('Detalle_Comanda')->insert([
            'id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 1, 'precio_unitario' => 800, 'estado' => 'Pendiente'
        ]);

        $resultado = $this->withSession($this->getSesionCapitan())->get('capitan');

        $resultado->assertOK();
        $resultado->assertSee('505');
        $resultado->assertSee('800'); // Verifica que el cálculo matemático funcionó y se imprimió
    }

    public function testTransferirMesaMueveComandaYRegistraAuditoria()
    {
        $db = \Config\Database::connect();
        
        // Mesa origen (Ocupada) y Mesa destino (Libre)
        $db->table('Mesa')->insert(['numero_mesa' => 404, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        $id_origen = $db->insertID();
        
        $db->table('Mesa')->insert(['numero_mesa' => 405, 'estado_mesa' => 'Libre', 'activa' => 1]);
        $id_destino = $db->insertID();

        $db->table('Comanda')->insert(['id_mesa' => $id_origen, 'id_usuario' => 2, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        $datosTransferencia = [
            'id_mesa_origen'  => $id_origen,
            'id_mesa_destino' => $id_destino
        ];

        $resultado = $this->withSession($this->getSesionCapitan())->post('capitan/transferir', $datosTransferencia);

        $resultado->assertRedirectTo(base_url('capitan'));
        
        // Validamos la inversión de papeles en MySQL
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_origen, 'estado_mesa' => 'Libre']);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_destino, 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Comanda', ['id_comanda' => $id_comanda, 'id_mesa' => $id_destino]);
        
        // Validamos que el capitán no hizo trampa y su movimiento quedó auditado
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_origen, 'tipo_movimiento' => 'Transferencia']);
    }

    public function testCancelarItemActualizaDetalleYRegistraAuditoria()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert(['numero_mesa' => 303, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 2, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // Insertamos 3 unidades del platillo
        $db->table('Detalle_Comanda')->insert([
            'id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 3, 'precio_unitario' => 100, 'estado' => 'Pendiente'
        ]);
        $id_detalle = $db->insertID();

        $datosCancelacion = [
            'id_mesa'    => $id_mesa,
            'id_detalle' => $id_detalle,
            'cantidad'   => 1, // Vamos a cancelar solo 1
            'motivo'     => 'Cliente cambió de opinión'
        ];

        $resultado = $this->withSession($this->getSesionCapitan())->post('capitan/cancelar_item', $datosCancelacion);

        $resultado->assertRedirectTo(base_url("capitan/detalle_orden/$id_mesa/cancelar"));
        
        // Validamos que de 3 pasaron a ser 2 en la BD
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle, 'cantidad' => 2]);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa, 'tipo_movimiento' => 'Cancelacion Platillo']);
    }

    public function testEjecutarDivisionCreaNuevaMesaYRegistraAuditoria()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert(['numero_mesa' => '100', 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // Item que se va a separar a la nueva cuenta
        $db->table('Detalle_Comanda')->insert([
            'id_comanda' => $id_comanda, 'id_platillo' => 1, 'cantidad' => 1, 'precio_unitario' => 50, 'estado' => 'Pendiente'
        ]);
        $id_item_separado = $db->insertID();

        $datosDivision = [
            'id_mesa' => $id_mesa,
            'sufijo'  => 'B',
            'items'   => [$id_item_separado]
        ];

        $resultado = $this->withSession($this->getSesionCapitan())->post('capitan/ejecutar_division', $datosDivision);

        $resultado->assertRedirectTo(base_url('capitan'));
        
        // Validamos que la nueva mesa se creó
        $this->seeInDatabase('Mesa', ['numero_mesa' => '100-B', 'estado_mesa' => 'Ocupada']);
        
        // Validamos que el platillo ya no pertenece a la comanda vieja
        $this->dontSeeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_item_separado, 'id_comanda' => $id_comanda]);
        $this->seeInDatabase('Movimientos', ['id_mesa' => $id_mesa, 'tipo_movimiento' => 'Division de Cuenta']);
    }
}

// vendor/bin/phpunit --filter testIndexCargaMesasYCalculaTotales --no-coverage
// vendor/bin/phpunit --filter testTransferirMesaMueveComandaYRegistraAuditoria --no-coverage
// vendor/bin/phpunit --filter testCancelarItemActualizaDetalleYRegistraAuditoria --no-coverage
// vendor/bin/phpunit --filter testEjecutarDivisionCreaNuevaMesaYRegistraAuditoria --no-coverage