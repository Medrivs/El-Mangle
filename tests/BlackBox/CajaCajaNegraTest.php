<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CajaCajaNegraTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();
        
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Mesa')->where('id_mesa', 8888)->delete();
        $db->table('Usuario')->where('username', 'cajero_cajanegra')->delete();
        $db->table('Comanda')->where('id_comanda', 8888)->delete();
        $db->table('Detalle_Comanda')->where('id_comanda', 8888)->delete();
        
        $db->table('Usuario')->insert([
            'id_usuario'      => 8888,
            'nombre_completo' => 'Cajero Caja Negra',
            'id_rol'          => 5, 
            'username'        => 'cajero_cajanegra',
            'password'        => password_hash('CajaNegra!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        $db->table('Mesa')->insert([
            'id_mesa'     => 8888,
            'numero_mesa' => 8888, 
            'estado_mesa' => 'Por Pagar', 
            'activa'      => 1
        ]);

        $db->table('Comanda')->insert([
            'id_comanda' => 8888,
            'id_mesa'    => 8888, 
            'id_usuario' => 8888, 
            'fecha_hora' => date('Y-m-d H:i:s')
        ]);

        $db->table('Detalle_Comanda')->insert([
            'id_comanda'      => 8888, 
            'id_platillo'     => 1, 
            'cantidad'        => 1, 
            'precio_unitario' => 500, 
            'estado'          => 'Listo'
        ]);
        
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function testRechazoDeMontosNegativos()
    {
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 8888];

        $respuesta = $this->withSession($sesionCajero)->post('caja/liquidar', [
            'id_mesa'        => 8888,
            'monto_efectivo' => -500, 
            'metodo_pago'    => 'efectivo'
        ]);

        $respuesta->assertRedirect(); 
        // Verificamos que el sistema se defendió y la mesa sigue sin cobrarse
        $this->seeInDatabase('Mesa', ['id_mesa' => 8888, 'estado_mesa' => 'Por Pagar']);
    }

    public function testRechazoDeCaracteresInvalidosEnMonto()
    {
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 8888];

        $respuesta = $this->withSession($sesionCajero)->post('caja/liquidar', [
            'id_mesa'        => 8888,
            'monto_efectivo' => 'abc', 
            'metodo_pago'    => 'efectivo'
        ]);

        $respuesta->assertRedirect();
        // Verificamos que el sistema rebotó las letras y la mesa sigue debiendo
        $this->seeInDatabase('Mesa', ['id_mesa' => 8888, 'estado_mesa' => 'Por Pagar']);
    }

    public function testCobroExitosoConDatosValidos()
    {
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 8888];

        $respuesta = $this->withSession($sesionCajero)->post('caja/liquidar', [
            'id_mesa'        => 8888,
            'monto_efectivo' => 500, 
            'metodo_pago'    => 'efectivo'
        ]);

        $respuesta->assertRedirect();
        // El cajero hizo las cosas bien, la mesa se libera exitosamente
        $this->seeInDatabase('Mesa', ['id_mesa' => 8888, 'estado_mesa' => 'Libre']);
    }
}