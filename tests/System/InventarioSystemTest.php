<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class InventarioSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // prueba de control de stock y alertas automaticas
    public function testInventarioControlDeStockYAlertas()
    {
        $db = \Config\Database::connect();
        
        // fase 0 preparacion de seguridad
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Administrador']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);

        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (1, 'Admin Inventario', 1, 'admin_inv', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (2, 'Mesero Inventario', 3, 'mesero_inv', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // preparamos un entorno de menu temporal
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 250, 'nombre_categoria' => 'Mar Frio E2E']);
        
        $db->table('Mesa')->insert(['numero_mesa' => 9999, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 2]);
        $id_mesa = $db->insertID();

        // fase 1 registro de insumo en desabasto el admin trabaja
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1];

        // simulamos que el admin registra el atun pero advierte que el stock actual es 0
        $datosMateria = [
            'nombre_producto' => 'Atun Aleta Amarilla E2E',
            'stock_actual'    => 0, 
            'precio_compra'   => 250.00,
            'unidad_medida'   => 'Kg',
            'stock_minimo'    => 2
        ];

        // disparamos el guardado usando tu ruta oficial
        $this->withSession($sesionAdmin)->post('materiaprima/guardar', $datosMateria);

        // rescatamos el id que le asigno mysql
        $materiaNueva = $db->table('Materia_Prima')->where('nombre_producto', 'Atun Aleta Amarilla E2E')->get()->getRowArray();
        $this->assertNotNull($materiaNueva);
        $id_materia = $materiaNueva['id_materia_prima'];

        // creamos una tostada que dependa obligatoriamente de ese atun
        $db->table('Platillo')->insert(['nombre_platillo' => 'Tostada de Atun E2E', 'precio_venta' => 120, 'id_categoria' => 250, 'disponible' => 1]);
        $id_platillo = $db->insertID();
        $db->table('Receta')->insert(['id_platillo' => $id_platillo, 'id_materia_prima' => $id_materia, 'cantidad_usada' => 0.2]); 

        // fase 2 el efecto domino el mesero intenta vender sin stock
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 2];

        // el mesero abre la categoria en su tableta pos
        $pantallaPosDesabasto = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/250");
        
        // verificamos que las alertas pasaron a la vista sin romper el sistema
        $pantallaPosDesabasto->assertOK();

        // fase 3 reabastecimiento y compras
        // el admin actualiza el stock a 15 kilos
        $datosCompra = [
            'nombre_producto' => 'Atun Aleta Amarilla E2E',
            'stock_actual'    => 15, 
            'precio_compra'   => 250.00,
            'unidad_medida'   => 'Kg',
            'stock_minimo'    => 2
        ];

        $this->withSession($sesionAdmin)->post("materiaprima/actualizar/$id_materia", $datosCompra);

        // confirmamos fisicamente en mysql que entraron los 15 kilos
        $this->seeInDatabase('Materia_Prima', [
            'id_materia_prima' => $id_materia,
            'stock_actual'     => 15
        ]);

        // fase 4 verificacion final
        // el mesero vuelve a actualizar su tableta
        $pantallaPosAbastecida = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/250");
        
        // el algoritmo levanto el bloqueo el http debe devolver 200 ok
        $pantallaPosAbastecida->assertOK();
    }
}