<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get('/','AuthController::login');$routes->get('login','AuthController::login');$routes->post('login','AuthController::attempt');$routes->post('logout','AuthController::logout');
$routes->group('',['filter'=>'auth'],static function(RouteCollection $routes):void{
 $routes->get('dashboard','DashboardController::index');
 $routes->get('notifications','NotificationsController::index');$routes->post('notifications/(:num)/read','NotificationsController::read/$1');$routes->post('notifications/read-all','NotificationsController::readAll');
 $routes->get('profile','ProfileController::index');$routes->post('profile','ProfileController::update');
 $routes->get('equipment','EquipmentController::index');$routes->get('equipment/(:num)','EquipmentController::show/$1');
 $routes->get('borrow-requests','BorrowRequestsController::index');$routes->get('borrow-requests/(:num)','BorrowRequestsController::show/$1');
 $routes->get('borrowings','BorrowingsController::index');$routes->get('borrow-history','BorrowingsController::history',['filter'=>'role:borrower']);
 $routes->group('',['filter'=>'role:borrower'],static function(RouteCollection $routes):void{
  $routes->get('borrow-requests/new','BorrowRequestsController::create');$routes->post('borrow-requests','BorrowRequestsController::store');$routes->post('borrow-requests/(:num)/cancel','BorrowRequestsController::cancel/$1');
 });
 $routes->group('',['filter'=>'role:admin,barangay_official'],static function(RouteCollection $routes):void{
  $routes->get('equipment/new','EquipmentController::create');$routes->post('equipment','EquipmentController::store');$routes->get('equipment/(:num)/edit','EquipmentController::edit/$1');$routes->post('equipment/(:num)','EquipmentController::update/$1');
  $routes->post('borrow-requests/(:num)/approve','BorrowRequestsController::approve/$1');$routes->post('borrow-requests/(:num)/reject','BorrowRequestsController::reject/$1');$routes->post('borrow-requests/(:num)/release','BorrowRequestsController::release/$1');$routes->post('borrow-requests/(:num)/return','BorrowRequestsController::recordReturn/$1');
  $routes->get('returns','ReturnsController::index');$routes->get('maintenance','MaintenanceController::index');$routes->post('maintenance','MaintenanceController::store');$routes->post('maintenance/(:num)/status','MaintenanceController::status/$1');
  $routes->get('reports','ReportsController::index');$routes->get('reports/print','ReportsController::print');$routes->get('reports/csv','ReportsController::csv');
 });
 $routes->group('',['filter'=>'role:admin'],static function(RouteCollection $routes):void{
  $routes->get('equipment-categories','EquipmentCategoriesController::index');$routes->post('equipment-categories','EquipmentCategoriesController::store');$routes->post('equipment-categories/(:num)','EquipmentCategoriesController::update/$1');$routes->post('equipment-categories/(:num)/status','EquipmentCategoriesController::status/$1');
  $routes->get('equipment-locations','EquipmentLocationsController::index');$routes->post('equipment-locations','EquipmentLocationsController::store');$routes->post('equipment-locations/(:num)','EquipmentLocationsController::update/$1');$routes->post('equipment-locations/(:num)/status','EquipmentLocationsController::status/$1');
  $routes->get('users','UsersController::index');$routes->post('users','UsersController::store');$routes->post('users/(:num)','UsersController::update/$1');$routes->post('users/(:num)/status','UsersController::status/$1');
  $routes->get('audit-logs','AuditLogsController::index');$routes->get('settings','SettingsController::index');$routes->post('settings','SettingsController::update');
 });
});
