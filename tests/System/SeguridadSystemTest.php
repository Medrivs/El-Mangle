<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class SeguridadSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // prueba del ciclo de vida de accesos y seguridad
    public function testSeguridadCicloDeVidaAccesos()
    {
        $db = \Config\Database::connect();
        
        // fase 0 limpieza quirurgica y preparacion de seguridad
        // solo eliminamos al empleado de prueba por si se quedo atascado
        $db->table('Usuario')->where('username', 'mesero_nuevo')->delete();
        
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Administrador']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);

        // blindaje para el admin si ya existe el usuario 1 solo actualizamos su estado y contrasena
        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (1, 'Admin Maestro', 1, 'admin_maestro', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // fase 1 alta de usuario el admin contrata a alguien
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1];

        // el admin llena el formulario de agregar usuario
        $datosNuevoUsuario = [
            'nombre_completo' => 'Mesero Contratado',
            'id_rol'          => 3,
            'username'        => 'mesero_nuevo',
            'password'        => '8888', 
            'telefono'        => '4771234567',
            'estado_usuario'  => 1 
        ];

        $creacion = $this->withSession($sesionAdmin)->post('usuarios/guardar', $datosNuevoUsuario);
        $creacion->assertRedirectTo(base_url('usuarios'));

        // rescatamos los datos del usuario recien creado en mysql
        $nuevoUsuario = $db->table('Usuario')->where('username', 'mesero_nuevo')->get()->getRowArray();
        $this->assertNotNull($nuevoUsuario);

        // fase 2 control de roles el nuevo empleado inicia sesion
        // mandamos el pin al controlador general de login
        $loginNuevo = $this->post('login/ingresar', [
            'pin' => '8888' 
        ]);

        // verificacion critica como es rol 3 el sistema debe mandarlo al pos
        $loginNuevo->assertRedirectTo(base_url('pos'));
        $loginNuevo->assertSessionHas('isLoggedIn', true);

        // fase 3 baja de usuario el admin despide al empleado
        // simulamos el clic del administrador en el boton rojo de eliminar
        $baja = $this->withSession($sesionAdmin)->get("usuarios/eliminar/" . $nuevoUsuario['id_usuario']);
        $baja->assertRedirectTo(base_url('usuarios'));

        // verificamos que el borrado logico haya apagado el interruptor
        $this->seeInDatabase('Usuario', [
            'id_usuario'     => $nuevoUsuario['id_usuario'],
            'estado_usuario' => 0
        ]);

        // fase 4 bloqueo de acceso el exempleado intenta entrar
        // el empleado intenta usar su pin al dia siguiente
        $loginDenegado = $this->post('login/ingresar', [
            'pin' => '8888'
        ]);

        // el cadenero del sistema debe rebotarlo a la pantalla de inicio
        $loginDenegado->assertRedirectTo(base_url('/'));
        
        // verificamos que se haya generado el mensaje de rechazo
        $loginDenegado->assertSessionHas('error'); 
    }
}