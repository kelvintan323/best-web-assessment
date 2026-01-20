<?php

namespace App\Models;

use App\Abstracts\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $guarded = ['id'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
