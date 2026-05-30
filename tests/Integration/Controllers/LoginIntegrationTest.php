<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class LoginIntegrationTest extends CIUnitTestCase
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

    // Inserta un usuario, simula el tecleo de su PIN y verifica que entre a la pantalla correcta
    public function testIngresarValidaPINYCreaSesion()
    {
        $db = \Config\Database::connect();
        
        // Creamos un PIN unico para este test, asegurándonos de que no colisione con otros usuarios 
        $pinUnico = '9999';
        
        // Creamos un Cajero (Rol 5) activo con contraseña encriptada real
        $db->table('Usuario')->insert([
            'nombre_completo' => 'Cajero Test',
            'id_rol'          => 5,
            'username'        => 'caja_test',
            'password'        => password_hash($pinUnico, PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // Simulamos que presiona el botón verde de "Ingresar" con el PIN único
        $resultado = $this->post('login/ingresar', [
            'pin' => $pinUnico
        ]);

        // Verificamos que CodeIgniter haya mapeado el rol 5 a la url 'caja'
        $resultado->assertRedirectTo(base_url('caja'));
        
        // Verificamos que la sesión se haya levantado en memoria
        $resultado->assertSessionHas('isLoggedIn', true);
        $resultado->assertSessionHas('id_rol', '5');
    }

    // Intenta entrar con una contraseña falsa y asegura que te devuelva a la pantalla principal
    public function testIngresarRechazaPINInvalidoOCuentaInactiva()
    {
        // Enviamos un PIN que sabemos que nadie tiene
        $resultado = $this->post('login/ingresar', [
            'pin' => '99999999'
        ]);

        // Nos debe rebotar al inicio (pantalla de login)
        $resultado->assertRedirectTo(base_url('/'));
        
        // Debe llevar consigo el mensaje rojo de error
        $resultado->assertSessionHas('error', 'PIN incorrecto o no autorizado.');
    }

    // Simula tener una sesión abierta, entra a la ruta de salir y comprueba que se destruya todo
    public function testLogoutDestruyeSesionYRedirige()
    {
        // Inyectamos una sesión como si ya estuviéramos dentro de El Mangle
        $resultado = $this->withSession([
            'isLoggedIn' => true,
            'nombre'     => 'Mesero Aburrido'
        ])->get('logout'); // Disparamos la ruta oficial de salida

        $resultado->assertRedirectTo(base_url('/'));
        
        // Validamos el mensaje de éxito de salida
        $resultado->assertSessionHas('success', 'Sesion cerrada de forma segura.');
    }
}
// vendor/bin/phpunit --filter testLogoutDestruyeSesionYRedirige --no-coverage
// vendor/bin/phpunit --filter testIngresarValidaPINYCreaSesion --no-coverage
// vendor/bin/phpunit --filter testIngresarRechazaPINInvalidoOCuenta    