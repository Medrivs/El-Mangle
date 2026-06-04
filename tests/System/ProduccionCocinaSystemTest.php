<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ProduccionCocinaSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // prueba del flujo de produccion y transicion de estados
    public function testProduccionCocinaFlujoYTransicion()
    {
        $db = \Config\Database::connect();
        
        // fase 0 preparacion del entorno
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 4, 'nombre_rol' => 'Chef']);

        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (333, 'Mesero Cocina', 3, 'mesero_cocina', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (444, 'Chef Maestro', 4, 'chef_maestro', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // usamos la categoria 2 porque el controlador chef busca esa categoria para la estacion caliente
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 2, 'nombre_categoria' => 'Caliente E2E']);
        
        $db->table('Mesa')->insert(['numero_mesa' => 5555, 'estado_mesa' => 'Ocupada', 'activa' => 1, 'id_usuario_mesero' => 333]);
        $id_mesa = $db->insertID();

        $db->table('Platillo')->insert(['nombre_platillo' => 'Corte Ribeye E2E', 'precio_venta' => 400, 'id_categoria' => 2, 'disponible' => 1]);
        $id_platillo = $db->insertID();

        // fase 1 inyeccion de la orden simulamos lo que haria el mesero
        $db->table('Comanda')->insert(['id_mesa' => $id_mesa, 'id_usuario' => 333, 'fecha_hora' => date('Y-m-d H:i:s')]);
        $id_comanda = $db->insertID();

        // el platillo entra con estado pendiente y una nota especial
        $db->table('Detalle_Comanda')->insert([
            'id_comanda'      => $id_comanda,
            'id_platillo'     => $id_platillo,
            'cantidad'        => 1,
            'precio_unitario' => 400,
            'estado'          => 'Pendiente', 
            'comentarios'     => 'termino 3/4 sin sal'
        ]);
        $id_detalle = $db->insertID();

        // fase 2 recepcion en pantalla el chef mira el monitor
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 4, 'id_usuario' => 444];

        $pantallaKanban = $this->withSession($sesionChef)->get('chef/dashboard?estacion=caliente');
        
        $pantallaKanban->assertOK();
        $pantallaKanban->assertSee('Corte Ribeye E2E');
        // verificamos que la comunicacion con el piso sea correcta leyendo la nota
        $pantallaKanban->assertSee('termino 3/4 sin sal'); 

        // fase 3 inicio de preparacion el chef toma el ticket
        $transicion1 = $this->withSession($sesionChef)->get("chef/cambiar_estado/$id_detalle/Preparando?estacion=caliente");
        $transicion1->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));

        // verificamos que la base de datos se haya actualizado para avisarle a los meseros
        $this->seeInDatabase('Detalle_Comanda', [
            'id_detalle_comanda' => $id_detalle,
            'estado'             => 'Preparando'
        ]);

        // fase 4 platillo terminado el chef toca la campana
        $transicion2 = $this->withSession($sesionChef)->get("chef/cambiar_estado/$id_detalle/Listo?estacion=caliente");
        $transicion2->assertRedirectTo(base_url('chef/dashboard?estacion=caliente'));

        $this->seeInDatabase('Detalle_Comanda', [
            'id_detalle_comanda' => $id_detalle,
            'estado'             => 'Listo'
        ]);
    }
}