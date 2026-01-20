<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'is_enabled' => 'boolean',
        ]);

        $product = Product::create($validated);

        return $this->response(['product' => $product->load('category')], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'is_enabled' => 'boolean',
        ]);

        $product->update($validated);

        return $this->response(['product' => $product->load('category')]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->response(['message' => 'Product deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        Product::whereIn('id', $validated['ids'])->delete();

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
