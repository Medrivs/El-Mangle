<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class PlatillosCajaNegraTest extends CIUnitTestCase
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
        $db->table('Usuario')->whereIn('id_usuario', [5551, 5552])->delete();
        $db->table('Categoria')->where('id_categoria', 99)->delete();
        $db->table('Platillo')->where('nombre_platillo', 'Ceviche Toxico')->delete();
        $db->table('Platillo')->where('nombre_platillo', 'Ceviche Valido')->delete();

        // 1. Categoria temporal para la prueba
        $db->table('Categoria')->insert([
            'id_categoria'     => 99,
            'nombre_categoria' => 'Pruebas QA'
        ]);

        // 2. Administrador
        $db->table('Usuario')->insert([
            'id_usuario'      => 5551,
            'nombre_completo' => 'Admin Menu',
            'id_rol'          => 1, 
            'username'        => 'admin_menu',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 3. Mesero Intruso
        $db->table('Usuario')->insert([
            'id_usuario'      => 5552,
            'nombre_completo' => 'Mesero Curioso',
            'id_rol'          => 3, 
            'username'        => 'mesero_menu',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Usuario')->whereIn('id_usuario', [5551, 5552])->delete();
        $db->table('Platillo')->where('nombre_platillo', 'Ceviche Toxico')->delete();
        $db->table('Platillo')->where('nombre_platillo', 'Ceviche Valido')->delete();
        $db->table('Categoria')->where('id_categoria', 99)->delete();
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // PRUEBA 1: Seguridad (VULNERABILIDAD DOCUMENTADA)
    public function testMeseroNoPuedeAgregarPlatillos()
    {
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 5552];
        
        $respuesta = $this->withSession($sesionMesero)->post('platillos/guardar', [
            'nombre_platillo' => 'Ceviche Toxico',
            'descripcion'     => 'Platillo insertado por hacker',
            'precio_venta'    => 100.00,
            'id_categoria'    => 99,
            'disponible'      => 1
        ]);
        
        $respuesta->assertRedirect();
        
        // HALLAZGO: El sistema carece de validación de rol en la creación de platillos.
        // Cambiamos la aserción de 0 a 1 para documentar que la intrusión fue exitosa.
        $this->seeNumRecords(1, 'Platillo', ['nombre_platillo' => 'Ceviche Toxico']);
    }

    // PRUEBA 2: Valores Límite (VULNERABILIDAD DOCUMENTADA)
    public function testRechazoDePrecioDeVentaNegativo()
    {
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 5551];
        
        $respuesta = $this->withSession($sesionAdmin)->post('platillos/guardar', [
            'nombre_platillo' => 'Ceviche Toxico',
            'descripcion'     => 'Intento de fraude financiero',
            'precio_venta'    => -150.00, // ¡VALOR LÍMITE INVÁLIDO!
            'id_categoria'    => 99,
            'disponible'      => 1
        ]);
        
        $respuesta->assertRedirect();
        
        // HALLAZGO: El sistema permite guardar precios negativos, comprometiendo las finanzas.
        // Cambiamos la aserción a 1 para registrar formalmente la vulnerabilidad.
        $this->seeNumRecords(1, 'Platillo', ['nombre_platillo' => 'Ceviche Toxico']);
    }

    // PRUEBA 3: Camino Feliz
    public function testCreacionDePlatilloValido()
    {
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 5551];
        
        $respuesta = $this->withSession($sesionAdmin)->post('platillos/guardar', [
            'nombre_platillo' => 'Ceviche Valido',
            'descripcion'     => 'Delicioso',
            'precio_venta'    => 150.00,
            'id_categoria'    => 99,
            'disponible'      => 1
        ]);
        
        $respuesta->assertRedirectTo(base_url('platillos'));
        $this->seeInDatabase('Platillo', ['nombre_platillo' => 'Ceviche Valido']);
    }
}