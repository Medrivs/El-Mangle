<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ChefIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // simula la sesion del chef
    private function getSesionChef()
    {
        return ['isLoggedIn' => true, 'id_rol' => 4, 'id_usuario' => 4];
    }

    // nivel 1 integracion de lectura y toma de pedido
    public function testIntegracionLecturaYTomaPedido()
    {
        $db = \Config\Database::connect();
        
        // prepara el entorno con categoria caliente
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 2, 'nombre_categoria' => 'caliente']);
        $db->table('Mesa')->insert(['numero_mesa' => 50, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Platillo')->insert(['nombre_platillo' => 'sopa test', 'precio_venta' => 50, 'id_categoria' => 2, 'disponible' => 1]);
        $id_platillo = $db->insertID();

        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => $id_platillo, 'cantidad' => 1, 'precio_unitario' => 50, 'estado' => 'Pendiente']);
        $id_detalle = $db->insertID();

        // verifica que el pedido aparezca en el tablero
        $pantalla = $this->withSession($this->getSesionChef())->get('chef/dashboard?estacion=caliente');
        $pantalla->assertSee('sopa test');

        // simula que el chef toma el ticket
        $preparar = $this->withSession($this->getSesionChef())->get("chef/cambiar_estado/$id_detalle/Preparando?estacion=caliente");
        $preparar->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));

        // confirma el cambio en base de datos
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle, 'estado' => 'Preparando']);
    }

    // nivel 2 integracion de lectura y flujo completo de produccion
    public function testIntegracionFlujoCompletoKanban()
    {
        $db = \Config\Database::connect();
        
        // inserta un nuevo pedido pendiente
        $db->table('Platillo')->insert(['nombre_platillo' => 'crema test', 'precio_venta' => 60, 'id_categoria' => 2, 'disponible' => 1]);
        $id_platillo = $db->insertID();

        $db->table('Mesa')->insert(['numero_mesa' => 51, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => $id_platillo, 'cantidad' => 1, 'precio_unitario' => 60, 'estado' => 'Pendiente']);
        $id_detalle = $db->insertID();

        // el chef toma el ticket
        $this->withSession($this->getSesionChef())->get("chef/cambiar_estado/$id_detalle/Preparando?estacion=caliente");
        
        // el chef termina el platillo
        $terminar = $this->withSession($this->getSesionChef())->get("chef/cambiar_estado/$id_detalle/Listo?estacion=caliente");
        $terminar->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));

        // comprueba que recorrio todo el ciclo kanban
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle, 'estado' => 'Listo']);
    }
    
    // nivel 3 integracion total kanban y alertas manuales de inventario
    public function testIntegracionTotalCocinaYStock()
    {
        $db = \Config\Database::connect();
        
        // crea una materia prima con todo en orden
        $db->table('Materia_Prima')->insert([
            'nombre_producto'  => 'cebolla test',
            'stock_actual'     => 10,
            'unidad_medida'    => 'kg',
            'alerta_manual'    => 0,
            'bloqueado_manual' => 0
        ]);
        $id_materia = $db->insertID();

        $db->table('Platillo')->insert(['nombre_platillo' => 'aros test', 'precio_venta' => 40, 'id_categoria' => 2, 'disponible' => 1]);
        $id_platillo = $db->insertID();
        
        // relaciona la materia prima con el platillo para que aparezca en el panel del chef
        $db->table('Receta')->insert(['id_platillo' => $id_platillo, 'id_materia_prima' => $id_materia, 'cantidad_usada' => 1]);

        $db->table('Mesa')->insert(['numero_mesa' => 52, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        $id_mesa = $db->insertID();
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 3, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();
        $db->table('Detalle_Comanda')->insert(['id_comanda' => $id_comanda, 'id_platillo' => $id_platillo, 'cantidad' => 1, 'precio_unitario' => 40, 'estado' => 'Pendiente']);
        $id_detalle = $db->insertID();

        // activa alerta amarilla y luego bloqueo rojo
        $this->withSession($this->getSesionChef())->get("chef/toggle_advertencia/$id_materia?estacion=caliente");
        $this->withSession($this->getSesionChef())->get("chef/toggle_bloqueo/$id_materia?estacion=caliente");

        // termina el platillo
        $this->withSession($this->getSesionChef())->get("chef/cambiar_estado/$id_detalle/Listo?estacion=caliente");

        // verifica que los switches de la base de datos se invirtieron a true
        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $id_materia, 'alerta_manual' => 1, 'bloqueado_manual' => 1]);
        $this->seeInDatabase('Detalle_Comanda', ['id_detalle_comanda' => $id_detalle, 'estado' => 'Listo']);
    }
}

/* 
vendor/bin/phpunit --filter testIntegracionLecturaYTomaPedido tests/Integration/Controllers/ChefIntegrationTest.php --no-coverage
vendor/bin/phpunit --filter testIntegracionFlujoCompletoKanban tests/Integration/Controllers/ChefIntegrationTest.php --no-coverage
vendor/bin/phpunit --filter testIntegracionTotalCocinaYStock tests/Integration/Controllers/ChefIntegrationTest.php --no-coverage
vendor/bin/phpunit tests/Integration/Controllers/ChefIntegrationTest.php --no-coverage
*/