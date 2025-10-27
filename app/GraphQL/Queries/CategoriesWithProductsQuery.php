<?php

namespace App\GraphQL\Queries;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoriesWithProductsQuery
{
    public function __invoke()
    {
        return Cache::remember('graphql_categories_with_products', 600, function() {
            return Category::select([
                    'categories.id',
                    'categories.name',
                    'categories.icon',
                    DB::raw('COUNT(products.id) as products_count')
                ])
                ->leftJoin('products', 'categories.id', '=', 'products.category_id')
                ->groupBy('categories.id', 'categories.name', 'categories.icon')
                ->having('products_count', '>', 0)
                ->get();
        });
    }
}
