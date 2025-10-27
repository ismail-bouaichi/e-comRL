<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$roles = DB::table('roles')->get(['id', 'name']);
echo json_encode($roles, JSON_PRETTY_PRINT);
