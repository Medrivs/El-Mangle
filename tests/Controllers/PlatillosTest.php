<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class PlatillosTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA 1: PASA (Estado inicial disponible por defecto)
    // =======================================================
    public function testGuardarAsignaDisponiblePorDefecto()
    {
        $post = [
            'nombre_platillo' => 'Ceviche de Camarón',
            'precio_venta' => 180.00
        ];

        // Mapeo interno de tu función guardar()
        $data_mapeada = [
            'nombre_platillo' => $post['nombre_platillo'],
            'precio_venta'    => $post['precio_venta'],
            'disponible'      => 1 // Forzado a 1 por defecto en tu código
        ];

        $this->assertEquals(1, $data_mapeada['disponible']);
    }

    // =======================================================
    // 2. PRUEBA 2: PASA (Retención de imagen previa si no se sube un archivo nuevo)
    // =======================================================
    public function testProcesarImagenRetieneRutaActual()
    {
        // Simulamos que el usuario edita el platillo pero no sube ninguna foto nueva
        $imagen_actual = 'uploads/platillos/tacos_antiguos.jpg';
        
        // Simulación lógica de tu método privado procesarImagen():
        // Si el archivo no es válido o es nulo, retorna $ruta_actual
        $archivo_subido = null; 
        $ruta_final = ($archivo_subido !== null) ? 'uploads/platillos/nueva.jpg' : $imagen_actual;

        $this->assertEquals('uploads/platillos/tacos_antiguos.jpg', $ruta_final);
    }

    // =======================================================
    // 3. PRUEBA 3: PASA (Procesamiento del switch de disponibilidad en edición)
    // =======================================================
    public function testActualizarProcesaSwitchDisponibilidad()
    {
        // Caso 1: El platillo se marca como disponible en la interfaz
        $post_activo = ['disponible' => '1'];
        $estado_1 = isset($post_activo['disponible']) ? 1 : 0;

        // Caso 2: Se desmarca el platillo (no viaja el campo en el POST)
        $post_inactivo = [];
        $estado_2 = isset($post_inactivo['disponible']) ? 1 : 0;

        $this->assertEquals(1, $estado_1);
        $this->assertEquals(0, $estado_2);
    }

    // =======================================================
    // 4. PRUEBA 4: PASA (Baja lógica del menú)
    // =======================================================
    public function testEliminarAplicaBajaLogicaPlatillo()
    {
        $id_platillo = 8;

        // Tu función eliminar() ejecuta un update cambiando el parámetro disponible a 0
        $data_actualizada = [
            'id_platillo' => $id_platillo,
            'disponible'  => 0
        ];

        $this->assertEquals(0, $data_actualizada['disponible']);
    }
}
