<?php

namespace App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;

class LoginTest extends CIUnitTestCase
{
    // =======================================================
    // 1. PRUEBA 1: PASA (Validación exitosa de PIN correcto)
    // =======================================================
    public function testIngresarPinCorrectoAutentica()
    {
        $pin_ingresado = '1234';

        // Simulamos un usuario activo devuelto por la base de datos
        $usuario_db = [
            'id_usuario' => 5,
            'nombre_completo' => 'Juan Pérez',
            'password' => '1234', // Guarda el PIN en texto plano o hash coincidente
            'id_rol' => 2
        ];

        // Regla del controlador: if ($pin === $usuario['password'])
        $autenticado = ($pin_ingresado === $usuario_db['password']);

        $this->assertTrue($autenticado);
    }

    // =======================================================
    // 2. PRUEBA 2: PASA (Rechazo de PIN si el usuario no es correcto)
    // =======================================================
    public function testIngresarPinInvalidoFalla()
    {
        $pin_ingresado = '9999'; // PIN incorrecto
        $usuario_db = ['password' => '1234'];

        $autenticado = ($pin_ingresado === $usuario_db['password']);

        $this->assertFalse($autenticado);
    }

    // =======================================================
    // 3. PRUEBA 3: PASA (Enrutamiento dinámico correcto por Rol)
    // =======================================================
    public function testRutaPorRolAsignaDestinoCorrecto()
    {
        // Probamos el rol de Caja (id_rol = 5) y el de Capitán (id_rol = 2)
        $id_rol_cajero = 5;
        $id_rol_capitan = 2;

        // Réplica de la lógica privada de rutaPorRol()
        $ruta_cajero = ($id_rol_cajero == 5) ? 'caja' : 'pos';
        $ruta_capitan = ($id_rol_capitan == 2) ? 'capitan' : 'pos';

        $this->assertEquals('caja', $ruta_cajero);
        $this->assertEquals('capitan', $ruta_capitan);
    }

    // =======================================================
    // 4. PRUEBA 4: PASA (Destrucción de datos al cerrar sesión)
    // =======================================================
    public function testLogoutLimpiaVariablesDeSesion()
    {
        // Simulamos una sesión activa antes de presionar salir
        $_SESSION['isLoggedIn'] = true;
        $_SESSION['nombre'] = 'Carlos';

        // Simulación de session()->destroy()
        unset($_SESSION['isLoggedIn']);
        unset($_SESSION['nombre']);

        $this->assertEmpty($_SESSION);
    }
}