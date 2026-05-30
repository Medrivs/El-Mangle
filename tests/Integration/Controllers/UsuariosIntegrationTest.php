<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class UsuariosIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; // Fuerza a usar la conexión de MySQL Workbench
    protected $migrate     = false;     // Apaga las migraciones de CI4
    protected $migrateOnce = false;     // Evita que busque archivos de migración
    protected $refresh     = false;     // Evita que intente borrar y recrear tu base de datos

    // Simula entrar a la pantalla de usuarios y verifica que muestre los datos que están en MySQL
    public function testVistaIndexCargaUsuariosDesdeBD()
    {
        $db = \Config\Database::connect('default');
        $db->table('Usuario')->insert([
            'nombre_completo' => 'Admin Visual',
            'id_rol'          => 1,
            'username'        => 'admin_vista',
            'password'        => '1234',
            'estado_usuario'  => 1
        ]);

        $resultado = $this->call('get', 'usuarios');

        $resultado->assertOK();
        $resultado->assertSee('Admin Visual');
    }

    // Simula enviar el formulario de "Agregar" y comprueba que el registro aparezca físicamente en la BD
    public function testGuardarNuevoUsuarioEnBaseDeDatosReal()
    {
        $datos = [
            'nombre_completo' => 'Cajero Nuevo',
            'id_rol'          => 5,
            'username'        => 'cajero_integ',
            'password'        => '1234',
            'telefono'        => '4771112233',
            'fecha_ingreso'   => '2026-05-29',
            'estado_usuario'  => 'on'
        ];

        $resultado = $this->call('post', 'usuarios/guardar', $datos);

        $resultado->assertRedirectTo(base_url('usuarios'));
        $this->seeInDatabase('Usuario', [
            'nombre_completo' => 'Cajero Nuevo',
            'username'        => 'cajero_integ',
            'telefono'        => '4771112233',
            'estado_usuario'  => 1
        ]);
    }

    // Crea un usuario temporal, simula que lo editamos y verifica que sus datos cambien en MySQL
    public function testActualizarUsuarioModificaLaBaseDeDatos()
    {
        $db = \Config\Database::connect();
        $db->table('Usuario')->insert([
            'nombre_completo' => 'Mesero Viejo',
            'id_rol'          => 3,
            'username'        => 'mesero_v',
            'password'        => '0000',
            'telefono'        => '000',
            'estado_usuario'  => 1
        ]);
        $idInsertado = $db->insertID();

        $datosActualizados = [
            'nombre_completo' => 'Mesero Renombrado',
            'id_rol'          => 3,
            'username'        => 'mesero_v',
            'telefono'        => '111',
            'estado_usuario'  => 'on'
        ];

        $resultado = $this->call('post', 'usuarios/actualizar/' . $idInsertado, $datosActualizados);

        $resultado->assertRedirectTo(base_url('usuarios'));
        $this->seeInDatabase('Usuario', [
            'id_usuario'      => $idInsertado,
            'nombre_completo' => 'Mesero Renombrado',
            'telefono'        => '111'
        ]);
    }

    // Simula presionar el botón eliminar y asegura que MySQL cambie el estado a 0 (baja lógica) en lugar de borrarlo
    public function testEliminarAplicaBorradoLogicoEnBD()
    {
        $db = \Config\Database::connect();
        $db->table('Usuario')->insert([
            'nombre_completo' => 'Usuario A Borrar',
            'id_rol'          => 2,
            'username'        => 'borrar_test',
            'password'        => '111',
            'estado_usuario'  => 1
        ]);
        $idInsertado = $db->insertID();

        $resultado = $this->call('get', 'usuarios/eliminar/' . $idInsertado);

        $resultado->assertRedirectTo(base_url('usuarios'));
        $this->seeInDatabase('Usuario', [
            'id_usuario'     => $idInsertado,
            'estado_usuario' => 0
        ]);
    }
}




// vendor/bin/phpunit --filter testVistaIndexCargaUsuariosDesdeBD --no-coverage
// vendor/bin/phpunit --filter testGuardarNuevoUsuarioEnBaseDeDatosReal --no-coverage
// vendor/bin/phpunit --filter testActualizarUsuarioModificaLaBaseDeDatos --no-coverage
// vendor/bin/phpunit --filter testEliminarAplicaBorradoLogicoEnBD --no-coverage