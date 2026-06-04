<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class UsuariosIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // NIVEL 1: INTEGRACIÓN DE AGREGAR + ELIMINAR

    public function testIntegracionAgregarYEliminar()
    {
        $db = \Config\Database::connect();
        
        $alta = $this->call('post', 'usuarios/guardar', [
            'nombre_completo' => 'Empleado Nivel Uno',
            'id_rol'          => 3,
            'username'        => 'emp_nivel1',
            'password'        => '1234',
            'telefono'        => '1111111111',
            'estado_usuario'  => 'on'
        ]);
        $alta->assertRedirectTo(base_url('usuarios'));

        $usuario = $db->table('Usuario')->where('username', 'emp_nivel1')->get()->getRowArray();
        $idGenerado = $usuario['id_usuario'];

        $baja = $this->call('get', "usuarios/eliminar/$idGenerado");
        $baja->assertRedirectTo(base_url('usuarios'));

        $this->seeInDatabase('Usuario', ['id_usuario' => $idGenerado, 'estado_usuario' => 0]);
    }

    // NIVEL 2: INTEGRACIÓN DE AGREGAR + ACTUALIZAR + ELIMINAR
    public function testIntegracionAgregarActualizarYEliminar()
    {
        $db = \Config\Database::connect();
        
        $this->call('post', 'usuarios/guardar', [
            'nombre_completo' => 'Empleado Nivel Dos',
            'id_rol'          => 3,
            'username'        => 'emp_nivel2',
            'password'        => '1234',
            'telefono'        => '2222222222',
            'estado_usuario'  => 'on'
        ]);
        
        $usuario = $db->table('Usuario')->where('username', 'emp_nivel2')->get()->getRowArray();
        $idGenerado = $usuario['id_usuario'];

        $edicion = $this->call('post', "usuarios/actualizar/$idGenerado", [
            'nombre_completo' => 'Empleado Nivel Dos (Editado)',
            'id_rol'          => 3,
            'username'        => 'emp_nivel2',
            'telefono'        => '9999999999',
            'estado_usuario'  => 'on'
        ]);
        $edicion->assertRedirectTo(base_url('usuarios'));
        
        $this->seeInDatabase('Usuario', ['id_usuario' => $idGenerado, 'telefono' => '9999999999']);

        $this->call('get', "usuarios/eliminar/$idGenerado");
        $this->seeInDatabase('Usuario', ['id_usuario' => $idGenerado, 'estado_usuario' => 0]);
    }

    // NIVEL 3: INTEGRACIÓN TOTAL (AGREGAR + LEER + ACTUALIZAR + ELIMINAR)
    public function testIntegracionTotalDelModuloUsuarios()
    {
        $db = \Config\Database::connect();
        
        $this->call('post', 'usuarios/guardar', [
            'nombre_completo' => 'Empleado Nivel Tres',
            'id_rol'          => 5,
            'username'        => 'emp_nivel3',
            'password'        => '1234',
            'telefono'        => '3333333333',
            'estado_usuario'  => 'on'
        ]);
        
        $usuario = $db->table('Usuario')->where('username', 'emp_nivel3')->get()->getRowArray();
        $idGenerado = $usuario['id_usuario'];

        $pantalla = $this->call('get', 'usuarios');
        $pantalla->assertSee('Empleado Nivel Tres');

        $this->call('post', "usuarios/actualizar/$idGenerado", [
            'nombre_completo' => 'Empleado Nivel Tres (Ascendido)',
            'id_rol'          => 1, 
            'username'        => 'emp_nivel3',
            'telefono'        => '3333333333',
            'estado_usuario'  => 'on'
        ]);

        $this->call('get', "usuarios/eliminar/$idGenerado");
        
        $this->seeInDatabase('Usuario', [
            'id_usuario'      => $idGenerado, 
            'nombre_completo' => 'Empleado Nivel Tres (Ascendido)',
            'estado_usuario'  => 0
        ]);
    }
}

//vendor/bin/phpunit --filter testIntegracionAgregarYEliminar --no-coverage
//vendor/bin/phpunit --filter testIntegracionAgregarActualizarYEliminar --no-coverage
//vendor/bin/phpunit --filter testIntegracionTotalDelModuloUsuarios --no-coverage