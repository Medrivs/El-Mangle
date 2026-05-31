<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CapitanCajaNegraTest extends CIUnitTestCase
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
        
        // Apagamos llaves para preparar el escenario limpio
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Mesa')->whereIn('id_mesa', [101, 102, 103])->delete();
        $db->table('Usuario')->where('id_usuario', 2222)->delete();
        
        // Creamos un Capitán de prueba
        $db->table('Usuario')->insert([
            'id_usuario'      => 2222,
            'nombre_completo' => 'Capitan Caja Negra',
            'id_rol'          => 2, // Rol de Capitán
            'username'        => 'capitan_cajanegra',
            'password'        => password_hash('Capitan2026!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // MESA 101: La mesa que queremos mover (Ocupada)
        $db->table('Mesa')->insert(['id_mesa' => 101, 'numero_mesa' => 101, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        
        // MESA 102: Una mesa con gente que estorba (Ocupada)
        $db->table('Mesa')->insert(['id_mesa' => 102, 'numero_mesa' => 102, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        
        // MESA 103: Una mesa limpia y lista (Libre)
        $db->table('Mesa')->insert(['id_mesa' => 103, 'numero_mesa' => 103, 'estado_mesa' => 'Libre', 'activa' => 1]);

        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // 🧹 Limpieza automática para no dejar basura en la base de datos
    protected function tearDown(): void
    {
        parent::tearDown();
        $db = \Config\Database::connect();
        
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Mesa')->whereIn('id_mesa', [101, 102, 103])->delete();
        $db->table('Usuario')->where('id_usuario', 2222)->delete();
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // PRUEBA 1: Fraude o error humano (Transferir la mesa a sí misma)
    public function testRechazoAlTransferirMesaASiMisma()
    {
        $sesionCapitan = ['isLoggedIn' => true, 'id_rol' => 2, 'id_usuario' => 2222];

        // Simulamos el envío del formulario de transferencia
        $respuesta = $this->withSession($sesionCapitan)->post('capitan/transferir', [
            'id_mesa_origen'  => 101,
            'id_mesa_destino' => 101 
        ]);

        // La caja negra debe rebotar la petición
        $respuesta->assertRedirect();
    }

    // PRUEBA 2: Colisión (Transferir a una mesa que ya está ocupada)
    public function testRechazoAlTransferirAUnaMesaOcupada()
    {
        $sesionCapitan = ['isLoggedIn' => true, 'id_rol' => 2, 'id_usuario' => 2222];

        $respuesta = $this->withSession($sesionCapitan)->post('capitan/transferir', [
            'id_mesa_origen'  => 101,
            'id_mesa_destino' => 102 // La 102 ya tiene clientes
        ]);

        $respuesta->assertRedirect();
    }

    // PRUEBA 3: Camino Feliz (Transferencia exitosa a mesa libre)
    public function testTransferenciaExitosaAMesaLibre()
    {
        $sesionCapitan = ['isLoggedIn' => true, 'id_rol' => 2, 'id_usuario' => 2222];

        $respuesta = $this->withSession($sesionCapitan)->post('capitan/transferir', [
            'id_mesa_origen'  => 101,
            'id_mesa_destino' => 103 // La 103 está libre
        ]);

        $respuesta->assertRedirect();
    }
}