<?php

namespace Config;

$routes = Services::routes();

if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Login::index'); // La ruta base ahora es el Login
$routes->post('login/ingresar', 'Login::ingresar');
$routes->get('login/salir', 'Login::salir');

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
$routes->get('materiaprima/editar/(:num)', 'MateriaPrima::editar/$1');
$routes->post('materiaprima/actualizar/(:num)', 'MateriaPrima::actualizar/$1');
$routes->get('materiaprima/eliminar/(:num)', 'MateriaPrima::eliminar/$1');

// --- RUTAS DE PLATILLOS ---
$routes->get('platillos', 'Platillos::index');
$routes->get('platillos/agregar', 'Platillos::agregar');
$routes->post('platillos/guardar', 'Platillos::guardar');
$routes->get('platillos/editar/(:num)', 'Platillos::editar/$1');
$routes->post('platillos/actualizar/(:num)', 'Platillos::actualizar/$1');
$routes->get('platillos/eliminar/(:num)', 'Platillos::eliminar/$1');
