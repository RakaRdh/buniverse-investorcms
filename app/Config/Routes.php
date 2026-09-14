<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Root URL (Redirect atau Direct Login)
$routes->get('/', 'Auth::login');

// Authentication
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');
$routes->get('/auth/verify-code', 'Auth::verifyCode');
$routes->post('/auth/verify-code', 'Auth::verifyCode');
$routes->post('/auth/resend-code', 'Auth::resendCode');
$routes->get('/auth/forgot-password', 'Auth::forgotPassword');
$routes->post('/auth/forgot-password', 'Auth::sendResetLink');
$routes->get('/auth/reset-password', 'Auth::resetPassword');
$routes->post('/auth/reset-password', 'Auth::resetPassword');
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
$routes->match(['GET', 'POST'], '/post/delete/(:num)', 'Post::delete/$1');
$routes->post('/post/bulk-action', 'Post::bulkAction');
$routes->get('/post/export', 'Post::export');
$routes->get('/post/history', 'Post::history');
$routes->get('/post/history/(:num)', 'Post::historyDetail/$1');
$routes->get('/post/preview/(:num)', 'PostPreview::show/$1');
$routes->get('/post/preview', 'PostPreview::fromSessionOrForm');
$routes->post('/post/preview', 'PostPreview::fromForm');

// Keyword Management (Master Topics & SEO)
$routes->get('/keyword', 'Keyword::index');
$routes->post('/keyword/save', 'Keyword::save');
$routes->get('/keyword/delete/(:num)', 'Keyword::delete/$1');

// Headline Management (Master, Admin, Editor)
$routes->get('/headline', 'Headline::index', ['filter' => 'role:master,admin,editor']);
$routes->post('/headline/add', 'Headline::add', ['filter' => 'role:master,admin,editor']);
$routes->get('/headline/remove/(:num)', 'Headline::remove/$1', ['filter' => 'role:master,admin,editor']);
$routes->post('/headline/save', 'Headline::save', ['filter' => 'role:master,admin,editor']);
$routes->post('/headline/reorder', 'Headline::reorder', ['filter' => 'role:master,admin,editor']);

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

// Financial Embed Chart (Template & Custom Data)
$routes->get('/embed-chart', 'EmbedChart::index', ['filter' => 'role:master,admin,editor']);
$routes->get('/embed-chart/template', 'EmbedChart::template', ['filter' => 'role:master,admin,editor']);
$routes->get('/embed-chart/custom', 'EmbedChart::custom', ['filter' => 'role:master,admin,editor']);
$routes->get('/embed-chart/custom/create', 'EmbedChart::createCustom', ['filter' => 'role:master,admin,editor']);
$routes->post('/embed-chart/custom/save', 'EmbedChart::saveCustom', ['filter' => 'role:master,admin,editor']);
$routes->get('/embed-chart/custom/edit/(:num)', 'EmbedChart::editCustom/$1', ['filter' => 'role:master,admin,editor']);
$routes->post('/embed-chart/custom/update/(:num)', 'EmbedChart::updateCustom/$1', ['filter' => 'role:master,admin,editor']);
$routes->post('/embed-chart/save', 'EmbedChart::save', ['filter' => 'role:master,admin,editor']);
$routes->get('/embed-chart/delete/(:num)', 'EmbedChart::delete/$1', ['filter' => 'role:master,admin,editor']);

// Editorial Statistics & Reporting
$routes->get('/statistic', 'Statistic::index');
$routes->get('/statistic/report', 'StatisticReport::index');
$routes->get('/statistic/hourly-heatmap', 'StatisticReport::hourlyHeatmap');

// Kanal & Sub-Kanal Taxonomy & Performance (Master, Admin)
$routes->get('/kanal', 'Kanal::master', ['filter' => 'role:master,admin']);
$routes->get('/kanal/master', 'Kanal::master', ['filter' => 'role:master,admin']);
$routes->post('/kanal/master/save', 'Kanal::saveMaster', ['filter' => 'role:master,admin']);
$routes->get('/kanal/sub', 'Kanal::subKanal', ['filter' => 'role:master,admin']);
$routes->post('/kanal/sub/save', 'Kanal::saveSub', ['filter' => 'role:master,admin']);
$routes->get('/kanal/stats', 'Kanal::stats', ['filter' => 'role:master,admin']);

