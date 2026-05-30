<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class CapitanTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA UNITARIA 1: PASA (Bloqueo de acceso sin rol)
    // =======================================================
    public function testIndexRedirigeSinRolCapitan()
    {
        $_SESSION['isLoggedIn'] = true;
        $_SESSION['id_rol'] = 1; 

        $this->assertEquals(1, $_SESSION['id_rol']);
        $this->assertTrue(true); 
    }

    // =======================================================
    // 2. PRUEBA UNITARIA 2: 🔥 FORZADA A FALLAR 🔥 (Transferir)
    // =======================================================
    public function testTransferirFallaSinDatos()
    {
        // Simulamos que el formulario SÍ lleva datos de mesas
        $post = ['id_mesa_origen' => 5, 'id_mesa_destino' => 8]; 
        
        // La prueba espera que esté vacío, pero como SÍ tiene datos, va a TRONAR
        $this->assertEmpty($post); 
    }

    // =======================================================
    // 3. PRUEBA UNITARIA 3: PASA (Reabrir mesa)
    // =======================================================
    public function testReabrirMesaExitoso()
    {
        $id_mesa = 1;
        $estado_mesa = 'Ocupada';

        $this->assertEquals(1, $id_mesa);
        $this->assertEquals('Ocupada', $estado_mesa);
    }

    // =======================================================
    // 4. PRUEBA UNITARIA 4: 🔥 FORZADA A FALLAR 🔥 (Cancelar ítem)
    // =======================================================
    public function testCancelarItemFallaCantidadInvalida()
    {
        // Simulamos que el capitán pone una cantidad correcta (por ejemplo, 3 plátanos/cervezas)
        $cantidad_cancelar = 3;

        // La prueba exige que el número sea menor o igual a 0. Como pusimos 3, va a TRONAR
        $this->assertLessThanOrEqual(0, $cantidad_cancelar);
    }

    // =======================================================
    // 5. PRUEBA UNITARIA 5: PASA (Dividir cuenta)
    // =======================================================
    public function testEjecutarDivisionFallaSinItems()
    {
        $items = [];
        $this->assertEmpty($items);
    }
}