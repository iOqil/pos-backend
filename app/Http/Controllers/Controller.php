<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", description: "API documentation for the POS system", title: "Universal POS API Documentation")]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: "API Server")]
#[OA\SecurityScheme(securityScheme: 'sanctum', type: 'http', scheme: 'bearer')]
abstract class Controller
{
    //
}
