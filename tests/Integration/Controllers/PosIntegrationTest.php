<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class PosIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // simula la sesion del mesero
    private function getSesionMesero()
    {
        return ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 3];
    }

    // nivel 1 integracion de navegacion de piso mesas y categorias
    public function testIntegracionNavegacionMesasYMenu()
    {
        $db = \Config\Database::connect();
        
        // prepara mesa libre y categoria con id dinamico
        $db->table('Mesa')->insert(['numero_mesa' => 10, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Categoria')->insert(['nombre_categoria' => 'tacos test']);
        $id_categoria = $db->insertID();
        
        // integra la lectura del mapa de mesas
        $mapa = $this->withSession($this->getSesionMesero())->get('pos');
        $mapa->assertSee('10');

        // integra la transicion al menu de la mesa
        $menu = $this->withSession($this->getSesionMesero())->get("pos/mesa/$id_mesa");
        $menu->assertSee('tacos test');

        // integra el filtro de platillos usando el id dinamico
        $filtro = $this->withSession($this->getSesionMesero())->get("pos/filtrar/$id_mesa/$id_categoria");
        $filtro->assertOK();
    }


    // nivel 2 integracion de toma de orden comandas e inventario
    public function testIntegracionTomaDeOrdenYDescuentoInventario()
    {
        $db = \Config\Database::connect();
        
        // prepara inventario y categoria dinamica
        $db->table('Materia_Prima')->insert(['nombre_producto' => 'carne test', 'stock_actual' => 10]);
        $id_materia = $db->insertID();
        
        $db->table('Categoria')->insert(['nombre_categoria' => 'carnes']);
        $id_categoria = $db->insertID();
        
        $db->table('Platillo')->insert(['nombre_platillo' => 'corte test', 'precio_venta' => 200, 'id_categoria' => $id_categoria, 'disponible' => 1]);
        $id_platillo = $db->insertID();
        
        $db->table('Receta')->insert(['id_platillo' => $id_platillo, 'id_materia_prima' => $id_materia, 'cantidad_usada' => 1]);

        $db->table('Mesa')->insert(['numero_mesa' => 11, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();

        $carrito = json_encode([
            ['id' => $id_platillo, 'cant' => 2, 'precio' => 200, 'nota' => 'bien cocido']
        ]);

        // procesa la orden conectando interfaz base de datos y algoritmos
        $orden = $this->withSession($this->getSesionMesero())->post('pos/enviar_orden', [
            'id_mesa'       => $id_mesa,
            'datos_carrito' => $carrito
        ]);
        $orden->assertRedirectTo(base_url("pos/ver_comanda/$id_mesa"));

        // valida el impacto relacional en cascada
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Ocupada']);
        $this->seeInDatabase('Detalle_Comanda', ['id_platillo' => $id_platillo, 'cantidad' => 2, 'comentarios' => 'bien cocido']);
        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $id_materia, 'stock_actual' => 8]); 
    }

    // nivel 3 integracion total ciclo de consumo y bloqueo de cuenta
    public function testIntegracionTotalCicloOperativoYBloqueo()
    {
        $db = \Config\Database::connect();
        
        // levanta una mesa y orden dinamica
        $db->table('Mesa')->insert(['numero_mesa' => 12, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Categoria')->insert(['nombre_categoria' => 'postres test']);
        $id_categoria = $db->insertID();

        $db->table('Platillo')->insert(['nombre_platillo' => 'postre test', 'precio_venta' => 100, 'id_categoria' => $id_categoria, 'disponible' => 1]);
        $id_platillo = $db->insertID();

        $carrito = json_encode([['id' => $id_platillo, 'cant' => 3, 'precio' => 100, 'nota' => '']]);
        
        // ejecuta inyeccion de orden
        $this->withSession($this->getSesionMesero())->post('pos/enviar_orden', [
            'id_mesa'       => $id_mesa,
            'datos_carrito' => $carrito
        ]);

        // integra modulo de calculo de comanda
        $resumen = $this->withSession($this->getSesionMesero())->get("pos/ver_comanda/$id_mesa");
        $resumen->assertSee('300'); 

        // integra modulo de ticketera y bloqueo
        $imprimir = $this->withSession($this->getSesionMesero())->get("pos/imprimir_cuenta/$id_mesa");
        $imprimir->assertRedirectTo(base_url("pos/ver_comanda/$id_mesa"));

        // valida candado final para el cajero
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Por Pagar']);
    }
}
/* 

vendor/bin/phpunit --filter testIntegracionNavegacionMesasYMenu tests/Integration/Controllers/PosIntegrationTest.php --no-coverage

vendor/bin/phpunit --filter testIntegracionTomaDeOrdenYDescuentoInventario tests/Integration/Controllers/PosIntegrationTest.php --no-coverage

vendor/bin/phpunit --filter testIntegracionTotalCicloOperativoYBloqueo tests/Integration/Controllers/PosIntegrationTest.php --no-coverage

vendor/bin/phpunit tests/Integration/Controllers/PosIntegrationTest.php --no-coverage

*/