<?php

namespace Tests\BlackBox;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ReportesCajaNegraTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Mesa')->where('id_mesa', 9999)->delete();
        $db->table('Usuario')->whereIn('id_usuario', [7771, 7773])->delete();

        // Cajero autorizado
        $db->table('Usuario')->insert([
            'id_usuario'      => 7771,
            'nombre_completo' => 'Cajero Autorizado',
            'id_rol'          => 5, // Rol Caja
            'username'        => 'cajero_reportes',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // Mesero (No autorizado para reportes financieros)
        $db->table('Usuario')->insert([
            'id_usuario'      => 7773,
            'nombre_completo' => 'Mesero Hacker',
            'id_rol'          => 3, // Rol Mesero
            'username'        => 'mesero_hacker',
            'password'        => password_hash('Seguridad!', PASSWORD_DEFAULT),
            'estado_usuario'  => 1
        ]);

        // Mesa ocupada para forzar el bloqueo del reporte
        $db->table('Mesa')->insert([
            'id_mesa'     => 9999,
            'numero_mesa' => 9999,
            'estado_mesa' => 'Ocupada',
            'activa'      => 1
        ]);
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->table('Mesa')->where('id_mesa', 9999)->delete();
        $db->table('Usuario')->whereIn('id_usuario', [7771, 7773])->delete();
        // Limpiamos si se llegó a crear algún reporte de prueba
        $db->table('Reporte_Ventas')->where('id_usuario_admin', 7771)->delete(); 
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // PRUEBA 1: Prueba de Seguridad (Control de Acceso)
    public function testSeguridadMeseroNoPuedeHacerCorteFinanciero()
    {
        $sesionMesero = ['isLoggedIn' => true, 'id_rol' => 3, 'id_usuario' => 7773];
        $respuesta = $this->withSession($sesionMesero)->post('caja/corte_caja');
        
        // El sistema lo saca (Redirección), no importa si es a '/' o a otro lado, 
        // lo importante es que YA NO está en 'caja/corte_caja'
        $respuesta->assertRedirect();
        
        // Verificamos que NO entró al módulo (la URL resultante NO debe contener 'caja')
        $this->assertStringNotContainsString('caja', $respuesta->response()->getHeaderLine('Location'));
    }
    // PRUEBA 2: Validación de Reglas de Negocio
    public function testRechazoDeReporteFinancieroConMesasActivas()
    {
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 7771];
        
        // Al haber una mesa "Ocupada", el sistema debe bloquear el reporte para evitar descuadres
        $respuesta = $this->withSession($sesionCajero)->post('caja/corte_caja');
        
        $respuesta->assertRedirectTo(base_url('caja'));
        $respuesta->assertSessionHas('error');
    }

    // PRUEBA 3: Camino Feliz
    public function testReporteFinancieroExitosoSinMesasActivas()
    {
        $db = \Config\Database::connect();
        // Cambiamos la mesa a libre simulando que el cajero ya cobró todo
        $db->table('Mesa')->where('id_mesa', 9999)->update(['estado_mesa' => 'Libre']);

        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 7771];
        $respuesta = $this->withSession($sesionCajero)->post('caja/corte_caja');
        
        $respuesta->assertRedirectTo(base_url('caja'));
        $respuesta->assertSessionHas('success');
    }
}