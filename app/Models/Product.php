<?php

namespace App\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    required: ['id', 'name', 'category_id', 'price', 'stock'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Product Name'),
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Product description'),
        new OA\Property(property: 'price', type: 'integer', example: 9999, description: 'Price in cents'),
        new OA\Property(property: 'stock', type: 'integer', example: 50),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category')
    ]
)]
class Product extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'products';

    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
