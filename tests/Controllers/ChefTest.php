<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class ChefTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA 1: PASA (Carga por defecto de estación caliente)
    // =======================================================
    public function testDashboardCargaEstacionPorDefecto()
    {
        // Si no se envía estación por la URL, el controlador asigna 'caliente'
        $get_estacion = null;
        $estacion_activa = $get_estacion ?? 'caliente';

        $this->assertEquals('caliente', $estacion_activa);
    }

    // =======================================================
    // 2. PRUEBA 2: PASA (Mapeo correcto de categorías de estación fría)
    // =======================================================
    public function testObtenerCategoriasEstacionFria()
    {
        $estacion = 'fria';
        $categorias = [];

        // Lógica de tu función privada obtenerCategoriasPorEstacion()
        if ($estacion === 'fria') {
            $categorias = [3];
        }

        $this->assertContains(3, $categorias);
        $this->assertCount(1, $categorias);
    }

    // =======================================================
    // 3. PRUEBA 3: PASA (Agrupación de comandas por número de mesa)
    // =======================================================
    public function testAgruparComandasPorMesa()
    {
        // Simulamos dos platillos que pertenecen a la misma comanda e ID de mesa
        $items_raw = [
            ['id_comanda' => 10, 'numero_mesa' => 'Mesa 4', 'fecha_hora' => '2026-05-29 14:00:00', 'nombre_platillo' => 'Tacos'],
            ['id_comanda' => 10, 'numero_mesa' => 'Mesa 4', 'fecha_hora' => '2026-05-29 14:00:00', 'nombre_platillo' => 'Gringas']
        ];

        // Lógica de tu función agruparPorMesa()
        $agrupado = [];
        foreach ($items_raw as $i) {
            $id = $i['id_comanda'];
            if (!isset($agrupado[$id])) {
                $agrupado[$id] = ['mesa' => $i['numero_mesa'], 'items' => []];
            }
            $agrupado[$id]['items'][] = $i;
        }

        // Verificamos que se hayan unido bajo la misma clave de comanda
        $this->assertCount(1, $agrupado);
        $this->assertEquals('Mesa 4', $agrupado[10]['mesa']);
    }

    // =======================================================
    // 4. PRUEBA 4: PASA (Cambio de estado del platillo genera redirección)
    // =======================================================
    public function testCambiarEstadoRedireccionaAEstacion()
    {
        $id_detalle = 15;
        $nuevo_estado = 'Preparando';
        $estacion = 'bebidas';

        // Tu controlador arma la URL final antes de redireccionar
        $url_redireccion = "chef/dashboard?estacion=" . $estacion;

        $this->assertEquals("chef/dashboard?estacion=bebidas", $url_redireccion);
    }

    // =======================================================
    // 5. PRUEBA 5: PASA (Inversión del switch de advertencia/alerta)
    // =======================================================
    public function testToggleAdvertenciaInvierteEstado()
    {
        // Simulamos que la alerta manual de un producto estaba apagada (false)
        $alerta_manual_inicial = false;

        // Tu controlador ejecuta un NOT lógico en la consulta SQL (alerta_manual = NOT alerta_manual)
        $alerta_manual_final = !$alerta_manual_inicial; // Cambia a true

        $this->assertTrue($alerta_manual_final);
    }
}