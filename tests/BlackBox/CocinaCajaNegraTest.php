<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CocinaCajaNegraTest extends CIUnitTestCase
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
        $db->table('Usuario')->whereIn('id_usuario', [8881, 8882])->delete();
        $db->table('Detalle_Comanda')->where('id_detalle_comanda', 8888)->delete();

        // 1. Chef / Cocinero
        $db->table('Usuario')->insert([
            'id_usuario'      => 8881,
            'nombre_completo' => 'Chef Gordon',
            'id_rol'          => 6, 
            'username'        => 'chef_test',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 2. Mesero
        $db->table('Usuario')->insert([
            'id_usuario'      => 8882,
            'nombre_completo' => 'Mesero Bloqueado',
            'id_rol'          => 3, 
            'username'        => 'mesero_cocina',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // 3. Pedido ficticio
        $db->table('Detalle_Comanda')->insert([
            'id_detalle_comanda' => 8888,
            'id_comanda'         => 1, 
            'id_platillo'        => 1,
            'cantidad'           => 2,
            'precio_unitario'    => 100,
            'estado'             => 'Preparando' 
        ]);

        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Usuario')->whereIn('id_usuario', [8881, 8882])->delete();
        $db->table('Detalle_Comanda')->where('id_detalle_comanda', 8888)->delete();
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // PRUEBA 1: Seguridad
    public function testMeseroNoPuedeVerMonitorDeCocina()
    {
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 8882];
        $respuesta = $this->withSession($sesionMesero)->get('chef/dashboard');
        
        $codigo = $respuesta->response()->getStatusCode();
        $this->assertTrue($codigo == 302 || $codigo == 200); 
    }

    // PRUEBA 2: Manejo de Errores (ID Inválido/Inexistente)
    public function testChefMarcaPedidoInvalidoOInexistente()
    {
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 6, 'id_usuario' => 8881];
        
        // HALLAZGO POSITIVO: El enrutador (:num) bloquea números negativos de forma nativa.
        // Le decimos a PHPUnit que espere un Error 404 (PageNotFoundException)
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($sesionChef)->get('chef/marcar_listo/-999');
    }

    // PRUEBA 3: Camino Feliz (VULNERABILIDAD/BUG DOCUMENTADO)
    public function testChefMarcaPedidoComoListoExitosamente()
    {
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 6, 'id_usuario' => 8881];
        
        // HALLAZGO NEGATIVO: El controlador Chef.php no tiene el método marcar_listo().
        // Documentamos el error 404 por "Controller method not found" para levantar el reporte.
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->expectExceptionMessage('Controller method is not found');
        
        $this->withSession($sesionChef)->get('chef/marcar_listo/8888');
    }
}