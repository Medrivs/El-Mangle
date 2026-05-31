<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class PlatillosIntegrationTest extends CIUnitTestCase
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

    // Inyectamos una sesión de Admin por si en el futuro proteges estas rutas
    protected function setUp(): void
    {
        parent::setUp();
        $session = \Config\Services::session();
        $session->set([
            'isLoggedIn' => true,
            'id_rol'     => 1,
            'id_usuario' => 1
        ]);
    }

    // Verifica que el catálogo lea de la BD y haga el JOIN con la categoría
    public function testVistaIndexMuestraListaDePlatillos()
    {
        $db = \Config\Database::connect();
        
        $db->table('Categoria')->ignore(true)->insert([
            'id_categoria'     => 100, 
            'nombre_categoria' => 'Mariscos TEST'
        ]);

        $db->table('Platillo')->insert([
            'nombre_platillo' => 'Ceviche Veracruzano TEST',
            'descripcion'     => 'Preparación tradicional',
            'precio_venta'    => 180.00,
            'id_categoria'    => 100,
            'disponible'      => 1
        ]);

        $resultado = $this->get('platillos');

        $resultado->assertOK();
        $resultado->assertSee('Ceviche Veracruzano TEST');
    }

    // Simula el llenado del formulario y verifica la inserción real en MySQL
    public function testGuardarInsertaPlatilloEnBD()
    {
        $db = \Config\Database::connect();
        
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 101, 'nombre_categoria' => 'Pescados TEST']);

        $datosFormulario = [
            'nombre_platillo' => 'Filete Empanizado TEST',
            'descripcion'     => 'Para mostrador',
            'precio_venta'    => 120.50,
            'id_categoria'    => 101
        ];

        $resultado = $this->post('platillos/guardar', $datosFormulario);

        $resultado->assertRedirectTo(base_url('platillos'));
        
        $this->seeInDatabase('Platillo', [
            'nombre_platillo' => 'Filete Empanizado TEST',
            'precio_venta'    => 120.50,
            'disponible'      => 1
        ]);
    }

    // Crea un platillo temporal, modifica su precio y revisa el cambio
    public function testActualizarModificaDatosDelPlatillo()
    {
        $db = \Config\Database::connect();
        
        $db->table('Platillo')->insert([
            'nombre_platillo' => 'Tostada Vieja',
            'precio_venta'    => 40.00,
            'disponible'      => 1
        ]);
        $id_platillo = $db->insertID();

        // Mandamos el formulario con el precio actualizado a $50
        $datosActualizados = [
            'nombre_platillo' => 'Tostada Vieja',
            'precio_venta'    => 50.00,
            'disponible'      => 'on' // Simula el checkbox marcado
        ];

        $resultado = $this->post("platillos/actualizar/$id_platillo", $datosActualizados);

        $resultado->assertRedirectTo(base_url('platillos'));
        
        $this->seeInDatabase('Platillo', [
            'id_platillo'  => $id_platillo,
            'precio_venta' => 50.00,
            'disponible'   => 1
        ]);
    }

    // Dispara el enlace de eliminar y confirma que apague la disponibilidad
    public function testEliminarAplicaBorradoLogico()
    {
        $db = \Config\Database::connect();
        
        $db->table('Platillo')->insert([
            'nombre_platillo' => 'Platillo a Borrar',
            'disponible'      => 1
        ]);
        $id_platillo = $db->insertID();

        $resultado = $this->get("platillos/eliminar/$id_platillo");

        $resultado->assertRedirectTo(base_url('platillos'));
        
        $this->seeInDatabase('Platillo', [
            'id_platillo' => $id_platillo,
            'disponible'  => 0 // Verificamos el borrado lógico
        ]);
    }
}
//vendor/bin/phpunit --filter testVistaIndexMuestraListaDePlatillos --no-coverage
//vendor/bin/phpunit --filter testGuardarInsertaPlatilloEnBD --no-coverage
//vendor/bin/phpunit --filter testActualizarModificaDatosDelPlatillo --no-coverage  
//vendor/bin/phpunit --filter testEliminarAplicaBorradoLogico --no-coverage
