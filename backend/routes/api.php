<?php

use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\RecipeController;
use Illuminate\Support\Facades\Route;

/*
| API v1. Todas las rutas requieren bearer token de un api_client activo
| (middleware "client"). Ver App\Http\Middleware\AuthenticateApiClient.
*/

foreach (glob(__DIR__ . '/api/*.php') as $routeFile) {
    require $routeFile;
}