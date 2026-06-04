<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class CatalogoSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // prueba de ciclo de vida del menu y reflejo en el punto de venta
    public function testCatalogoCicloVidaYReflejoPos()
    {
        $db = \Config\Database::connect();
        
        // fase 0 aislamiento y preparacion
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 1, 'nombre_rol' => 'Administrador']);
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 3, 'nombre_rol' => 'Mesero']);

        // creamos a los actores de la prueba
        $passwordHash = password_hash('1234', PASSWORD_DEFAULT);
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (1, 'Admin Catalogo', 1, 'admin_cat', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");
                    
        $db->query("INSERT INTO Usuario (id_usuario, nombre_completo, id_rol, username, password, estado_usuario) 
                    VALUES (2, 'Mesero Catalogo', 3, 'mesero_cat', '$passwordHash', 1) 
                    ON DUPLICATE KEY UPDATE password='$passwordHash', estado_usuario=1");

        // creamos un entorno base categoria e ingrediente
        $db->table('Categoria')->ignore(true)->insert(['id_categoria' => 150, 'nombre_categoria' => 'Especialidades E2E']);
        $db->table('Materia_Prima')->insert(['nombre_producto' => 'Langosta Cruda E2E', 'stock_actual' => 20, 'unidad_medida' => 'Kg']);
        $id_materia = $db->insertID();

        // fase 1 registro de nuevo platillo el admin trabaja
        $sesionAdmin = ['isLoggedIn' => true, 'id_rol' => 1, 'id_usuario' => 1];

        $datosPlatillo = [
            'nombre_platillo' => 'Langosta al Mojo E2E',
            'descripcion'     => 'Platillo de prueba de sistema',
            'precio_venta'    => 500.00,
            'id_categoria'    => 150
        ];

        // el admin guarda el platillo
        $this->withSession($sesionAdmin)->post('platillos/guardar', $datosPlatillo);

        // rescatamos el id dinamico que mysql le dio a la langosta
        $platilloNuevo = $db->table('Platillo')->where('nombre_platillo', 'Langosta al Mojo E2E')->get()->getRowArray();
        $this->assertNotNull($platilloNuevo, 'el platillo no se guardo en la bd');
        $id_platillo = $platilloNuevo['id_platillo'];

        // le asignamos su receta
        $db->table('Receta')->insert([
            'id_platillo'      => $id_platillo,
            'id_materia_prima' => $id_materia,
            'cantidad_usada'   => 1
        ]);

        // fase 2 ajuste de precios
        // el admin actualiza el precio a 650
        $datosActualizados = [
            'nombre_platillo' => 'Langosta al Mojo E2E',
            'descripcion'     => 'Platillo de prueba (Editado)',
            'precio_venta'    => 650.00, 
            'id_categoria'    => 150,
            'disponible'      => 'on'
        ];

        $this->withSession($sesionAdmin)->post("platillos/actualizar/$id_platillo", $datosActualizados);

        // verificamos que el cambio financiero se aplico
        $this->seeInDatabase('Platillo', [
            'id_platillo'  => $id_platillo,
            'precio_venta' => 650.00
        ]);

        // fase 3 eliminacion y verificacion cruzada en pos
        // el admin da de baja el platillo
        $this->withSession($sesionAdmin)->get("platillos/eliminar/$id_platillo");

        // comprobamos el borrado logico
        $this->seeInDatabase('Platillo', [
            'id_platillo' => $id_platillo,
            'disponible'  => 0
        ]);

        // el mesero entra al sistema de punto de venta
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 2];

        // asignamos mesa libre al mesero para abrir el menu
        $db->table('Mesa')->insert(['numero_mesa' => 8888, 'estado_mesa' => 'Libre', 'activa' => 1, 'id_usuario_mesero' => 2]);
        $id_mesa = $db->insertID();

        // el mesero filtra por la categoria 150
        $pantallaPos = $this->withSession($sesionMesero)->get("pos/filtrar/$id_mesa/150");

        // verificacion final el html del pos no debe contener el platillo
        $pantallaPos->assertDontSee('Langosta al Mojo E2E');
    }
}