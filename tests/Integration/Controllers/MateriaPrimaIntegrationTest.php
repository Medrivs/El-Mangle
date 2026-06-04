<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class MateriaPrimaIntegrationTest extends CIUnitTestCase
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
        
        $alta = $this->call('post', 'materiaprima/guardar', [
            'nombre_producto'      => 'Pulpo Prueba Nivel 1',
            'stock_actual'         => 10,
            'precio_compra'        => 200.50,
            'unidad_medida'        => 'Kg',
            'stock_minimo'         => 2,
            'fecha_ultima_entrada' => '2026-06-01'
        ]);
        $alta->assertRedirectTo(base_url('materiaprima'));

        $materia = $db->table('Materia_Prima')->where('nombre_producto', 'Pulpo Prueba Nivel 1')->get()->getRowArray();
        $idGenerado = $materia['id_materia_prima'];

        $baja = $this->call('get', "materiaprima/eliminar/$idGenerado");
        $baja->assertRedirectTo(base_url('materiaprima'));

        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $idGenerado, 'estado_materia' => 0]);
    }

    // NIVEL 2: INTEGRACIÓN DE AGREGAR + ACTUALIZAR + ELIMINAR
    public function testIntegracionAgregarActualizarYEliminar()
    {
        $db = \Config\Database::connect();
        
        $this->call('post', 'materiaprima/guardar', [
            'nombre_producto'      => 'Camaron Prueba Nivel 2',
            'stock_actual'         => 5,
            'precio_compra'        => 150.00,
            'unidad_medida'        => 'Kg',
            'stock_minimo'         => 2,
            'fecha_ultima_entrada' => '2026-06-01'
        ]);
        
        $materia = $db->table('Materia_Prima')->where('nombre_producto', 'Camaron Prueba Nivel 2')->get()->getRowArray();
        $idGenerado = $materia['id_materia_prima'];

        $edicion = $this->call('post', "materiaprima/actualizar/$idGenerado", [
            'nombre_producto'      => 'Camaron Prueba Nivel 2',
            'stock_actual'         => 15, // Actualizamos el stock (Abastecimiento)
            'precio_compra'        => 155.00, // Cambio de precio
            'unidad_medida'        => 'Kg',
            'stock_minimo'         => 2,
            'fecha_ultima_entrada' => '2026-06-02',
            'estado_materia'       => 'on'
        ]);
        $edicion->assertRedirectTo(base_url('materiaprima'));
        
        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $idGenerado, 'stock_actual' => 15]);

        $this->call('get', "materiaprima/eliminar/$idGenerado");
        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $idGenerado, 'estado_materia' => 0]);
    }

    // NIVEL 3: INTEGRACIÓN TOTAL (AGREGAR + LEER + ACTUALIZAR + ELIMINAR)
    public function testIntegracionTotalDelModuloMateriaPrima()
    {
        $db = \Config\Database::connect();
        
        $this->call('post', 'materiaprima/guardar', [
            'nombre_producto'      => 'Salmon Prueba Nivel 3',
            'stock_actual'         => 8,
            'precio_compra'        => 300.00,
            'unidad_medida'        => 'Kg',
            'stock_minimo'         => 3,
            'fecha_ultima_entrada' => '2026-06-01'
        ]);
        
        $materia = $db->table('Materia_Prima')->where('nombre_producto', 'Salmon Prueba Nivel 3')->get()->getRowArray();
        $idGenerado = $materia['id_materia_prima'];

        $pantalla = $this->call('get', 'materiaprima');
        $pantalla->assertSee('Salmon Prueba Nivel 3');
        $pantalla->assertSee('300.00');

        $this->call('post', "materiaprima/actualizar/$idGenerado", [
            'nombre_producto'      => 'Salmon Prueba Nivel 3 (Fresco)',
            'stock_actual'         => 8,
            'precio_compra'        => 320.00,
            'unidad_medida'        => 'Kg',
            'stock_minimo'         => 3,
            'fecha_ultima_entrada' => '2026-06-03',
            'estado_materia'       => 'on'
        ]);

        $this->call('get', "materiaprima/eliminar/$idGenerado");
        
        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $idGenerado, 
            'nombre_producto'  => 'Salmon Prueba Nivel 3 (Fresco)',
            'estado_materia'   => 0
        ]);
    }
}

// vendor/bin/phpunit --filter testIntegracionAgregarYEliminar tests/Integration/Controllers/MateriaPrimaIntegrationTest.php --no-coverage
// vendor/bin/phpunit --filter testIntegracionAgregarActualizarYEliminar tests/Integration/Controllers/MateriaPrimaIntegrationTest.php --no-coverage
// vendor/bin/phpunit --filter testIntegracionTotalDelModuloMateriaPrima tests/Integration/Controllers/MateriaPrimaIntegrationTest.php --no-coverage
