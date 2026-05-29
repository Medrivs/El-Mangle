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
$routes->get('logout', 'Login::logout');
$routes->get('login/logout', 'Login::logout');

// --- RUTAS DEL PUNTO DE VENTA (POS) ---
// --- RUTAS DEL PUNTO DE VENTA (POS) ---
$routes->get('pos', 'Pos::index');
$routes->get('pos/mesa/(:num)', 'Pos::mesa/$1');
$routes->get('pos/filtrar/(:num)/(:num)', 'Pos::filtrar/$1/$2');
$routes->get('pos/filtrar/(:num)/(:num)/(:any)', 'Pos::filtrar/$1/$2/$3'); // RUTA PARA LAS PESTAÑAS
$routes->get('pos/seleccionar/(:num)/(:num)', 'Pos::seleccionar_platillo/$1/$2');
$routes->get('pos/ver_comanda/(:num)', 'Pos::ver_comanda/$1');
$routes->get('pos/imprimir_cuenta/(:num)', 'Pos::imprimir_cuenta/$1');

// app/Config/Routes.php
$routes->post('pos/enviar_orden', 'Pos::enviar_orden');

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

// --- RUTAS DE CHEF ---
$routes->get('chef/dashboard', 'Chef::dashboard');
$routes->get('chef/marcar_listo/(:num)', 'Chef::marcar_listo/$1');
$routes->get('chef/cambiar_estado/(:num)/(:any)', 'Chef::cambiar_estado/$1/$2');
$routes->get('chef/toggle_advertencia/(:num)', 'Chef::toggle_advertencia/$1');
$routes->get('chef/toggle_bloqueo/(:num)', 'Chef::toggle_bloqueo/$1');

// =======================================================
// RUTAS DEL MÓDULO DE CAJA
// =======================================================
$routes->get('caja', 'Caja::index');
$routes->get('caja/index/(:num)', 'Caja::index/$1');
$routes->post('caja/liquidar', 'Caja::liquidar');
$routes->post('caja/corte_caja', 'Caja::corte_caja');

