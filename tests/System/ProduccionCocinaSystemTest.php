<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ProduccionCocinaSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    // ===================================================================
    // ⚙️ CONFIGURACIÓN DE BASE DE DATOS
    // ===================================================================
    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     
    // ===================================================================

    public function testFlujoDeProduccionYTransicionDeEstados()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: PREPARACIÓN DEL ENTORNO
        // ===================================================================
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 4, 'nombre_rol' => 'Chef']);

        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (333, 'Mesero Cocina', 3, 'mesero_cocina', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (444, 'Chef Maestro', 4, 'chef_maestro', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // Nota: Usamos la categoría 2 porque tu controlador Chef.php busca esa categoría para la estación "caliente"
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 2, 'nombre_categoria' => 'Caliente E2E']);
        
        $db->table('Mesa')->insert(['numero_mesa' => 5555, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 333]);
        $id_mesa = $db->insertID();

        $db->table('Platillo')->insert(['nombre_platillo' => 'Corte Ribeye E2E', 'precio_venta' => 400, 'id_categoria' => 2, 'disponible' => 1]);
        $id_platillo = $db->insertID();

        // ===================================================================
        // FASE 1: INYECCIÓN DE LA ORDEN (Lo que haría el mesero)
        // ===================================================================
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 333, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // El platillo entra con estado "Pendiente" y una nota especial
        $db->table('Detalle_Comanda')->insert([
            'id_comanda'      => $id_comanda,
            'id_platillo'     => $id_platillo,
            'cantidad'        => 1,
            'precio_unitario' => 400,
            'estado'          => 'Pendiente', 
            'comentarios'     => 'Termino 3/4, sin sal'
        ]);
        $id_detalle = $db->insertID();

        // ===================================================================
        // FASE 2: RECEPCIÓN EN PANTALLA (El Chef mira el monitor)
        // ===================================================================
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 4, 'id_usuario' => 444];

        $pantallaKanban = $this->withSession($sesionChef)->get('chef/dashboard?estacion=caliente');
        
        $pantallaKanban->assertOK();
        $pantallaKanban->assertSee('Corte Ribeye E2E');
        $pantallaKanban->assertSee('Termino 3/4, sin sal'); // Verifica que la comunicación con el piso sea correcta

        // ===================================================================
        // FASE 3: INICIO DE PREPARACIÓN (El Chef toma el ticket)
        // ===================================================================
        $transicion1 = $this->withSession($sesionChef)->get("chef/cambiar_estado/$id_detalle/Preparando?estacion=caliente");
        $transicion1->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));

        // Verificamos que la base de datos se haya actualizado para avisarle a los meseros
        $this->seeInDatabase('Detalle_Comanda', [
            'id_detalle_comanda' => $id_detalle,
            'estado'             => 'Preparando'
        ]);

        // ===================================================================
        // FASE 4: PLATILLO TERMINADO (El Chef toca la campana)
        // ===================================================================
        $transicion2 = $this->withSession($sesionChef)->get("chef/cambiar_estado/$id_detalle/Listo?estacion=caliente");
        $transicion2->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));

        $this->seeInDatabase('Detalle_Comanda', [
            'id_detalle_comanda' => $id_detalle,
            'estado'             => 'Listo'
        ]);
    }
}