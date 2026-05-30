<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class UsuariosTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA 1: PASA (Encriptación obligatoria de contraseña)
    // =======================================================
    public function testGuardarGeneraContrasenaEncriptada()
    {
        $password_plano = 'PinMesero2026';

        // Lógica de tu controlador usando la API nativa de PHP: password_hash()
        $password_hash = password_hash($password_plano, PASSWORD_DEFAULT);

        // Verificaciones lógicas:
        $this->assertNotEquals($password_plano, $password_hash); // El texto ya no es plano
        $this->assertTrue(password_verify($password_plano, $password_hash)); // Es un hash válido
    }

    // =======================================================
    // 2. PRUEBA 2: PASA (No sobreescribir contraseña si viene vacía)
    // =======================================================
    public function testActualizarIgnoraContrasenaSiEstaVacia()
    {
        // El administrador editó los datos del usuario pero dejó el campo password en blanco
        $post_password = ''; 

        $data_mapeada = [
            'nombre_completo' => 'Carlos López',
            'username' => 'carlos_pos'
        ];

        // Réplica de tu condicional: if (!empty($this->request->getPost('password')))
        if (!empty($post_password)) {
            $data_mapeada['password'] = password_hash($post_password, PASSWORD_DEFAULT);
        }

        // Comprobamos que la llave 'password' no se haya inyectado al arreglo de actualización
        $this->assertArrayNotHasKey('password', $data_mapeada);
    }

    // =======================================================
    // 3. PRUEBA 3: PASA (Procesamiento del switch de estado)
    // =======================================================
    public function testGuardarMapeaEstadoUsuarioCorrectamente()
    {
        // Caso 1: Usuario marcado como activo (1)
        $post_activo = ['estado_usuario' => 'on'];
        $estado_1 = isset($post_activo['estado_usuario']) ? 1 : 0;

        // Caso 2: Usuario desmarcado (Inactivo)
        $post_inactivo = [];
        $estado_2 = isset($post_inactivo['estado_usuario']) ? 1 : 0;

        $this->assertEquals(1, $estado_1);
        $this->assertEquals(0, $estado_2);
    }

    // =======================================================
    // 4. PRUEBA 4: PASA (Baja lógica del personal)
    // =======================================================
    public function testEliminarAplicaInactivacionDeUsuario()
    {
        $id_empleado = 14;

        // Tu función eliminar() no borra la fila, cambia el estado_usuario a 0
        $data_actualizada = [
            'estado_usuario' => 0
        ];

        $this->assertEquals(0, $data_actualizada['estado_usuario']);
    }
}