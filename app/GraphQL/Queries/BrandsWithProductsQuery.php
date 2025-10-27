<?php

namespace App\GraphQL\Queries;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BrandsWithProductsQuery
{
    public function __invoke()
    {
        return Cache::remember('graphql_brands_with_products', 600, function() {
            return Brand::select([
                    'brands.id',
                    'brands.name',
                    'brands.logo_path',
                    DB::raw('COUNT(products.id) as products_count')
                ])
                ->leftJoin('products', 'brands.id', '=', 'products.brand_id')
                ->groupBy('brands.id', 'brands.name', 'brands.logo_path')
                ->having('products_count', '>', 0)
                ->get();
        });
    }
}
