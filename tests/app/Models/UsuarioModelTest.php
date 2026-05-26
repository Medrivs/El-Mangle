<?php
namespace App\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\UsuarioModel;

class UsuarioModelTest extends CIUnitTestCase
{

    public function testUsuarioModel()
    {
        // 1. Preparamos el entorno instanciando la clase
        $modelo = new UsuarioModel();

        // 2. Afirmamos (Assert) que la tabla configurada sea la correcta
        $this->assertEquals('Usuarios', $modelo->table);

        // 3. Afirmamos que la llave primaria es correcta
        $this->assertEquals('id_usuario', $modelo->primaryKey);

        // 4. Afirmamos que el campo 'telefono' está permitido para evitar hackeos
        $this->assertContains('telefono', $modelo->allowedFields);
    }
}