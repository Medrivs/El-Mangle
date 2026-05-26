<?php

namespace Config;

$routes = Services::routes();

if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

// Rutas de Usuarios
$routes->get('usuarios', 'Usuarios::index');
$routes->get('usuarios/agregar', 'Usuarios::agregar');
$routes->post('usuarios/guardar', 'Usuarios::guardar');
$routes->get('usuarios/editar/(:num)', 'Usuarios::editar/$1'); // Carga vista edición
$routes->post('usuarios/actualizar/(:num)', 'Usuarios::actualizar/$1'); // Procesa cambios
$routes->get('usuarios/eliminar/(:num)', 'Usuarios::eliminar/$1');

$routes->get('/', 'Usuarios::index');