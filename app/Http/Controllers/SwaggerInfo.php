<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "UniStore API",
    version: "1.0.0",
    description: "API REST UniStore - BIT"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Serveur local"
)]
class SwaggerInfo
{
}
