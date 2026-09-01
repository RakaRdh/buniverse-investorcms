<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Root URL (Redirect atau Direct Login)
$routes->get('/', 'Auth::login');

// Authentication
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');
$routes->get('/logout', 'Auth::logout');
$routes->post('/logout', 'Auth::logout');

// Dashboard CMS Overview
$routes->get('/dashboard', 'Dashboard::index');

// Post Management
$routes->get('/post', 'Post::index');
$routes->get('/post/new', 'Post::new');
$routes->post('/post/save', 'Post::save');
$routes->get('/post/edit/(:num)', 'Post::edit/$1');
$routes->post('/post/update/(:num)', 'Post::update/$1');
$routes->get('/post/delete/(:num)', 'Post::delete/$1');
$routes->post('/post/bulk-action', 'Post::bulkAction');
$routes->get('/post/export', 'Post::export');

// Headline Management
$routes->get('/headline', 'Headline::index');
$routes->post('/headline/add', 'Headline::add');
$routes->get('/headline/remove/(:num)', 'Headline::remove/$1');
$routes->post('/headline/save', 'Headline::save');

// Media Library
$routes->get('/media', 'Media::index');
$routes->get('/media/new', 'Media::new');
$routes->post('/media/save', 'Media::save');
$routes->get('/media/edit/(:num)', 'Media::edit/$1');
$routes->post('/media/update/(:num)', 'Media::update/$1');
$routes->get('/media/delete/(:num)', 'Media::delete/$1');

// Files Management
$routes->get('/files', 'Files::index');
$routes->post('/files/upload', 'Files::upload');
$routes->get('/files/delete/(:num)', 'Files::delete/$1');

// Financial Embed Chart
$routes->get('/embed-chart', 'EmbedChart::index');
$routes->post('/embed-chart/save', 'EmbedChart::save');
$routes->get('/embed-chart/delete/(:num)', 'EmbedChart::delete/$1');

// Editorial Statistics
$routes->get('/statistic', 'Statistic::index');

// Mobile Apps Ecosystem
$routes->get('/apps-version', 'Apps::version');
$routes->post('/apps-version/save', 'Apps::saveVersion');

$routes->get('/apps-push-notif', 'Apps::pushNotif');
$routes->post('/apps-push-notif/create', 'Apps::createPushNotif');

$routes->get('/apps-feature-content', 'Apps::featureContent');
$routes->post('/apps-feature-content/save', 'Apps::saveFeatureContent');

// Category Taxonomy
$routes->get('/categories', 'Categories::index');
$routes->post('/categories/save', 'Categories::save');
$routes->get('/categories/delete/(:num)', 'Categories::delete/$1');

// User & Role Management
$routes->get('/user', 'User::index');
$routes->post('/user/save', 'User::save');
$routes->get('/user/delete/(:num)', 'User::delete/$1');
