<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use App\Http\Requests\BulkDeleteProductRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
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

    public function show(Product $product)
    {
        return $this->response(['product' => $product->load('category')]);
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        return $this->response(
            data: ['product' => $product->load('category')],
            statusCode: 201
        );
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return $this->response(['product' => $product->load('category')]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->response(['message' => 'Product deleted successfully']);
    }

    public function bulkDestroy(BulkDeleteProductRequest $request)
    {
        Product::whereIn('id', $request->validated()['ids'])->delete();

        return $this->response(['message' => 'Products deleted successfully']);
    }

    public function export(Request $request)
    {
        $status = $request->status;
        $categoryId = $request->category_id;

        $filename = 'products_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new ProductExport($status, $categoryId), $filename);
    }
}
