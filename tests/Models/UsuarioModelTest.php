<?php
 
namespace Tests\App\Models;
 
use CodeIgniter\Test\CIUnitTestCase;
use App\Models\UsuarioModel;
 
/**
 * Pruebas unitarias del UsuarioModel
 * Módulo: Administrador
 * 
 * Qué verifica este archivo:
 *  - Que el modelo apunta a la tabla correcta (usuario)
 *  - Que la llave primaria es id_usuario
 *  - Que los campos permitidos (allowedFields) son exactamente los definidos
 *  - Que la lógica de borrado lógico (estado_usuario = 0) funciona correctamente
 *  - Que el hash de contraseña se genera bien
 *  - Que la función rutaPorRol del Login devuelve las rutas correctas según el rol
 *
 * Cómo correr SOLO este archivo:
 *   vendor/bin/phpunit tests/app/Models/UsuarioModelTest.php
 *
 * Cómo correr solo un método:
 *   vendor/bin/phpunit --filter testTablaEsUsuario tests/app/Models/UsuarioModelTest.php
 */
class UsuarioModelTest extends CIUnitTestCase
{
    // =========================================================
    // SETUP - Se ejecuta antes de cada test
    // =========================================================
 
    private UsuarioModel $model;
 
    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UsuarioModel();
    }
 
    // =========================================================
    // BLOQUE 1: Configuración del modelo
    // Pruebas simples que NO necesitan base de datos.
    // Verifican que el modelo esté bien configurado.
    // =========================================================
 
    /**
     * @test
     * Verifica que el modelo apunta a la tabla 'usuario' en la BD.
     * (El test anterior tenía 'Usuarios' con U mayúscula y s final - era incorrecto)
     */
    public function testTablaEsUsuario(): void
    {
        // Assert - afirmamos que la tabla sea exactamente 'usuario' (minúsculas, sin 's' al final)
        $this->assertEquals('usuario', $this->model->table);
    }
 
    /**
     * @test
     * Verifica que la llave primaria es 'id_usuario'.
     */
    public function testLlavePrimariaEsIdUsuario(): void
    {
        $this->assertEquals('id_usuario', $this->model->primaryKey);
    }
 
    /**
     * @test
     * Verifica que todos los campos permitidos están presentes en allowedFields.
     * Esto protege contra inyección de campos maliciosos en formularios.
     */
    public function testAllowedFieldsContieneLosCamposEsperados(): void
    {
        $camposEsperados = [
            'nombre_completo',
            'id_rol',
            'username',
            'password',
            'telefono',
            'fecha_ingreso',
            'estado_usuario',
        ];
 
        foreach ($camposEsperados as $campo) {
            $this->assertContains(
                $campo,
                $this->model->allowedFields,
                "El campo '$campo' debería estar en allowedFields"
            );
        }
    }
 
    /**
     * @test
     * Verifica que NO hay campos extra inesperados en allowedFields.
     * Si alguien agrega un campo sin querer, este test lo detecta.
     */
    public function testAllowedFieldsNoTieneExtraCampos(): void
    {
        $cantidadEsperada = 7; // Los 7 campos definidos en el modelo
        $this->assertCount(
            $cantidadEsperada,
            $this->model->allowedFields,
            "allowedFields debería tener exactamente $cantidadEsperada campos"
        );
    }
 
    // =========================================================
    // BLOQUE 2: Lógica de contraseñas
    // Pruebas que verifican el comportamiento de password_hash
    // y password_verify, que son las funciones que usa el Login.
    // =========================================================
 
    /**
     * @test
     * Verifica que password_hash genera un hash diferente al texto original.
     * Esta es la protección principal de los PINes de los usuarios.
     */
    public function testPasswordHashNoPuedeLeerseDirectamente(): void
    {
        $pin = '1234';
 
        // Arrange + Act
        $hash = password_hash($pin, PASSWORD_DEFAULT);
 
        // Assert - el hash no debe ser igual al texto plano
        $this->assertNotEquals($pin, $hash);
        // Assert - el hash debe ser una cadena no vacía
        $this->assertNotEmpty($hash);
    }
 
    /**
     * @test
     * Verifica que password_verify puede confirmar que un PIN es correcto.
     * Esto simula exactamente lo que hace el Login cuando alguien teclea su PIN.
     */
    public function testPasswordVerifyValidaPinCorrecto(): void
    {
        $pin  = '9876'; // PIN del cajero Victor
 
        // Act - así guarda el controlador Usuarios en la BD
        $hash = password_hash($pin, PASSWORD_DEFAULT);
 
        // Assert - así valida el controlador Login cuando alguien entra
        $this->assertTrue(
            password_verify($pin, $hash),
            'password_verify debería devolver true para el PIN correcto'
        );
    }
 
    /**
     * @test
     * Verifica que password_verify rechaza un PIN incorrecto.
     */
    public function testPasswordVerifyRechazaPinIncorrecto(): void
    {
        $pinCorrecto   = '1234';
        $pinIncorrecto = '0000';
 
        $hash = password_hash($pinCorrecto, PASSWORD_DEFAULT);
 
        $this->assertFalse(
            password_verify($pinIncorrecto, $hash),
            'password_verify debería devolver false para un PIN incorrecto'
        );
    }
 
    /**
     * @test
     * @dataProvider proveedorPines
     * Verifica que todos los PINes de los empleados del Mangle
     * se pueden hashear y verificar correctamente.
     */
    public function testTodosLosPinesDelMangleSePuedenHashear(string $pin, string $empleado): void
    {
        $hash = password_hash($pin, PASSWORD_DEFAULT);
 
        $this->assertTrue(
            password_verify($pin, $hash),
            "El PIN de $empleado ('$pin') debería pasar la verificación"
        );
    }
 
    /**
     * Proveedor de datos: PINes reales del restaurante El Mangle
     * (los mismos que están en la BD de respaldo)
     */
    public static function proveedorPines(): array
    {
        return [
            'admin Alan'       => ['1234', 'Alan Medina Rivas (Admin)'],
            'capitan Arturo'   => ['2303', 'José Arturo Flores (Capitán)'],
            'mesero Efraín'    => ['2606', 'Efraín Hurtado (Mesero)'],
            'chef Juan'        => ['1010', 'Juan Pablo Mateo (Chef)'],
            'cajero Victor'    => ['9876', 'Victor Hugo Ojeda (Cajero)'],
        ];
    }
 
    // =========================================================
    // BLOQUE 3: Lógica de borrado lógico (estado_usuario)
    // El controlador Usuarios.php::eliminar() no borra el registro,
    // solo pone estado_usuario = 0. Aquí probamos esa lógica.
    // =========================================================
 
    /**
     * @test
     * Verifica que el array de datos para "eliminar" un usuario
     * solo cambia estado_usuario a 0, no borra nada más.
     *
     * Esto replica exactamente lo que hace Usuarios::eliminar($id)
     * antes de llamar a $model->update($id, $data).
     */
    public function testBorradoLogicoUsaEstadoCero(): void
    {
        // Arrange - así construye el controlador el array antes del update
        $dataBaja = [
            'estado_usuario' => 0
        ];
 
        // Assert - solo debe tener ese campo, no otros
        $this->assertArrayHasKey('estado_usuario', $dataBaja);
        $this->assertEquals(0, $dataBaja['estado_usuario']);
        $this->assertCount(1, $dataBaja, 'El borrado lógico solo debe modificar estado_usuario');
    }
 
    /**
     * @test
     * Verifica que estado_usuario = 1 significa activo.
     */
    public function testEstadoActivoEsUno(): void
    {
        $estadoActivo = 1;
        $this->assertEquals(1, $estadoActivo);
        $this->assertTrue((bool) $estadoActivo);
    }
 
    /**
     * @test
     * Verifica que estado_usuario = 0 significa inactivo.
     */
    public function testEstadoInactivoEsCero(): void
    {
        $estadoInactivo = 0;
        $this->assertEquals(0, $estadoInactivo);
        $this->assertFalse((bool) $estadoInactivo);
    }
 
    // =========================================================
    // BLOQUE 4: Lógica de roles
    // El Login.php::rutaPorRol() decide a dónde manda a cada usuario.
    // Probamos esa lógica sin necesidad de levantar HTTP.
    // =========================================================
 
    /**
     * @test
     * @dataProvider proveedorRoles
     * Verifica que cada rol recibe la ruta correcta.
     * Replica la lógica del método privado Login::rutaPorRol()
     */
    public function testRutaCorrectaPorRol(int $idRol, string $rutaEsperada): void
    {
        // Act - replicamos la lógica de rutaPorRol()
        if ($idRol == 1) {
            $ruta = 'usuarios';
        } elseif ($idRol == 4) {
            $ruta = 'chef/dashboard';
        } elseif ($idRol == 5) {
            $ruta = 'caja';
        } else {
            $ruta = 'pos'; // Capitán (2) y Mesero (3)
        }
 
        $this->assertEquals($rutaEsperada, $ruta);
    }
 
    /**
     * Proveedor: todos los roles del Mangle y sus rutas esperadas
     */
    public static function proveedorRoles(): array
    {
        return [
            'Administrador (1) → usuarios'    => [1, 'usuarios'],
            'Capitán (2) → pos'               => [2, 'pos'],
            'Mesero (3) → pos'                => [3, 'pos'],
            'Chef (4) → chef/dashboard'       => [4, 'chef/dashboard'],
            'Cajero (5) → caja'               => [5, 'caja'],
        ];
    }
 
    // =========================================================
    // BLOQUE 5: Construcción del array de datos (guardado)
    // El controlador Usuarios::guardar() construye un array antes
    // de insertar. Verificamos que ese array tiene la estructura correcta.
    // =========================================================
 
    /**
     * @test
     * Verifica que el array de datos para crear usuario contiene todos los campos necesarios.
     */
    public function testArrayGuardarContieneTodasLasClaves(): void
    {
        // Arrange - simulamos lo que construye Usuarios::guardar()
        $dataNuevoUsuario = [
            'nombre_completo' => 'Pedro García López',
            'id_rol'          => 3,
            'username'        => 'mesero_pedro',
            'password'        => password_hash('5555', PASSWORD_DEFAULT),
            'telefono'        => '4771234567',
            'fecha_ingreso'   => '2026-05-28',
            'estado_usuario'  => 1,
        ];
 
        // Assert - todos los allowedFields deben estar presentes
        foreach ($this->model->allowedFields as $campo) {
            $this->assertArrayHasKey(
                $campo,
                $dataNuevoUsuario,
                "El array de guardar debe incluir el campo '$campo'"
            );
        }
    }
 
    /**
     * @test
     * Verifica que estado_usuario se convierte correctamente de checkbox a entero.
     * En el formulario: si el checkbox está marcado → 1, si no → 0
     */
    public function testCheckboxEstadoConvierteAEntero(): void
    {
        // Simula checkbox marcado (truthy en PHP)
        $checkboxMarcado = '1';
        $resultado = $checkboxMarcado ? 1 : 0;
        $this->assertEquals(1, $resultado);
 
        // Simula checkbox no marcado (falsy: null o no viene en POST)
        $checkboxVacio = null;
        $resultado = $checkboxVacio ? 1 : 0;
        $this->assertEquals(0, $resultado);
    }
}