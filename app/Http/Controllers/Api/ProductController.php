<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Image;
use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Actions\Product\CalculateProductDiscountAction;
use App\Actions\Product\GetBestSellingProductsAction;
use App\Actions\Product\SearchProductsAction;
use App\Actions\Product\GetProductDetailsAction;


class ProductController extends Controller
{
  
    
    public function index(Request $request)
    {
       
        $sortOption = $request->query('sort', 'all');
        $page = $request->query('page', 1);
        $selectedCategories = json_decode($request->query('selectedCategories', '[]'));
        $selectedBrands = json_decode($request->query('selectedBrands', '[]'));
        $minPrice = $request->query('min_price', 0);
        $maxPrice = $request->query('max_price', 1000);
        $searchKey = $request->query('searchKey', '');
    
        // Generate cache key
        $cacheKey = "products_{$page}_{$sortOption}_" . md5(
            implode('_', $selectedCategories) . "_" .
            implode('_', $selectedBrands) . "_{$minPrice}_{$maxPrice}_{$searchKey}"
        );
    
        $products = Cache::remember($cacheKey, 600, function() use (
            $sortOption, $page, $selectedCategories, $selectedBrands, $minPrice, $maxPrice, $searchKey
        ) {
            $query = Product::select('id', 'name', 'price', 'category_id', 'brand_id')
                ->with([
                    'images' => function ($q) {
                        $q->select('id', 'product_id', 'file_path')->limit(1);
                    },
                    'category:id,name',
                    'brand:id,name',
                ]);
    
            // Apply filters
            if (!empty($selectedCategories)) {
                $query->whereHas('category', function($q) use ($selectedCategories) {
                    $q->whereIn('name', $selectedCategories);
                });
            }
    
            if (!empty($selectedBrands)) {
                $query->whereHas('brand', function($q) use ($selectedBrands) {
                    $q->whereIn('name', $selectedBrands);
                });
            }
    
            $query->whereBetween('price', [$minPrice, $maxPrice]);
    
            if (!empty($searchKey)) {
                $query->where('name', 'like', '%' . $searchKey . '%');
            }
    
            // Apply sorting
            switch ($sortOption) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    break;
            }
    
            return $query->paginate(9, ['*'], 'page', $page);
        });
    
        // Transform products to include discount information
        $discountAction = new CalculateProductDiscountAction();
        $products->getCollection()->transform(function ($product) use ($discountAction) {
            return $discountAction->execute($product);
        });
    
        return response()->json($products);
    
       
    }
    
    
    public function category()
    {
        try {
            $categories = Cache::remember('categories_with_products', 600, function() {
                return Category::query()
                    ->select([
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
    
            return response()->json($categories);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch categories'], 500);
        }
    }
    
    public function brand()
    {
        try {
            $brands = Cache::remember('brands_with_products', 600, function() {
                return Brand::query()
                    ->select([
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
    
            return response()->json($brands);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch brands'], 500);
        }
    }

    public function bestSellingProduct(GetBestSellingProductsAction $getBestSelling)
    {
        try {
            $products = $getBestSelling->execute(10);
            
            return response()->json([
                'data' => $products,
                'cached_at' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('BestSelling Products Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Server Error',
                'message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

   
    public function selectProductBestRating(Request $request)
    {
        $products = Product::with(['images:id,product_id,file_path', 'category:id,name', 'brand:id,name'])
                            ->withAvg('ratings', 'rating') // Assuming 'ratings' relationship
                            ->orderByDesc('ratings_avg_rating')
                            ->paginate(20);
    
        return response()->json($products);
    }

    public function search(Request $request, SearchProductsAction $searchProducts)
    {
        try {
            $key = $request->searchKey;
            $products = $searchProducts->execute($key);
            
            return response()->json($products);
        } catch (\Exception $exception) {
            return response(['message' => $exception->getMessage()], 400);
        }
    }



    public function show($id, GetProductDetailsAction $getProductDetails)
    {
        try {
            $result = $getProductDetails->execute($id);
            return response()->json($result);
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Product not found' ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
  
}
