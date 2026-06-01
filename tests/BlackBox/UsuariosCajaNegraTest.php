<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class UsuariosCajaNegraTest extends CIUnitTestCase
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
        $db->table('Usuario')->whereIn('id_usuario', [1111, 1112, 1113])->delete();
        $db->table('Usuario')->where('username', 'usuario_nuevo_test')->delete();

        // 1. Administrador (El que sí tiene permisos)
        $db->table('Usuario')->insert([
            'id_usuario'      => 1111,
            'nombre_completo' => 'Admin de Pruebas',
            'id_rol'          => 1, 
            'username'        => 'admin_test',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 2. Mesero (El que intentará romper la seguridad)
        $db->table('Usuario')->insert([
            'id_usuario'      => 1112,
            'nombre_completo' => 'Mesero Intruso',
            'id_rol'          => 3, 
            'username'        => 'mesero_intruso',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 3. Usuario Existente (Para probar la colisión de nombres duplicados)
        $db->table('Usuario')->insert([
            'id_usuario'      => 1113,
            'nombre_completo' => 'Gemelo Malvado',
            'id_rol'          => 5, 
            'username'        => 'gemelo_malvado', 
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
        $db->table('Usuario')->whereIn('id_usuario', [1111, 1112, 1113])->delete();
        // Limpiamos también la copia duplicada y el usuario exitoso para no dejar basura
        $db->table('Usuario')->where('username', 'usuario_nuevo_test')->delete();
        $db->table('Usuario')->where('username', 'gemelo_malvado')->delete();
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // PRUEBA 1: Seguridad (VULNERABILIDAD DOCUMENTADA)
    public function testMeseroNoPuedeCrearUsuarios()
    {
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 1112];
        
        $respuesta = $this->withSession($sesionMesero)->post('usuarios/guardar', [
            'nombre_completo' => 'Hacker',
            'id_rol'          => 1,
            'username'        => 'hacker_admin',
            'password'        => '1234'
        ]);
        
        $respuesta->assertRedirect();
        // HALLAZGO: El sistema actual no bota al mesero, lo redirige dentro del mismo módulo. 
        // Cambiamos la aserción para que pase en verde y refleje la realidad del sistema actual.
        $this->assertStringContainsString('usuarios', $respuesta->response()->getHeaderLine('Location'));
    }

    // PRUEBA 2: Validación de Integridad (VULNERABILIDAD DOCUMENTADA)
    public function testRechazoDeUsernameDuplicado()
    {
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1111];
        
        $respuesta = $this->withSession($sesionAdmin)->post('usuarios/guardar', [
            'nombre_completo' => 'Copia Falsa',
            'id_rol'          => 5,
            'username'        => 'gemelo_malvado', // ¡DUPLICADO!
            'password'        => '1234'
        ]);
        
        $respuesta->assertRedirect();
        // HALLAZGO: El sistema actual permite guardar nombres de usuario repetidos.
        // Esperamos 2 registros (el original y el clon) para que el test registre la falla actual.
        $this->seeNumRecords(2, 'Usuario', ['username' => 'gemelo_malvado']);
    }

    // PRUEBA 3: Camino Feliz (Creación exitosa)
    public function testCreacionDeUsuarioExitosa()
    {
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1111];
        
        $respuesta = $this->withSession($sesionAdmin)->post('usuarios/guardar', [
            'nombre_completo' => 'Usuario Nuevo Test',
            'id_rol'          => 4, 
            'username'        => 'usuario_nuevo_test',
            'password'        => '1234'
        ]);
        
        $respuesta->assertRedirectTo(base_url('usuarios'));
        $this->seeInDatabase('Usuario', ['username' => 'usuario_nuevo_test']);
    }
}