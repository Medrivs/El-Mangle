<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ReportesFinancierosSystemTest extends CIUnitTestCase
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

    public function testConciliacionMatematicaDelCorteDeCaja()
    {
        $db = \Config\Database::connect();
        
        // ===================================================================
        // FASE 0: LIMPIEZA FINANCIERA Y BLINDAJE DE CATÁLOGOS
        // ===================================================================
        $db->table('Mesa')->where('id_mesa >', 0)->update(['estado_mesa' => 'Libre']);
        $db->table('Cuenta_Pago')->where('id_pago >', 0)->delete();
        $db->table('Reporte_Ventas')->where('id_reporte >', 0)->delete();
        
        // FORZAMOS LOS NOMBRES EXACTOS PARA QUE EL CONTROLADOR NO FALLE LA VALIDACIÓN
        $db->query("INSERT INTO Metodo_Pago (id_metodo, nombre) VALUES (1, 'Efectivo') ON DUPLICATE KEY UPDATE nombre='Efectivo'");
        $db->query("INSERT INTO Metodo_Pago (id_metodo, nombre) VALUES (2, 'Tarjeta') ON DUPLICATE KEY UPDATE nombre='Tarjeta'");
        
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 5, 'nombre_rol' => 'Caja']);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 555, 'nombre_completo' => 'Auditor Test', 'id_rol' => 5, 'estado_usuario' => 1]);

        // Aseguramos la fecha exacta del servidor
        $hoy = date('Y-m-d');
        $hora_pago = $hoy . ' 12:00:00';

        // ===================================================================
        // FASE 1: INYECCIÓN DE VENTAS (Simulamos un día de trabajo)
        // ===================================================================
        
        // Venta 1: Efectivo, Consumo $500, Propina $50 (Total Recibido $550)
        $db->table('Cuenta_Pago')->insert([
            'id_comanda'      => 1, 
            'id_usuario'      => 555,
            'subtotal'        => 431.03,
            'iva'             => 68.97, 
            'propina'         => 50.00,
            'total'           => 550.00,
            'id_metodo'       => 1,
            'fecha_hora_pago' => $hora_pago
        ]);

        // Venta 2: Efectivo, Consumo $200, Propina $20 (Total Recibido $220)
        $db->table('Cuenta_Pago')->insert([
            'id_comanda'      => 2,
            'id_usuario'      => 555,
            'subtotal'        => 172.41,
            'iva'             => 27.59,
            'propina'         => 20.00,
            'total'           => 220.00,
            'id_metodo'       => 1,
            'fecha_hora_pago' => $hora_pago
        ]);

        // Venta 3: Tarjeta, Consumo $1000, Propina $150 (Total Recibido $1150)
        $db->table('Cuenta_Pago')->insert([
            'id_comanda'      => 3,
            'id_usuario'      => 555,
            'subtotal'        => 862.07,
            'iva'             => 137.93,
            'propina'         => 150.00,
            'total'           => 1150.00,
            'id_metodo'       => 2,
            'fecha_hora_pago' => $hora_pago
        ]);

        // ===================================================================
        // FASE 2: EJECUCIÓN DEL CORTE DE CAJA
        // ===================================================================
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 555];
        
        $corte = $this->withSession($sesionCajero)->post('caja/corte_caja');
        
        if (session()->has('error')) {
            $this->fail('Error en el controlador: ' . session('error'));
        }

        $corte->assertRedirectTo(base_url('caja'));

        // ===================================================================
        // FASE 3: AUDITORÍA CRUZADA
        // ===================================================================
        
        $reporteGenerado = $db->table('Reporte_Ventas')->where('fecha_cierre', $hoy)->get()->getRowArray();
        
        $this->assertNotNull($reporteGenerado, 'El Reporte de Ventas no se guardó en la base de datos.');

        // Forzamos COALESCE para asegurar que devuelva 0 si algo falla
        $sumaReal = $db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN id_metodo = 1 THEN total ELSE 0 END), 0) as efectivo_real,
                COALESCE(SUM(CASE WHEN id_metodo = 2 THEN total ELSE 0 END), 0) as tarjeta_real,
                COALESCE(SUM(propina), 0) as propinas_reales,
                COALESCE(SUM(total - propina), 0) as venta_neta_real
            FROM Cuenta_Pago 
            WHERE DATE(fecha_hora_pago) = '$hoy'
        ")->getRowArray();

        // Comparamos los cálculos
        $this->assertEquals((float)$sumaReal['efectivo_real'], (float)$reporteGenerado['total_efectivo'], 'Descuadre en Efectivo');
        $this->assertEquals((float)$sumaReal['tarjeta_real'], (float)$reporteGenerado['total_tarjeta'], 'Descuadre en Tarjeta');
        $this->assertEquals((float)$sumaReal['propinas_reales'], (float)$reporteGenerado['total_propinas'], 'Descuadre en Propinas');
        $this->assertEquals((float)$sumaReal['venta_neta_real'], (float)$reporteGenerado['venta_neta'], 'Descuadre CRÍTICO en la Venta Neta');
    }
}