// Mobile Apps Ecosystem (Master, Admin)
$routes->get('/apps-version', 'Apps::version', ['filter' => 'role:master,admin']);
$routes->post('/apps-version/save', 'Apps::saveVersion', ['filter' => 'role:master,admin']);
$routes->get('/apps-version/delete/(:num)', 'Apps::deleteVersion/$1', ['filter' => 'role:master,admin']);

$routes->get('/apps-push-notif', 'Apps::pushNotif', ['filter' => 'role:master,admin']);
$routes->post('/apps-push-notif/create', 'Apps::createPushNotif', ['filter' => 'role:master,admin']);

$routes->get('/apps-feature-content', 'Apps::featureContent', ['filter' => 'role:master,admin']);
$routes->post('/apps-feature-content/save', 'Apps::saveFeatureContent', ['filter' => 'role:master,admin']);
$routes->get('/apps-feature-content/delete/(:num)', 'Apps::deleteFeature/$1', ['filter' => 'role:master,admin']);

// Category Taxonomy (Legacy Redirect)
$routes->get('/categories', 'Kanal::master', ['filter' => 'role:master,admin']);
$routes->post('/categories/save', 'Categories::save', ['filter' => 'role:master,admin']);
$routes->get('/categories/delete/(:num)', 'Categories::delete/$1', ['filter' => 'role:master,admin']);

// Member / Client Management (Master, Admin) - Table: client_account_entity
$routes->get('/member', 'Member::index', ['filter' => 'role:master,admin']);
$routes->post('/member/save', 'Member::save', ['filter' => 'role:master,admin']);
$routes->get('/member/delete/(:num)', 'Member::delete/$1', ['filter' => 'role:master,admin']);

// User & Role Management (Master, Admin)
$routes->get('/user', 'User::index', ['filter' => 'role:master,admin']);
$routes->post('/user/save', 'User::save', ['filter' => 'role:master,admin']);
$routes->get('/user/delete/(:num)', 'User::delete/$1', ['filter' => 'role:master,admin']);
$routes->post('/user/toggle-status/(:num)', 'User::toggleStatus/$1', ['filter' => 'role:master,admin']);
$routes->get('/user/permissions/(:num)', 'User::getPermissions/$1', ['filter' => 'role:master,admin']);
$routes->post('/user/permissions/save', 'User::savePermissions', ['filter' => 'role:master,admin']);
$routes->get('/api/user/check-email', 'User::checkEmail');

// User Personal Profile (All authenticated users)
$routes->get('/user/profile', 'User::profile');
$routes->post('/user/profile/update', 'User::updateProfile');
$routes->post('/user/profile/change-password', 'User::changePasswordProfile');
$routes->post('/user/profile/request-reset-email', 'User::requestResetEmail');

// AI Editorial Assistant (Gemini)
$routes->post('/api/ai/title', 'AiAssistant::generateTitle');
$routes->post('/api/ai/summary', 'AiAssistant::generateSummary');
$routes->post('/api/ai/tags', 'AiAssistant::generateTags');
$routes->post('/api/ai/points', 'AiAssistant::generatePoints');
$routes->post('/api/ai/scrape', 'AiAssistant::scrape');

// Editorial Auto-Save, Standardized Image Upload & Edit Lock
$routes->post('/api/autosave', 'Api\PostApi::autosave');
$routes->post('/api/upload-image', 'Api\PostApi::uploadImage');
$routes->post('/api/post/release-lock/(:num)', 'Api\PostApi::releaseLock/$1');
$routes->post('/api/post/force-unlock/(:num)', 'Api\PostApi::forceUnlock/$1');

// Tag Real-Time Autocomplete
$routes->get('/api/tags/search', 'Post::searchTags');

// Stock Ticker Real-Time Autocomplete (IDX)
$routes->get('/api/stocks/search', 'Post::searchStocks');

// Public Reader API Endpoints (Frontend Integration)
$routes->get('/api/articles', 'Api\ArticleApi::index');
$routes->get('/api/articles/(:any)/related', 'Api\ArticleApi::related/$1');
$routes->get('/api/articles/(:any)', 'Api\ArticleApi::show/$1');
$routes->get('/api/categories', 'Api\ArticleApi::categories');
$routes->get('/api/search', 'Api\ArticleApi::search');

$routes->get('/api/market/ticker', 'Api\MarketApi::ticker');
$routes->get('/api/market/movers', 'Api\MarketApi::movers');

$routes->post('/api/auth/client-login', 'Api\AuthApi::clientLogin');
$routes->post('/api/auth/client-register', 'Api\AuthApi::clientRegister');
