<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class LoginCajaNegraTest extends CIUnitTestCase
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
        
        // Destruimos cualquier rastro de este usuario para no contaminar la base de datos
        $db->table('Usuario')->where('username', 'user_caja_negra')->delete();
        
        // Lo creamos con una contraseña única imposible de confundir
        $db->table('Usuario')->insert([
            'id_usuario'      => 9999,
            'nombre_completo' => 'Usuario Caja Negra',
            'id_rol'          => 3, // Mesero
            'username'        => 'user_caja_negra',
            'password'        => password_hash('CajaNegra2026!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);
    }

    public function testInputVacioMuestraError()
    {
        $respuesta = $this->post('login/ingresar', [
            'pin' => ''
        ]);
        $respuesta->assertRedirectTo(base_url('/'));
        $respuesta->assertSessionHas('error');
    }

    public function testInputConCaracteresEspecialesOInyeccionSQLEsRechazado()
    {
        $respuesta = $this->post('login/ingresar', [
            'pin' => "' OR '1'='1" 
        ]);
        $respuesta->assertRedirectTo(base_url('/'));
        $respuesta->assertSessionHas('error');
    }

    public function testInputExtremadamenteLargoEsManejadoCorrectamente()
    {
        $respuesta = $this->post('login/ingresar', [
            'pin' => str_repeat('9', 1000) 
        ]);
        $respuesta->assertRedirectTo(base_url('/'));
        $respuesta->assertSessionHas('error');
    }

    public function testInputValidoPermiteElAcceso()
    {
        $respuesta = $this->post('login/ingresar', [
            'pin' => 'CajaNegra2026!' // Usamos el PIN ultra seguro
        ]);

        $respuesta->assertRedirectTo(base_url('pos'));
        $respuesta->assertSessionHas('isLoggedIn', true);
    }
}