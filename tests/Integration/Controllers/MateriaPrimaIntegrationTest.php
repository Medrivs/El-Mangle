<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class MateriaPrimaIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    public function testVistaInventarioMuestraDatosReales()
    {
        $db = \Config\Database::connect();
        $db->table('Materia_Prima')->insert([
            'nombre_producto' => 'Pulpo Congelado',
            'stock_actual'    => 10.5,
            'estado_materia'  => 1
        ]);

        $resultado = $this->call('get', 'materiaprima');

        $resultado->assertOK();
        $resultado->assertSee('Pulpo Congelado');
        $resultado->assertSee('10.5');
    }

    public function testGuardarNuevoIngredienteEnBD()
    {
        $datos = [
            'nombre_producto'      => 'Camarón Pacotilla',
            'stock_actual'         => 20,
            'precio_compra'        => 250.50,
            'unidad_medida'        => 'Kg',
            'stock_minimo'         => 5,
            'fecha_ultima_entrada' => '2026-05-29'
        ];

        $_SESSION['id_usuario'] = 1; // Simulamos sesión activa

        $resultado = $this->call('post', 'materiaprima/guardar', $datos);

        $resultado->assertRedirectTo(base_url('materiaprima'));
        $this->seeInDatabase('Materia_Prima', [
            'nombre_producto' => 'Camarón Pacotilla',
            'unidad_medida'   => 'Kg',
            'estado_materia'  => 1
        ]);
    }

    public function testActualizarStockDeIngredienteEnBD()
    {
        $db = \Config\Database::connect();
        $db->table('Materia_Prima')->insert([
            'nombre_producto' => 'Limón sin semilla',
            'stock_actual'    => 2,
            'unidad_medida'   => 'Kg',
            'estado_materia'  => 1
        ]);
        $idInsertado = $db->insertID();

        $datosActualizados = [
            'nombre_producto' => 'Limón sin semilla',
            'stock_actual'    => 15, // Aumentamos el stock
            'unidad_medida'   => 'Kg',
            'estado_materia'  => 'on'
        ];

        $resultado = $this->call('post', 'materiaprima/actualizar/' . $idInsertado, $datosActualizados);

        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $idInsertado,
            'stock_actual'     => 15
        ]);
    }

    public function testEliminarIngredienteAplicaBorradoLogico()
    {
        $db = \Config\Database::connect();
        $db->table('Materia_Prima')->insert([
            'nombre_producto' => 'Ingrediente Caducado',
            'stock_actual'    => 0,
            'estado_materia'  => 1
        ]);
        $idInsertado = $db->insertID();

        $resultado = $this->call('get', 'materiaprima/eliminar/' . $idInsertado);

        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $idInsertado,
            'estado_materia'   => 0
        ]);
    }
}
// vendor/bin/phpunit --filter testVistaInventarioMuestraDatosReales --no-coverage
// vendor/bin/phpunit --filter testGuardarNuevoIngredienteEnBD --no-coverage
// vendor/bin/phpunit --filter testActualizarStockDeIngredienteEnBD --no-coverage
// vendor/bin/phpunit --filter testEliminarIngredienteAplicaBorradoLogico --no-coverage