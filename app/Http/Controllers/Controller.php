<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'MiniShop API',
    description: 'API documentation for the MiniShop e-commerce backend.'
)]
#[OA\Server(
    url: '/',
    description: 'Current server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Enter the token obtained from the login endpoint, without the "Bearer" prefix.'
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Error'),
        new OA\Property(property: 'errors', type: 'object', nullable: true),
    ]
)]
abstract class Controller
{
    //
}
