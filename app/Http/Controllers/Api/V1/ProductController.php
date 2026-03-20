<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query();

        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $products = $query->paginate($request->input('per_page', 15));

        return ProductResource::collection($products);
    }
}
