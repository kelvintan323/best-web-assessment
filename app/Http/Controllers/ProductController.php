<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use App\Http\Requests\BulkDeleteProductRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/products',
        summary: 'List all products',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status (1=enabled, 0=disabled)', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filter by category ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (default: 10)', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort_key', in: 'query', description: 'Sort by field', schema: new OA\Schema(type: 'string', enum: ['name', 'price', 'stock', 'is_enabled', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', description: 'Sort order', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of products with pagination',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'products', properties: [
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'total', type: 'integer', example: 100)
                            ], type: 'object')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_enabled', $request->status);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id !== '') {
            $query->where('category_id', $request->category_id);
        }

        // Sorting
        if ($request->has('sort_key') && $request->sort_key) {
            $sortKey = $request->sort_key;
            $sortOrder = $request->sort_order ?? 'desc';

            $allowedSortKeys = ['name', 'price', 'stock', 'is_enabled', 'created_at'];
            if (in_array($sortKey, $allowedSortKeys)) {
                $query->orderBy($sortKey, $sortOrder === 'asc' ? 'asc' : 'desc');
            }
        }

        $products = $query->latest()->paginate($request->per_page ?? 10);

        return $this->response(['products' => $products]);
    }

    #[OA\Get(
        path: '/products/{id}',
        summary: 'Get a single product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Product ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'product', ref: '#/components/schemas/Product')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function show(Product $product)
    {
        return $this->response(['product' => $product->load('category')]);
    }

    #[OA\Post(
        path: '/products',
        summary: 'Create a new product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id', 'price', 'stock'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Product Name'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Product description'),
                    new OA\Property(property: 'price', type: 'integer', minimum: 0, example: 9999, description: 'Price in cents'),
                    new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 50),
                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'product', ref: '#/components/schemas/Product')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        return $this->response(
            data: ['product' => $product->load('category')],
            statusCode: 201
        );
    }

    #[OA\Put(
        path: '/products/{id}',
        summary: 'Update a product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Product ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id', 'price', 'stock'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Updated Product Name'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                    new OA\Property(property: 'price', type: 'integer', minimum: 0, example: 12999),
                    new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 25),
                    new OA\Property(property: 'is_enabled', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'product', ref: '#/components/schemas/Product')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return $this->response(['product' => $product->load('category')]);
    }

    #[OA\Delete(
        path: '/products/{id}',
        summary: 'Delete a product (soft delete)',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Product ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'Product deleted successfully')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function destroy(Product $product)
    {
        $product->delete();

        return $this->response(['message' => 'Product deleted successfully']);
    }

    #[OA\Post(
        path: '/products/bulk-delete',
        summary: 'Bulk delete products',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'Products deleted successfully')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function bulkDestroy(BulkDeleteProductRequest $request)
    {
        Product::whereIn('id', $request->validated()['ids'])->delete();

        return $this->response(['message' => 'Products deleted successfully']);
    }

    #[OA\Get(
        path: '/products/export',
        summary: 'Export products to Excel',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filter by category ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Excel file download',
                content: new OA\MediaType(
                    mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function export(Request $request)
    {
        $status = $request->status;
        $categoryId = $request->category_id;

        $filename = 'products_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new ProductExport($status, $categoryId), $filename);
    }
}
