<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class M01_ComandasSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     


    public function testFlujoMaestroDeComandas()
    {
        $db = \Config\Database::connect();
        
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 300, 'nombre_completo' => 'Mesero E2E', 'id_rol' => 3, 'estado_usuario' => 1]);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 400, 'nombre_completo' => 'Chef E2E', 'id_rol' => 4, 'estado_usuario' => 1]);

   
        $db->table('Mesa')->insert(['numero_mesa' => 1100, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 300]);
        $id_mesa = $db->insertID();

        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 2, 'nombre_categoria' => 'Parrilla E2E']);
        
        $db->table('Materia_Prima')->insert(['nombre_producto' => 'Arrachera E2E', 'stock_actual' => 10, 'unidad_medida' => 'Kg']);
        $id_materia = $db->insertID();

        $db->table('Platillo')->insert(['nombre_platillo' => 'Taco de Arrachera', 'precio_venta' => 150, 'id_categoria' => 2, 'disponible' => 1]);
        $id_platillo = $db->insertID();
        
        $db->table('Receta')->insert(['id_platillo' => $id_platillo, 'id_materia_prima' => $id_materia, 'cantidad_usada' => 0.5]); 

        
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 300];

        $pantallaMesa = $this->withSession($sesionMesero)->get("pos/mesa/$id_mesa");
        $pantallaMesa->assertOK();
        $pantallaMesa->assertSee('Parrilla E2E'); 

        $pantallaPlatillos = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/2");
        $pantallaPlatillos->assertOK();

      
        $carritoJSON = json_encode([['id' => $id_platillo, 'cant' => 2, 'precio' => 150, 'nota' => 'Bien cocida']]);
        
        $envioOrden = $this->withSession($sesionMesero)->post('pos/enviar_orden', [
            'id_mesa'       => $id_mesa,
            'datos_carrito' => $carritoJSON
        ]);
        
        $envioOrden->assertRedirectTo(base_url("pos/ver_comanda/$id_mesa"));

        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $id_materia, 'stock_actual' => 9]);
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Ocupada']);

       
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 4, 'id_usuario' => 400];

        $pantallaCocina = $this->withSession($sesionChef)->get('chef/dashboard');
        
        $pantallaCocina->assertOK();
        
        $pantallaCocina->assertSee('Taco de Arrachera');
        $pantallaCocina->assertSee('Bien cocida');
        $pantallaCocina->assertSee('1100'); 
    }
}