<?php

namespace App\GraphQL\Queries;

use App\Actions\Product\SearchProductsAction;

class SearchProductsQuery
{
    protected $searchProducts;
    
    public function __construct(SearchProductsAction $searchProducts)
    {
        $this->searchProducts = $searchProducts;
    }
    
    public function __invoke($_, array $args)
    {
        return $this->searchProducts->execute($args['searchKey']);
    }
}
