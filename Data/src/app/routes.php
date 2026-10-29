<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;


$app->group('/api', function (RouteCollectorProxy $api) {
    $api->group('/productos', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Producto::class . ':read');
        $endpoint->post('', Producto::class . ':create');
        $endpoint->put('/{id}', Producto::class . ':update');
        $endpoint->delete('/{id}', Producto::class . ':delete');
    });

});
