<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CatalogoSystemTest extends CIUnitTestCase
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

    public function testCicloDeVidaDelMenuYReflejoEnPOS()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: AISLAMIENTO Y PREPARACIÓN
        // ===================================================================
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Administrador']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);

        // Creamos a los actores de nuestra historia
        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (1, 'Admin Catalogo', 1, 'admin_cat', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
                    
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (2, 'Mesero Catalogo', 3, 'mesero_cat', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // Creamos un entorno base: Categoría y un Ingrediente
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 150, 'nombre_categoria' => 'Especialidades E2E']);
        $db->table('Materia_Prima')->insert(['nombre_producto' => 'Langosta Cruda E2E', 'stock_actual' => 20, 'unidad_medida' => 'Kg']);
        $id_materia = $db->insertID();

        // ===================================================================
        // FASE 1: REGISTRO DE NUEVO PLATILLO (El Admin trabaja)
        // ===================================================================
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1];

        $datosPlatillo = [
            'nombre_platillo' => 'Langosta al Mojo E2E',
            'descripcion'     => 'Platillo de prueba de sistema',
            'precio_venta'    => 500.00,
            'id_categoria'    => 150
        ];

        // El admin guarda el platillo
        $this->withSession($sesionAdmin)->post('platillos/guardar', $datosPlatillo);

        // Rescatamos el ID dinámico que MySQL le dio a la Langosta
        $platilloNuevo = $db->table('Platillo')->where('nombre_platillo', 'Langosta al Mojo E2E')->get()->getRowArray();
        $this->assertNotNull($platilloNuevo, 'El platillo no se guardó en la BD.');
        $id_platillo = $platilloNuevo['id_platillo'];

        // Le asignamos su receta (1 kg de langosta cruda)
        $db->table('Receta')->insert([
            'id_platillo'      => $id_platillo,
            'id_materia_prima' => $id_materia,
            'cantidad_usada'   => 1
        ]);

        // ===================================================================
        // FASE 2: AJUSTE DE PRECIOS
        // ===================================================================
        // Subió la inflación, el admin actualiza el precio a $650
        $datosActualizados = [
            'nombre_platillo' => 'Langosta al Mojo E2E',
            'descripcion'     => 'Platillo de prueba (Editado)',
            'precio_venta'    => 650.00, 
            'id_categoria'    => 150,
            'disponible'      => 'on'
        ];

        $this->withSession($sesionAdmin)->post("platillos/actualizar/$id_platillo", $datosActualizados);

        // Verificamos que el cambio financiero se aplicó
        $this->seeInDatabase('Platillo', [
            'id_platillo'  => $id_platillo,
            'precio_venta' => 650.00
        ]);

        // ===================================================================
        // FASE 3: ELIMINACIÓN Y VERIFICACIÓN CRUZADA EN POS (El Mesero)
        // ===================================================================
        // Se acabó la temporada. El admin da de baja el platillo.
        $this->withSession($sesionAdmin)->get("platillos/eliminar/$id_platillo");

        // Comprobamos el borrado lógico
        $this->seeInDatabase('Platillo', [
            'id_platillo' => $id_platillo,
            'disponible'  => 0
        ]);

        // AHORA EL MESERO: Entra al sistema de punto de venta
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 2];

        // Le asignamos una mesa libre al mesero para que pueda abrir el menú
        $db->table('Mesa')->insert(['numero_mesa' => 8888, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 2]);
        $id_mesa = $db->insertID();

        // El mesero filtra por la categoría 150 (Especialidades E2E)
        $pantallaPos = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/150");

        // VERIFICACIÓN FINAL: El HTML que recibe el mesero NO DEBE contener a la Langosta
        $pantallaPos->assertDontSee('Langosta al Mojo E2E');
    }
}