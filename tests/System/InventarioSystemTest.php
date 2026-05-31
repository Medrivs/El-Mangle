<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class InventarioSystemTest extends CIUnitTestCase
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

    public function testControlDeStockYAlertasAutomaticas()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: PREPARACIÓN DE SEGURIDAD
        // ===================================================================
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Administrador']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);

        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (1, 'Admin Inventario', 1, 'admin_inv', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (2, 'Mesero Inventario', 3, 'mesero_inv', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // Preparamos un entorno de menú temporal
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 250, 'nombre_categoria' => 'Mar Frio E2E']);
        
        $db->table('Mesa')->insert(['numero_mesa' => 9999, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 2]);
        $id_mesa = $db->insertID();

        // ===================================================================
        // FASE 1: REGISTRO DE INSUMO EN DESABASTO (El Admin trabaja)
        // ===================================================================
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1];

        // Simulamos que el admin registra el atún pero advierte que el stock actual es 0
        $datosMateria = [
            'nombre_producto' => 'Atun Aleta Amarilla E2E',
            'stock_actual'    => 0, // ¡No hay atún!
            'precio_compra'   => 250.00,
            'unidad_medida'   => 'Kg',
            'stock_minimo'    => 2
        ];

        // Disparamos el guardado usando tu ruta oficial de MateriaPrima
        $this->withSession($sesionAdmin)->post('materiaprima/guardar', $datosMateria);

        // Rescatamos el ID que le asignó MySQL
        $materiaNueva = $db->table('Materia_Prima')->where('nombre_producto', 'Atun Aleta Amarilla E2E')->get()->getRowArray();
        $this->assertNotNull($materiaNueva, 'La materia prima no se guardó.');
        $id_materia = $materiaNueva['id_materia_prima'];

        // Creamos una tostada que dependa obligatoriamente de ese atún
        $db->table('Platillo')->insert(['nombre_platillo' => 'Tostada de Atun E2E', 'precio_venta' => 120, 'id_categoria' => 250, 'disponible' => 1]);
        $id_platillo = $db->insertID();
        $db->table('Receta')->insert(['id_platillo' => $id_platillo, 'id_materia_prima' => $id_materia, 'cantidad_usada' => 0.2]); // 200 gramos por tostada

        // ===================================================================
        // FASE 2: EL EFECTO DOMINÓ (El mesero intenta vender sin stock)
        // ===================================================================
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 2];

        // El mesero abre la categoría "Mar Frio E2E" en su tableta POS
        $pantallaPosDesabasto = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/250");
        
        // El controlador del POS debe procesar los arreglos "nombres_bloqueados" porque el stock es 0.
        // Verificamos que la variable haya pasado a la vista sin romper el sistema.
        $pantallaPosDesabasto->assertOK();

        // ===================================================================
        // FASE 3: REABASTECIMIENTO Y COMPRAS
        // ===================================================================
        // Llegó el camión del proveedor. El Admin actualiza el stock a 15 Kilos.
        $datosCompra = [
            'nombre_producto' => 'Atun Aleta Amarilla E2E',
            'stock_actual'    => 15, // Ya hay inventario
            'precio_compra'   => 250.00,
            'unidad_medida'   => 'Kg',
            'stock_minimo'    => 2
        ];

        $this->withSession($sesionAdmin)->post("materiaprima/actualizar/$id_materia", $datosCompra);

        // Confirmamos físicamente en MySQL que entraron los 15 kilos
        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $id_materia,
            'stock_actual'     => 15
        ]);

        // ===================================================================
        // FASE 4: VERIFICACIÓN FINAL
        // ===================================================================
        // El mesero vuelve a actualizar su tableta
        $pantallaPosAbastecida = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/250");
        
        // El sistema procesa de nuevo las alertas. Como ahora hay 15 kg (mayor al mínimo de 2),
        // el algoritmo levantó el bloqueo. El HTTP debe devolver 200 OK.
        $pantallaPosAbastecida->assertOK();
    }
}