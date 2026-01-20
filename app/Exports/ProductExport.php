<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductExport implements FromQuery, WithHeadings, WithMapping
{
    protected $status;

    protected $categoryId;

    public function __construct($status = null, $categoryId = null)
    {
        $this->status = $status;
        $this->categoryId = $categoryId;
    }

    public function query()
    {
        $query = Product::with('category');

        if ($this->status !== null && $this->status !== '') {
            $query->where('is_enabled', $this->status);
        }

        if ($this->categoryId !== null && $this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Category',
            'Description',
            'Price',
            'Stock',
            'Status',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->category?->name,
            $product->description,
            number_format($product->price / 100, 2),
            $product->stock,
            $product->is_enabled ? 'Active' : 'Inactive',
            $product->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
