<?php

namespace App\GraphQL\Queries;

use App\Actions\Product\GetBestSellingProductsAction;

class BestSellingProductsQuery
{
    protected $getBestSelling;
    
    public function __construct(GetBestSellingProductsAction $getBestSelling)
    {
        $this->getBestSelling = $getBestSelling;
    }
    
    public function __invoke($_, array $args)
    {
        $limit = $args['limit'] ?? 10;
        return $this->getBestSelling->execute($limit);
    }
}
