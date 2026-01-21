<?php

namespace App\Http\Controllers;

use App\Models\Category;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/categories',
        summary: 'List all categories',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of categories',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(
                                property: 'categories',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Category')
                            ),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return $this->response(['categories' => $categories, 'etes' => '123']);
    }
}
