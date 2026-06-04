<?php

namespace Tests\System;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ReportesFinancierosSystemTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup     = 'default'; 
    protected $migrate     = false;     
    protected $migrateOnce = false;     
    protected $refresh     = false;     

    // prueba de reportes financieros
    public function testReportesFinancierosConciliacion()
    {
        $db = \Config\Database::connect();
        
        // fase 0 limpieza financiera y blindaje de catalogos
        $db->table('Mesa')->where('id_mesa >', 0)->update(['estado_mesa' => 'Libre']);
        $db->table('Cuenta_Pago')->where('id_pago >', 0)->delete();
        $db->table('Reporte_Ventas')->where('id_reporte >', 0)->delete();
        
        // forzamos los nombres exactos para que el controlador no falle
        $db->query("INSERT INTO Metodo_Pago (id_metodo, nombre) VALUES (1, 'Efectivo') ON DUPLICATE KEY UPDATE nombre='Efectivo'");
        $db->query("INSERT INTO Metodo_Pago (id_metodo, nombre) VALUES (2, 'Tarjeta') ON DUPLICATE KEY UPDATE nombre='Tarjeta'");
        
        $db->table('Rol')->ignore(true)->insert(['id_rol' => 5, 'nombre_rol' => 'Caja']);
        $db->table('Usuario')->ignore(true)->insert(['id_usuario' => 555, 'nombre_completo' => 'Auditor Test', 'id_rol' => 5, 'estado_usuario' => 1]);

        // aseguramos la fecha exacta del servidor
        $hoy = date('Y-m-d');
        $hora_pago = $hoy . ' 12:00:00';

        // fase 1 inyeccion de ventas simulamos un dia de trabajo
        
        // venta 1 efectivo consumo 500 propina 50 total recibido 550
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

        // venta 2 efectivo consumo 200 propina 20 total recibido 220
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

        // venta 3 tarjeta consumo 1000 propina 150 total recibido 1150
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

        // fase 2 ejecucion del corte de caja
        $sesionCajero = ['isLoggedIn' => true, 'id_rol' => 5, 'id_usuario' => 555];
        
        $corte = $this->withSession($sesionCajero)->post('caja/corte_caja');
        
        if (session()->has('error')) {
            $this->fail('error en el controlador ' . session('error'));
        }

        $corte->assertRedirectTo(base_url('caja'));

        // fase 3 auditoria cruzada
        $reporteGenerado = $db->table('Reporte_Ventas')->where('fecha_cierre', $hoy)->get()->getRowArray();
        
        $this->assertNotNull($reporteGenerado, 'el reporte no se guardo');

        // forzamos coalesce para asegurar que devuelva 0 si algo falla
        $sumaReal = $db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN id_metodo = 1 THEN total ELSE 0 END), 0) as efectivo_real,
                COALESCE(SUM(CASE WHEN id_metodo = 2 THEN total ELSE 0 END), 0) as tarjeta_real,
                COALESCE(SUM(propina), 0) as propinas_reales,
                COALESCE(SUM(total - propina), 0) as venta_neta_real
            FROM Cuenta_Pago 
            WHERE DATE(fecha_hora_pago) = '$hoy'
        ")->getRowArray();

        // comparamos los calculos sin signos de puntuacion en los mensajes
        $this->assertEquals((float)$sumaReal['efectivo_real'], (float)$reporteGenerado['total_efectivo'], 'descuadre en efectivo');
        $this->assertEquals((float)$sumaReal['tarjeta_real'], (float)$reporteGenerado['total_tarjeta'], 'descuadre en tarjeta');
        $this->assertEquals((float)$sumaReal['propinas_reales'], (float)$reporteGenerado['total_propinas'], 'descuadre en propinas');
        $this->assertEquals((float)$sumaReal['venta_neta_real'], (float)$reporteGenerado['venta_neta'], 'descuadre critico en venta neta');
    }
}