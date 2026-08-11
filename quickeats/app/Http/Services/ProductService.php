<?php

namespace App\Http\Services;

use App\Models\Product;

class ProductService
{
    public function getAllProducts()
    {
        return Product::all();
    }
    
    public function createProduct(array $data)
    {
        return Product::create($data);
    }
}
