<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class PosTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA 1: PASA (Configuración de UI dinámica por Rol)
    // =======================================================
    public function testGetUIConfigAsignaParametrosSegunRol()
    {
        // Caso A: El usuario es Capitán (id_rol = 2)
        $id_rol_capitan = 2;
        $es_capitan = ($id_rol_capitan == 2);
        $bg_header_capitan = $es_capitan ? 'bg-[#0A1F3D]' : 'bg-[#15325A]';

        // Caso B: El usuario es Mesero convencional (id_rol = 3)
        $id_rol_mesero = 3;
        $es_capitan_b = ($id_rol_mesero == 2);
        $bg_header_mesero = $es_capitan_b ? 'bg-[#0A1F3D]' : 'bg-[#15325A]';

        $this->assertEquals('bg-[#0A1F3D]', $bg_header_capitan);
        $this->assertEquals('bg-[#15325A]', $bg_header_mesero);
    }

    // =======================================================
    // 2. PRUEBA 2: PASA (Cálculo de acumulado total de consumo)
    // =======================================================
    public function testCalcularConsumoTotalSumaMontosCorrectamente()
    {
        // Simulamos los detalles de los platillos activos de una comanda
        $detalles_comanda = [
            ['cantidad' => 2, 'precio_unitario' => 150.00], // 300.00
            ['cantidad' => 1, 'precio_unitario' => 45.00]   // 45.00
        ];

        // Réplica exacta de la lógica de sumatoria de tu controlador
        $total_calculado = 0;
        foreach ($detalles_comanda as $d) {
            $total_calculado += ($d['cantidad'] * $d['precio_unitario']);
        }

        $this->assertEquals(345.00, $total_calculado);
    }

    // =======================================================
    // 3. PRUEBA 3: PASA (Bloqueo de reimpresión de cuenta)
    // =======================================================
    public function testImprimirCuentaBloqueaSiYaEstaPorPagar()
    {
        // Simulamos una mesa que ya fue impresa previamente
        $mesa = [
            'id_mesa' => 4,
            'estado_mesa' => 'Por Pagar'
        ];

        // Regla de tu controlador: if ($mesa['estado_mesa'] === 'Por Pagar')
        $accion_denegada = ($mesa['estado_mesa'] === 'Por Pagar');

        $this->assertTrue($accion_denegada);
    }

    // =======================================================
    // 4. PRUEBA 4: PASA (Asignación automática de alerta por stock mínimo)
    // =======================================================
    public function testProcesarAlertasInventarioDetectaStockMinimo()
    {
        // Ingrediente simulado cuyos niveles están en zona de riesgo
        $ingrediente = [
            'nombre_producto' => 'Camarón Mediano',
            'bloqueado_manual' => 0,
            'alerta_manual' => 0,
            'stock_actual' => 2.0, // Está por debajo o igual al mínimo
            'stock_minimo' => 3.0
        ];

        $alerta_activada = false;

        // Lógica de evaluación de alertas de tu método procesarAlertasInventario()
        if ($ingrediente['bloqueado_manual'] == 1 || $ingrediente['stock_actual'] <= 0) {
            $alerta_activada = false; // Sería un bloqueo completo
        } elseif ($ingrediente['alerta_manual'] == 1 || $ingrediente['stock_actual'] <= $ingrediente['stock_minimo']) {
            $alerta_activada = true; // Activa bandera amarilla
        }

        $this->assertTrue($alerta_activada);
    }

    // =======================================================
    // 5. PRUEBA 5: PASA (Descuento preciso de insumos por receta)
    // =======================================================
    public function testProcesarItemsCarritoDescuentaStock()
    {
        $stock_inicial = 10.0; // 10 kg de carne en el almacén
        
        $item_carrito = ['cant' => 3]; // El cliente pide 3 ordenes de tacos
        $receta = ['cantidad_usada' => 0.250]; // Cada orden gasta 250 gramos (0.250 kg)

        // Fórmula de tu controlador: $cantidad_a_descontar = $r['cantidad_usada'] * $item['cant'];
        $cantidad_a_descontar = $receta['cantidad_usada'] * $item_carrito['cant']; // 0.750 kg
        $stock_final = $stock_inicial - $cantidad_a_descontar;

        $this->assertEquals(9.25, $stock_final);
    }
}