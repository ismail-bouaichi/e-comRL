<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$resolver = new \App\GraphQL\Queries\ProductsQuery(
    new \App\Actions\Product\CalculateProductDiscountAction()
);

$result = $resolver->__invoke(null, [
    'page' => 1,
    'first' => 9,
]);

echo "Products count: " . count($result['data']) . PHP_EOL;
echo "Total: " . $result['paginatorInfo']['total'] . PHP_EOL;
echo "Has items: " . (!empty($result['data']) ? 'yes' : 'no') . PHP_EOL;

if (!empty($result['data'])) {
    $first = $result['data'][0];
    echo "First product ID: " . $first->id . PHP_EOL;
    echo "First product name: " . $first->name . PHP_EOL;
    echo "Is null? " . ($first === null ? 'yes' : 'no') . PHP_EOL;
}
