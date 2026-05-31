<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class AlertasDisponibilidadSystemTest extends CIUnitTestCase
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

    public function testActivacionDeBotonDePanicoYBloqueoManual()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: PREPARACIÓN DEL ENTORNO (Catálogos y Usuarios)
        // ===================================================================
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 6, 'nombre_rol' => 'Cocina']);

        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (309, 'Mesero Alertas', 3, 'mesero_alertas', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (609, 'Chef Alertas', 6, 'chef_alertas', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 300, 'nombre_categoria' => 'Frios E2E']);
        
        $db->table('Mesa')->insert(['numero_mesa' => 7777, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 309]);
        $id_mesa = $db->insertID();

        // Creamos un ingrediente con MUCHÍSIMO stock (Para engañar al sistema)
        $db->table('Materia_Prima')->insert([
            'nombre_producto'  => 'Salmon Fresco E2E',
            'stock_actual'     => 100, // Hay 100 Kilos (Debería estar disponible)
            'unidad_medida'    => 'Kg',
            'stock_minimo'     => 5,
            'alerta_manual'    => 0,
            'bloqueado_manual' => 0
        ]);
        $id_materia = $db->insertID();

        $db->table('Platillo')->insert(['nombre_platillo' => 'Sashimi de Salmon', 'precio_venta' => 250, 'id_categoria' => 300, 'disponible' => 1]);
        $id_platillo = $db->insertID();
        $db->table('Receta')->insert(['id_platillo' => $id_platillo, 'id_materia_prima' => $id_materia, 'cantidad_usada' => 0.3]);

        // ===================================================================
        // FASE 1: EL CHEF ACTIVA LA ALERTA AMARILLA (Poco producto en buen estado)
        // ===================================================================
        $sesionChef = ['isLoggedIn' => true, 'id_rol' => 6, 'id_usuario' => 609];

        // El Chef nota que algo anda mal y activa la advertencia manual
        $alerta = $this->withSession($sesionChef)->get("chef/toggle_advertencia/$id_materia?estacion=fria");
        $alerta->assertRedirectTo(base_url('chef/dashboard?estacion=fria'));

        // Verificamos que el sistema forzó la alerta ignorando los 100 Kilos que hay en sistema
        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $id_materia,
            'alerta_manual'    => 1
        ]);

        // ===================================================================
        // FASE 2: EL CHEF ACTIVA EL BLOQUEO ROJO (Se echó a perder el salmón)
        // ===================================================================
        // Ocurre el accidente, el Chef bloquea el insumo por completo
        $bloqueo = $this->withSession($sesionChef)->get("chef/toggle_bloqueo/$id_materia?estacion=fria");
        $bloqueo->assertRedirectTo(base_url('chef/dashboard?estacion=fria'));

        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $id_materia,
            'bloqueado_manual' => 1
        ]);

        // ===================================================================
        // FASE 3: PROTECCIÓN EN EL PUNTO DE VENTA (El Mesero es advertido)
        // ===================================================================
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 309];

        // El mesero abre la tableta justo después del accidente
        $pantallaPos = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/300");
        
        // Comprobamos que el algoritmo dinámico de Pos::filtrar() no se rompa al procesar
        // la matriz de bloqueos manuales y logre cargar la página con las banderas rojas.
        $pantallaPos->assertOK();
    }
}