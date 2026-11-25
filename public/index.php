<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = is_file(__DIR__.'/../vendor/autoload.php')
    ? dirname(__DIR__)
    : dirname(__DIR__, 3).'/apps/'.basename(dirname(__DIR__));

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

$app = require_once $basePath.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
