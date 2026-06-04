<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class PlatillosIntegrationTest extends CIUnitTestCase
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
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 100, 'nombre_categoria' => 'Test Cat']);
        
        $alta = $this->call('post', 'platillos/guardar', [
            'nombre_platillo' => 'Platillo Nivel Uno',
            'descripcion'     => 'Desc Nivel 1',
            'precio_venta'    => 100.00,
            'id_categoria'    => 100
        ]);
        $alta->assertRedirectTo(base_url('platillos'));

        $platillo = $db->table('Platillo')->where('nombre_platillo', 'Platillo Nivel Uno')->get()->getRowArray();
        $idGenerado = $platillo['id_platillo'];

        $baja = $this->call('get', "platillos/eliminar/$idGenerado");
        $baja->assertRedirectTo(base_url('platillos'));

        $this->seeInDatabase('Platillo', ['id_platillo' => $idGenerado, 'disponible' => 0]);
    }

    // NIVEL 2: INTEGRACIÓN DE AGREGAR + ACTUALIZAR + ELIMINAR
    public function testIntegracionAgregarActualizarYEliminar()
    {
        $db = \Config\Database::connect();
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 100, 'nombre_categoria' => 'Test Cat']);
        
        $this->call('post', 'platillos/guardar', [
            'nombre_platillo' => 'Platillo Nivel Dos',
            'descripcion'     => 'Desc Nivel 2',
            'precio_venta'    => 150.00,
            'id_categoria'    => 100
        ]);
        
        $platillo = $db->table('Platillo')->where('nombre_platillo', 'Platillo Nivel Dos')->get()->getRowArray();
        $idGenerado = $platillo['id_platillo'];

        $edicion = $this->call('post', "platillos/actualizar/$idGenerado", [
            'nombre_platillo' => 'Platillo Nivel Dos',
            'descripcion'     => 'Desc Editada',
            'precio_venta'    => 180.00,
            'id_categoria'    => 100,
            'disponible'      => 'on'
        ]);
        $edicion->assertRedirectTo(base_url('platillos'));
        
        $this->seeInDatabase('Platillo', ['id_platillo' => $idGenerado, 'precio_venta' => 180.00]);

        $this->call('get', "platillos/eliminar/$idGenerado");
        $this->seeInDatabase('Platillo', ['id_platillo' => $idGenerado, 'disponible' => 0]);
    }

    // NIVEL 3: INTEGRACIÓN TOTAL (AGREGAR + LEER + ACTUALIZAR + ELIMINAR)
    public function testIntegracionTotalDelModuloPlatillos()
    {
        $db = \Config\Database::connect();
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 100, 'nombre_categoria' => 'Test Cat']);
        
        $this->call('post', 'platillos/guardar', [
            'nombre_platillo' => 'Platillo Nivel Tres',
            'descripcion'     => 'Desc Nivel 3',
            'precio_venta'    => 200.00,
            'id_categoria'    => 100
        ]);
        
        $platillo = $db->table('Platillo')->where('nombre_platillo', 'Platillo Nivel Tres')->get()->getRowArray();
        $idGenerado = $platillo['id_platillo'];

        $pantalla = $this->call('get', 'platillos');
        $pantalla->assertSee('Platillo Nivel Tres');
        $pantalla->assertSee('200.00');

        $this->call('post', "platillos/actualizar/$idGenerado", [
            'nombre_platillo' => 'Platillo Nivel Tres (Editado)',
            'descripcion'     => 'Desc Nueva',
            'precio_venta'    => 250.00,
            'id_categoria'    => 100,
            'disponible'      => 'on'
        ]);

        $this->call('get', "platillos/eliminar/$idGenerado");
        
        $this->seeInDatabase('Platillo', [
            'id_platillo'     => $idGenerado, 
            'nombre_platillo' => 'Platillo Nivel Tres (Editado)',
            'disponible'      => 0
        ]);
    }
}

//vendor/bin/phpunit --filter testIntegracionAgregarYEliminar tests/Integration/Controllers/PlatillosIntegrationTest.php --no-coverage

// Nivel 2: vendor/bin/phpunit --filter testIntegracionAgregarActualizarYEliminar tests/Integration/Controllers/PlatillosIntegrationTest.php --no-coverage

// Nivel 3 (Total): vendor/bin/phpunit --filter testIntegracionTotalDelModuloPlatillos tests/Integration/Controllers/PlatillosIntegrationTest.php --no-coverage

// Todo el archivo junto: vendor/bin/phpunit tests/Integration/Controllers/PlatillosIntegrationTest.php --no-coverage
