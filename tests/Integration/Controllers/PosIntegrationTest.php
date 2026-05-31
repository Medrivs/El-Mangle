<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class PosIntegrationTest extends CIUnitTestCase
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

    // Retorna la sesión de un Mesero (Rol 3)
    private function getSesionMesero($id_usuario = 3)
    {
        return [
            'isLoggedIn' => true,
            'id_rol'     => 3, 
            'id_usuario' => $id_usuario
        ];
    }

    // Comprueba que un mesero no pueda ver las mesas de sus compañeros
    public function testMeseroSoloVeSusMesasAsignadas()
    {
        $db = \Config\Database::connect();
        
        // Mesa asignada al Mesero 3 (el que hará login)
        $db->table('Mesa')->insert(['numero_mesa' => 111, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 3]);
        
        // Mesa asignada al Mesero 99 (otro compañero)
        $db->table('Mesa')->insert(['numero_mesa' => 222, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 99]);

        $resultado = $this->withSession($this->getSesionMesero(3))->get('pos');

        $resultado->assertOK();
        
        // Debe ver su mesa, pero NO debe ver la del compañero
        $resultado->assertSee('111');
        $resultado->assertDontSee('222');
    }

    // Ejecuta la transacción más compleja del sistema: Carrito -> Comanda -> Inventario
    public function testEnviarOrdenCreaComandaYDeduceInventario()
    {
        $db = \Config\Database::connect();
        
        // 1. Preparamos la Mesa Libre
        $db->table('Mesa')->insert(['numero_mesa' => 333, 'estado_mesa' => 'Libre', 'activa' => 1]);
        $id_mesa = $db->insertID();

        // 2. Preparamos el Inventario (Materia Prima con 10 kg)
        $db->table('Materia_Prima')->insert([
            'nombre_producto' => 'Camarón Crudo TEST',
            'stock_actual'    => 10,
            'unidad_medida'   => 'Kg'
        ]);
        $id_materia = $db->insertID();

        // 3. Preparamos el Platillo y su Receta (Usa 2 Kg por platillo)
        $db->table('Platillo')->insert(['nombre_platillo' => 'Aguachile TEST', 'precio_venta' => 200, 'disponible' => 1]);
        $id_platillo = $db->insertID();

        $db->table('Receta')->insert([
            'id_platillo'      => $id_platillo,
            'id_materia_prima' => $id_materia,
            'cantidad_usada'   => 2 // 2 Kg por cada Aguachile
        ]);

        // 4. Simulamos el JSON del carrito (Pide 2 Aguachiles)
        $carritoJSON = json_encode([
            [
                'id'     => $id_platillo,
                'cant'   => 2, // Va a pedir 2 platillos (2 * 2kg = 4kg a descontar)
                'precio' => 200,
                'nota'   => 'Sin picante'
            ]
        ]);

        // 5. Enviamos la orden
        $resultado = $this->withSession($this->getSesionMesero())->post('pos/enviar_orden', [
            'id_mesa'       => $id_mesa,
            'datos_carrito' => $carritoJSON
        ]);

        $resultado->assertRedirectTo(base_url('pos/ver_comanda/' . $id_mesa));
        
        // 6. Verificaciones de Integración en Cascada
        // A) La mesa debe estar ocupada
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Ocupada']);
        
        // B) La comanda y el detalle deben existir
        $this->seeInDatabase('Detalle_Comanda', ['id_platillo' => $id_platillo, 'cantidad' => 2, 'comentarios' => 'Sin picante']);
        
        // C) El inventario debió bajar de 10 a 6 (10 - 4)
        $this->seeInDatabase('Materia_Prima', ['id_materia_prima' => $id_materia, 'stock_actual' => 6]);
    }

    // Comprueba el candado de seguridad al imprimir la cuenta
    public function testImprimirCuentaBloqueaMesaParaCaja()
    {
        $db = \Config\Database::connect();
        
        $db->table('Mesa')->insert(['numero_mesa' => 444, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        $id_mesa = $db->insertID();

        // Primera petición: El mesero pide la cuenta (Debe pasar y bloquear la mesa)
        $resultado1 = $this->withSession($this->getSesionMesero())->get("pos/imprimir_cuenta/$id_mesa");
        $resultado1->assertSessionHas('success');
        
        $this->seeInDatabase('Mesa', ['id_mesa' => $id_mesa, 'estado_mesa' => 'Por Pagar']);

        // Segunda petición: Intenta pedir la cuenta de nuevo por accidente
        $resultado2 = $this->withSession($this->getSesionMesero())->get("pos/imprimir_cuenta/$id_mesa");
        
        // El sistema debe rechazarlo
        $resultado2->assertSessionHas('error', '¡Acción denegada! La cuenta ya fue impresa una vez.');
    }
}
// vendor/bin/phpunit --filter testMeseroSoloVeSusMesasAsignadas --no-coverage
// vendor/bin/phpunit --filter testEnviarOrdenCreaComandaYDeduceInventario --no-coverage
// vendor/bin/phpunit --filter testImprimirCuentaBloqueaMesaParaCaja --no-coverage
