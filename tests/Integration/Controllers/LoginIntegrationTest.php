<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class LoginIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // nivel 1 integracion de login retencion de sesion y cierre seguro
    public function testIntegracionLoginMemoriaYLogout()
    {
        $db = \Config\Database::connect();
        
        // prepara un usuario administrador activo
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Admin']);
        $db->table('Usuario')->insert([
            'nombre_completo' => 'admin login test',
            'id_rol'          => 1,
            'username'        => 'admin_test',
            'password'        => '9999',
            'estado_usuario'  => 1
        ]);

        // inicia la sesion y guarda los datos generados
        $login = $this->post('login/ingresar', ['pin' => '9999']);
        $login->assertRedirectTo(base_url('usuarios'));
        $login->assertSessionHas('isLoggedIn', true);

        // rescata la sesion real para simular que es el mismo navegador
        $sesionViva = session()->get();

        // comprueba que la sesion sigue viva apuntando a la raiz
        $index = $this->withSession($sesionViva)->get('/');
        $index->assertRedirectTo(base_url('usuarios'));

        // comprueba que el controlador finalizo el proceso mandando el mensaje de exito
        $logout = $this->withSession($sesionViva)->get('login/logout');
        $logout->assertRedirectTo(base_url('/'));
        $logout->assertSessionHas('success'); 
    }

    // nivel 2 integracion total ciclo de vida y bloqueo de credenciales
    public function testIntegracionTotalDeSeguridadYBloqueo()
    {
        $db = \Config\Database::connect();
        
        // inserta un usuario funcional
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 5, 'nombre_rol' => 'Caja']);
        $db->table('Usuario')->insert([
            'nombre_completo' => 'cajero login test',
            'id_rol'          => 5,
            'username'        => 'caja_test',
            'password'        => '7777',
            'estado_usuario'  => 1
        ]);
        $id_usuario = $db->insertID();

        // entra al sistema normalmente
        $login = $this->post('login/ingresar', ['pin' => '7777']);
        $login->assertRedirectTo(base_url('caja'));

        // el administrador lo da de baja logica
        $db->table('Usuario')->where('id_usuario', $id_usuario)->update(['estado_usuario' => 0]);

        // intenta reingresar pero el metodo detiene el flujo
        $reingreso = $this->post('login/ingresar', ['pin' => '7777']);
        
        // verifica que se reboto el acceso y mando alerta
        $reingreso->assertRedirectTo(base_url('/'));
        $reingreso->assertSessionHas('error');
    }
}

//vendor/bin/phpunit --filter testIntegracionLoginMemoriaYLogout tests/Integration/Controllers/LoginIntegrationTest.php --no-coverage
//vendor/bin/phpunit --filter testIntegracionTotalDeSeguridadYBloqueo tests/Integration/Controllers/LoginIntegrationTest.php --no-coverage