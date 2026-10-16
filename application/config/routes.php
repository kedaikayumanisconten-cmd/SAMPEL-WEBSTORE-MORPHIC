<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
|--------------------------------------------------------------------------
| Public Storefront
|--------------------------------------------------------------------------
*/
$route['products']            = 'shop/index';
$route['category/(:any)']     = 'shop/index/0/$1';
$route['product/(:any)']      = 'shop/detail/$1';
$route['search']              = 'shop/index';

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
$route['login']               = 'auth/login';
$route['register']            = 'auth/register';
$route['logout']              = 'auth/logout';

/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/
$route['cart']                = 'cart/index';
$route['cart/add/(:num)']     = 'cart/add/$1';
$route['cart/remove/(:num)']  = 'cart/remove/$1';
$route['cart/clear']          = 'cart/clear';

/*
|--------------------------------------------------------------------------
| Checkout & Payment
|--------------------------------------------------------------------------
*/
$route['checkout']                 = 'checkout/index';
$route['checkout/place']           = 'checkout/place';
$route['checkout/success/(:num)']  = 'checkout/success/$1';
$route['checkout/proof/(:num)']    = 'checkout/proof/$1';

/*
|--------------------------------------------------------------------------
| Customer Account & Digital Library
|--------------------------------------------------------------------------
*/
$route['account']             = 'account/index';
$route['account/profile']     = 'account/profile';
$route['account/orders']      = 'account/orders';
$route['account/order/(:num)'] = 'account/order/$1';
$route['library']             = 'account/library';
$route['license/(:num)']      = 'account/license/$1';
$route['download/(:any)']     = 'download/index/$1';

/*
|--------------------------------------------------------------------------
| Admin Panel (directory: application/controllers/admin/)
|--------------------------------------------------------------------------
*/
$route['admin']               = 'admin/dashboard';
$route['admin/login']         = 'auth/login';
