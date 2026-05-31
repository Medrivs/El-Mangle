<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class SeguridadSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    // ===================================================================
    // ⚙️ CONFIGURACIÓN DE BASE DE DATOS
    // ===================================================================
    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     
    // ===================================================================

    public function testCicloDeVidaDeAccesosYSeguridad()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: LIMPIEZA QUIRÚRGICA Y PREPARACIÓN DE SEGURIDAD
        // ===================================================================
        // Solo eliminamos al empleado de prueba por si se quedó atascado en una corrida anterior
        $db->table('Usuario')->where('username', 'mesero_nuevo')->delete();
        
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Administrador']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);

        // Blindaje para el Admin: Si ya existe el usuario 1, solo actualizamos su estado y contraseña para este test
        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (1, 'Admin Maestro', 1, 'admin_maestro', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // ===================================================================
        // FASE 1: ALTA DE USUARIO (El Admin contrata a alguien)
        // ===================================================================
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1];

        // El admin llena el formulario de "Agregar Usuario"
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

        // Rescatamos los datos del usuario recién creado en MySQL
        $nuevoUsuario = $db->table('Usuario')->where('username', 'mesero_nuevo')->get()->getRowArray();
        $this->assertNotNull($nuevoUsuario, 'El nuevo empleado no se guardó en la base de datos.');

        // ===================================================================
        // FASE 2: CONTROL DE ROLES (El nuevo empleado inicia sesión)
        // ===================================================================
        // Mandamos el PIN al controlador general de Login
        $loginNuevo = $this->post('login/ingresar', [
            'pin' => '8888' 
        ]);

        // VERIFICACIÓN CRÍTICA: Como es Rol 3 (Mesero), el sistema de seguridad DEBE mandarlo al POS, no a caja ni a admin.
        $loginNuevo->assertRedirectTo(base_url('pos'));
        $loginNuevo->assertSessionHas('isLoggedIn', true);

        // ===================================================================
        // FASE 3: BAJA DE USUARIO (El Admin despide al empleado)
        // ===================================================================
        // Simulamos el clic del administrador en el botón rojo de "Eliminar"
        $baja = $this->withSession($sesionAdmin)->get("usuarios/eliminar/" . $nuevoUsuario['id_usuario']);
        $baja->assertRedirectTo(base_url('usuarios'));

        // Verificamos que el borrado lógico haya apagado el interruptor (estado = 0)
        $this->seeInDatabase('Usuario', [
            'id_usuario'     => $nuevoUsuario['id_usuario'],
            'estado_usuario' => 0
        ]);

        // ===================================================================
        // FASE 4: BLOQUEO DE ACCESO (El exempleado intenta entrar)
        // ===================================================================
        // El empleado intenta usar su PIN al día siguiente
        $loginDenegado = $this->post('login/ingresar', [
            'pin' => '8888'
        ]);

        // El cadenero del sistema (Login) debe rebotarlo a la pantalla de inicio
        $loginDenegado->assertRedirectTo(base_url('/'));
        
        // Verificamos que se haya generado el mensaje de rechazo
        $loginDenegado->assertSessionHas('error'); 
    }
}