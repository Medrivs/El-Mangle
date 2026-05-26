<?php

namespace Config;

$routes = Services::routes();

if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * @var RouteCollection $routes
 */

// --- RUTAS DE USUARIOS ---
$routes->get('usuarios', 'Usuarios::index');
$routes->get('usuarios/agregar', 'Usuarios::agregar');
$routes->post('usuarios/guardar', 'Usuarios::guardar');
$routes->get('usuarios/editar/(:num)', 'Usuarios::editar/$1');
$routes->post('usuarios/actualizar/(:num)', 'Usuarios::actualizar/$1');
$routes->get('usuarios/eliminar/(:num)', 'Usuarios::eliminar/$1');

// --- RUTAS DE MATERIA PRIMA ---
$routes->get('materiaprima', 'MateriaPrima::index');
$routes->get('materiaprima/agregar', 'MateriaPrima::agregar');
$routes->post('materiaprima/guardar', 'MateriaPrima::guardar');
$routes->get('materiaprima/editar/(:num)', 'MateriaPrima::editar/$1');       // Carga la vista de edición
$routes->post('materiaprima/actualizar/(:num)', 'MateriaPrima::actualizar/$1'); // Guarda los cambios
$routes->get('materiaprima/eliminar/(:num)', 'MateriaPrima::eliminar/$1');

// Ruta principal
$routes->get('/', 'Usuarios::index');