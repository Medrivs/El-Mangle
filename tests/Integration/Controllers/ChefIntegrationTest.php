<?php

namespace Tests\Integration\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ChefIntegrationTest extends CIUnitTestCase
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

    // Retorna la sesión del Chef (Rol 6: Cocina)
    private function getSesionChef()
    {
        return [
            'isLoggedIn' => true,
            'id_rol'     => 6, 
            'id_usuario' => 4
        ];
    }

    // Inyecta todo el ecosistema de un pedido para ver si el Kanban lo atrapa en la estación correcta
    public function testDashboardCargaComandasPorEstacion()
    {
        $db = \Config\Database::connect();
        
        // 1. Necesitamos una categoría que pertenezca a la estación "caliente" (ej. Categoría 2)
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 2, 'nombre_categoria' => 'Fuerte']);
        
        // 2. Creamos un platillo de esa categoría
        $db->table('Platillo')->insert([
            'nombre_platillo' => 'Tacos de Marlin TEST',
            'id_categoria'    => 2,
            'disponible'      => 1
        ]);
        $id_platillo = $db->insertID();

        // 3. Creamos la Mesa y la Comanda
        $db->table('Mesa')->insert(['numero_mesa' => 888, 'estado_mesa' => 'Ocupada', 'activa' => 1]);
        $id_mesa = $db->insertID();
        
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 1, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // 4. Insertamos el detalle como 'Pendiente'
        $db->table('Detalle_Comanda')->insert([
            'id_comanda'      => $id_comanda, 
            'id_platillo'     => $id_platillo, 
            'cantidad'        => 2, 
            'precio_unitario' => 150, 
            'estado'          => 'Pendiente'
        ]);

        // Simula entrar a la pantalla de cocina caliente
        $resultado = $this->withSession($this->getSesionChef())->get('chef/dashboard?estacion=caliente');

        $resultado->assertOK();
        $resultado->assertSee('Tacos de Marlin TEST');
    }

    // Comprueba que al dar clic en "Preparar", cambie el estado en MySQL y mantenga al chef en su estación
    public function testCambiarEstadoActualizaMySQLYRedirige()
    {
        $db = \Config\Database::connect();
        
        // Insertamos un registro base directo en Detalle_Comanda
        $db->table('Detalle_Comanda')->insert([
            'id_comanda'      => 1, // ID ficticio para forzar la prueba
            'id_platillo'     => 1, 
            'cantidad'        => 1, 
            'precio_unitario' => 100, 
            'estado'          => 'Pendiente'
        ]);
        $id_detalle = $db->insertID();

        // El Chef pasa el platillo a "Preparando" desde la estación "fria"
        $resultado = $this->withSession($this->getSesionChef())
                          ->get("chef/cambiar_estado/$id_detalle/Preparando?estacion=fria");

        $resultado->assertRedirectTo(base_url('chef/dashboard?estacion=fria'));
        
        // Verificamos que la BD haya acatado la orden
        $this->seeInDatabase('Detalle_Comanda', [
            'id_detalle_comanda' => $id_detalle, 
            'estado'             => 'Preparando'
        ]);
    }

    // Verifica la función booleana de alerta (inventario lateral)
    public function testToggleAdvertenciaInvierteElValorEnMySQL()
    {
        $db = \Config\Database::connect();
        
        // Creamos una materia prima sin alertas (0)
        $db->table('Materia_Prima')->insert([
            'nombre_producto' => 'Cilantro TEST',
            'estado_materia'  => 1,
            'alerta_manual'   => 0
        ]);
        $id_materia = $db->insertID();

        $resultado = $this->withSession($this->getSesionChef())
                          ->get("chef/toggle_advertencia/$id_materia?estacion=caliente");

        $resultado->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));
        
        // Verificamos que el operador lógico NOT de SQL haya cambiado el 0 por un 1
        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $id_materia, 
            'alerta_manual'    => 1
        ]);
    }

    // Verifica el botón rojo de Bloqueo 86 (Agotado)
    public function testToggleBloqueoInvierteElValorEnMySQL()
    {
        $db = \Config\Database::connect();
        
        // Creamos una materia prima activa y sin bloqueos
        $db->table('Materia_Prima')->insert([
            'nombre_producto'  => 'Pulpo TEST',
            'estado_materia'   => 1,
            'bloqueado_manual' => 0
        ]);
        $id_materia = $db->insertID();

        $resultado = $this->withSession($this->getSesionChef())
                          ->get("chef/toggle_bloqueo/$id_materia?estacion=fria");
                          
        // Verificamos que ahora esté bloqueada en MySQL
        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $id_materia, 
            'bloqueado_manual' => 1
        ]);
    }
}