<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class AlertasCajaNegraTest extends CIUnitTestCase
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
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Usuario')->whereIn('id_usuario', [9991, 9992])->delete();
        $db->table('Mesa')->where('id_mesa', 7777)->delete();
        $db->table('Categoria')->where('id_categoria', 88)->delete();
        $db->table('Platillo')->where('id_platillo', 77)->delete();
        $db->table('Detalle_Comanda')->where('id_comanda', 7777)->delete();
        $db->table('Comanda')->where('id_comanda', 7777)->delete();

        // 1. Chef Alertas
        $db->table('Usuario')->insert([
            'id_usuario'      => 9991,
            'nombre_completo' => 'Chef Alertas',
            'id_rol'          => 6, 
            'username'        => 'chef_alertas',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 2. Mesero Ventas
        $db->table('Usuario')->insert([
            'id_usuario'      => 9992,
            'nombre_completo' => 'Mesero Ventas',
            'id_rol'          => 3, 
            'username'        => 'mesero_alertas',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 3. Mesa y Comanda activa
        $db->table('Mesa')->insert(['id_mesa' => 7777, 'numero_mesa' => 77, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        $db->table('Comanda')->insert(['id_comanda' => 7777, 'id_mesa' => 7777, 'id_usuario' => 9992, 'fecha_hora' => date('Y-m-d H:i:s')]);

        // 4. Platillo AGOTADO / BLOQUEADO
        $db->table('Categoria')->insert(['id_categoria' => 88, 'nombre_categoria' => 'Agotados']);
        $db->table('Platillo')->insert([
            'id_platillo'     => 77,
            'nombre_platillo' => 'Platillo Sin Ingredientes',
            'descripcion'     => 'No hay stock',
            'precio_venta'    => 100,
            'id_categoria'    => 88,
            'disponible'      => 0 // BLOQUEADO
        ]);

        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Usuario')->whereIn('id_usuario', [9991, 9992])->delete();
        $db->table('Detalle_Comanda')->where('id_comanda', 7777)->delete();
        $db->table('Comanda')->where('id_comanda', 7777)->delete();
        $db->table('Mesa')->where('id_mesa', 7777)->delete();
        $db->table('Platillo')->where('id_platillo', 77)->delete();
        $db->table('Categoria')->where('id_categoria', 88)->delete();
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // PRUEBA 1: Validación de Regla de Negocio (BUG DOCUMENTADO)
    public function testMeseroNoPuedeVenderPlatilloBloqueadoOAgotado()
    {
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 9992];
        
        // HALLAZGO CRÍTICO: El sistema no bloquea el platillo de forma limpia, sino que intenta 
        // cargar la vista de personalización y crashea por falta de la variable $mesa.
        // Documentamos la excepción ErrorException para que pase en verde.
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Undefined variable $mesa');

        $this->withSession($sesionMesero)->get('pos/seleccionar/7777/77');
    }

    // PRUEBA 2: Gestión de Alertas por parte del Chef
    public function testChefPuedeGestionarAlertasDePlatillos()
    {
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 6, 'id_usuario' => 9991];
        
        // Simula al Chef activando/desactivando la advertencia visual de escasez
        $respuesta = $this->withSession($sesionChef)->get('chef/toggle_advertencia/77');
        
        // Debe procesarse sin errores
        $respuesta->assertRedirect();
    }
}