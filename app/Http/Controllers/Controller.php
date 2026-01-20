<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Product Management API',
    description: 'RESTful API for Product CRUD operations with admin authentication'
)]
#[OA\Server(url: '/api', description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Tag(name: 'Authentication', description: 'Admin authentication endpoints')]
#[OA\Tag(name: 'Products', description: 'Product management endpoints')]
#[OA\Tag(name: 'Categories', description: 'Category endpoints')]
class Controller extends BaseController
{
    public function response($data = [], $message = '', $code = '', $statusCode = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'code' => $code,
        ], $statusCode);
    }
}
