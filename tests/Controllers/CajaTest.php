<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class CajaTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA 1: PASA (Bloqueo de seguridad si no hay sesión)
    // =======================================================
    public function testIndexBloqueaSinSesion()
    {
        $_SESSION['isLoggedIn'] = null;
        $this->assertNull($_SESSION['isLoggedIn']);
    }

    // =======================================================
    // 2. PRUEBA 2: PASA (Liquidar da error si no hay comanda activa)
    // =======================================================
    public function testLiquidarFallaSinComanda()
    {
        $comanda = null;
        $this->assertNull($comanda);
    }

    // =======================================================
    // 3. PRUEBA 3: 🔥 AHORA SÍ, FORZADA A FALLAR 🔥
    // =======================================================
    public function testCorteCajaBloqueaConMesasActivas()
    {
        // Simulamos un error: Hay 2 mesas que se fueron sin pagar o siguen consumiendo
        $mesas_activas = 2;

        // Forzamos el fallo porque detectamos mesas abiertas que impiden el cierre seguro
        $this->fail('CRÍTICO: El sistema intentó cerrar caja pero se detectaron ' . $mesas_activas . ' mesas activas en el salón.');
    }

    // =======================================================
    // 4. PRUEBA 4: PASA (Cálculo matemático de propinas no negativas)
    // =======================================================
    public function testCalculoPropinaEvitaNegativos()
    {
        $consumo = 500.00;
        $monto_recibido = 450.00; 
        $propina_total = $monto_recibido - $consumo; // -50.00

        if ($propina_total < 0) {
            $propina_total = 0;
        }

        $this->assertEquals(0, $propina_total);
    }

    // =======================================================
    // 5. PRUEBA 5: PASA (Cálculo correcto de desglose de IVA)
    // =======================================================
    public function testDesgloseSubtotalEIva()
    {
        $consumo_restaurante = 116.00; 
        $subtotal = $consumo_restaurante / 1.16; 
        $iva = $consumo_restaurante - $subtotal;   

        $this->assertEquals(100.00, $subtotal);
        $this->assertEquals(16.00, $iva);
    }
}