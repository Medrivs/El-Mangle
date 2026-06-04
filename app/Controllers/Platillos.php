<?php

namespace App\Controllers;

use App\Models\PlatilloModel;
use App\Models\CategoriaModel;
use App\Models\MateriaPrimaModel;
use App\Models\RecetaModel;

class Platillos extends BaseController
{
    protected $platilloModel;
    protected $categoriaModel;
    protected $materiaPrimaModel;
    protected $recetaModel;

    // inyecta los modelos para permitir pruebas unitarias con mocks
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->platilloModel = new PlatilloModel();
        $this->categoriaModel = new CategoriaModel();
        $this->materiaPrimaModel = new MateriaPrimaModel();
        $this->recetaModel = new RecetaModel();
    }

    // paso 1 del rf 2 1 ingresa al modulo de platillos
    public function index()
    {
        // paso 2 y 3 del rf 2 1 peticion a la base de datos y agrupacion
        $data['platillos'] = $this->platilloModel->select('platillo.*, categoria.nombre_categoria')
            ->join('categoria', 'categoria.id_categoria = platillo.id_categoria', 'left')
            ->orderBy('categoria.nombre_categoria', 'ASC')
            ->findAll();
        
        // paso 4 del rf 2 1 muestra informacion
        return view('admin/platillos', $data);
    }

    // paso 2 del rf 2 2 despliega el formulario
    public function agregar()
    {
        $data = [
            'categorias'    => $this->categoriaModel->findAll(),
            'subcategorias' => $this->platilloModel->select('subcategoria')->distinct()->orderBy('subcategoria', 'ASC')->findAll(),
            'materias'      => $this->materiaPrimaModel->where('estado_materia', 1)->findAll()
        ];
        return view('admin/platillos_agregar', $data);
    }

    // recibe la informacion para guardar
    public function guardar()
    {
        $post = $this->request->getPost();
        
        // excepcion 2 del rf 2 2 si se intenta guardar con campos vacios
        if (empty($post['nombre_platillo']) || empty($post['precio_venta']) || empty($post['id_categoria']) || empty($post['subcategoria'])) {
            return redirect()->back()->with('error', 'la informacion obligatoria esta incompleta');
        }

        $dataPlatillo = [
            'nombre_platillo' => $post['nombre_platillo'],
            'descripcion'     => $post['descripcion'] ?? '',
            'precio_venta'    => $post['precio_venta'],
            'id_categoria'    => $post['id_categoria'],
            'subcategoria'    => $post['subcategoria'],
            'imagen_url'      => $this->procesarImagen(),
            'disponible'      => 1
        ];

        // paso 5 del rf 2 2 peticion de insercion a la base de datos
        $this->platilloModel->save($dataPlatillo);
        $id_platillo = $this->platilloModel->getInsertID();

        // guardado de la receta iterando los arreglos del formulario
        $id_materias = $this->request->getPost('id_materia_prima');
        $cantidades = $this->request->getPost('cantidad_usada');

        if (!empty($id_materias) && !empty($cantidades)) {
            for ($i = 0; $i < count($id_materias); $i++) {
                if (!empty($id_materias[$i]) && !empty($cantidades[$i])) {
                    $this->recetaModel->save([
                        'id_platillo'      => $id_platillo,
                        'id_materia_prima' => $id_materias[$i],
                        'cantidad_usada'   => $cantidades[$i]
                    ]);
                }
            }
        }

        return redirect()->to(base_url('platillos'))->with('success', 'el nuevo platillo y su receta se almacenan en el catalogo');
    }

    // paso 2 del rf 2 3 despliega el formulario editor precargando
    public function editar($id)
    {
        $data = [
            'platillo'      => $this->platilloModel->find($id),
            'recetas'       => $this->recetaModel->where('id_platillo', $id)->findAll(),
            'categorias'    => $this->categoriaModel->findAll(),
            'subcategorias' => $this->platilloModel->select('subcategoria')->distinct()->orderBy('subcategoria', 'ASC')->findAll(),
            'materias'      => $this->materiaPrimaModel->where('estado_materia', 1)->findAll()
        ];
        
        return view('admin/platillos_editar', $data);
    }

    // recibe los datos para actualizar
    public function actualizar($id)
    {
        $post = $this->request->getPost();
        
        // excepcion 2 del rf 2 3 si se intenta guardar con campos vacios
        if (empty($post['nombre_platillo']) || empty($post['precio_venta']) || empty($post['id_categoria']) || empty($post['subcategoria'])) {
            return redirect()->back()->with('error', 'los datos obligatorios no pueden estar vacios');
        }

        $dataPlatillo = [
            'nombre_platillo' => $post['nombre_platillo'],
            'descripcion'     => $post['descripcion'] ?? '',
            'precio_venta'    => $post['precio_venta'],
            'id_categoria'    => $post['id_categoria'],
            'subcategoria'    => $post['subcategoria'],
            'imagen_url'      => $this->procesarImagen($post['imagen_actual'] ?? ''),
            'disponible'      => isset($post['disponible']) ? 1 : 0
        ];

        // paso 5 del rf 2 3 peticion de actualizacion a la tabla platillo
        $this->platilloModel->update($id, $dataPlatillo);

        // purga la receta anterior y guarda la nueva
        $this->recetaModel->where('id_platillo', $id)->delete();
        
        $id_materias = $this->request->getPost('id_materia_prima');
        $cantidades = $this->request->getPost('cantidad_usada');

        if (!empty($id_materias) && !empty($cantidades)) {
            for ($i = 0; $i < count($id_materias); $i++) {
                if (!empty($id_materias[$i]) && !empty($cantidades[$i])) {
                    $this->recetaModel->save([
                        'id_platillo'      => $id,
                        'id_materia_prima' => $id_materias[$i],
                        'cantidad_usada'   => $cantidades[$i]
                    ]);
                }
            }
        }

        return redirect()->to(base_url('platillos'))->with('success', 'la informacion se almacena permanentemente');
    }

    // solicita inhabilitacion del producto
    public function eliminar($id)
    {
        // paso 4 del rf 2 4 peticion de actualizacion para cambiar disponible a false
        $this->platilloModel->update($id, ['disponible' => 0]);
        
        // paso 5 del rf 2 4 muestra un mensaje informativo de exito
        return redirect()->to(base_url('platillos'))->with('success', 'exito de la operacion platillo inhabilitado');
    }

    // maneja la subida de archivos
    private function procesarImagen(string $ruta_actual = ''): string
    {
        $file = $this->request->getFile('imagen');
        
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move('uploads/platillos/', $newName);
            return 'uploads/platillos/' . $newName;
        }
        
        return $ruta_actual;
    }
}