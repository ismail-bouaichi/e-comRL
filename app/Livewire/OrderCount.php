<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Carbon\Carbon;

class OrderCount extends Component
{
    public $counter = 0;
    public $type = 'orders';
    public $percentageChange = 0;
    public $trend = 'increase';
    public $sparklineData = [];
    public $cacheTime = 3600; // Cache for 1 hour
    public $days = 30; // window size

    protected $listeners = [
        'refreshOrderMetric' => 'refreshData',
    ];

    public function mount($type = 'orders')
    {
        // Validate type parameter
        if (!in_array($type, ['revenue', 'customers', 'orders'])) {
            $type = 'orders';
        }
        $this->type = $type;
        
        // Check authorization
    if (!auth()->check() || optional(auth()->user()->role)->name !== 'admin') {
            abort(403, 'Unauthorized access to analytics');
        }

        $this->loadData();
    }

    public function loadData()
    {
        $now = Carbon::now();
        $periodStart = $now->copy()->subDays($this->days - 1)->startOfDay();
        $previousStart = $periodStart->copy()->subDays($this->days);
        $previousEnd = $periodStart->copy()->subSecond();

        $cacheKey = "order_count_{$this->type}_{$this->days}_" . $now->format('Y-m-d-H');

        $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($periodStart, $now, $previousStart, $previousEnd) {
            $currentValue = 0; $previousValue = 0; $dailyData = [];

            switch ($this->type) {
                case 'revenue':
                    $currentValue = OrderDetail::whereBetween('created_at', [$periodStart, $now])->sum('total_price');
                    $previousValue = OrderDetail::whereBetween('created_at', [$previousStart, $previousEnd])->sum('total_price');
                    $dailyData = OrderDetail::selectRaw('DATE(created_at) as d, SUM(total_price) as v')
                        ->whereBetween('created_at', [$periodStart, $now])
                        ->groupBy('d')->pluck('v', 'd')->toArray();
                    break;
                case 'customers':
                    $currentValue = Order::whereBetween('created_at', [$periodStart, $now])
                        ->distinct('customer_id')->count('customer_id');
                    $previousValue = Order::whereBetween('created_at', [$previousStart, $previousEnd])
                        ->distinct('customer_id')->count('customer_id');
                    $dailyData = Order::selectRaw('DATE(created_at) as d, COUNT(DISTINCT customer_id) as v')
                        ->whereBetween('created_at', [$periodStart, $now])
                        ->groupBy('d')->pluck('v', 'd')->toArray();
                    break;
                case 'orders':
                default:
                    $currentValue = Order::whereBetween('created_at', [$periodStart, $now])->count();
                    $previousValue = Order::whereBetween('created_at', [$previousStart, $previousEnd])->count();
                    $dailyData = Order::selectRaw('DATE(created_at) as d, COUNT(*) as v')
                        ->whereBetween('created_at', [$periodStart, $now])
                        ->groupBy('d')->pluck('v', 'd')->toArray();
            }

            // Build sparkline array
            $spark = [];
            for ($i = $this->days - 1; $i >= 0; $i--) {
                $dayKey = $now->copy()->subDays($i)->format('Y-m-d');
                $spark[] = (float) ($dailyData[$dayKey] ?? 0);
            }

            // Percentage change
            if ($previousValue > 0) {
                $percentageChange = (($currentValue - $previousValue) / $previousValue) * 100;
            } elseif ($currentValue > 0) {
                $percentageChange = 100; // From 0 to something
            } else {
                $percentageChange = 0;
            }

            return [
                'current' => $currentValue,
                'previous' => $previousValue,
                'percentageChange' => $percentageChange,
                'trend' => $currentValue >= $previousValue ? 'increase' : 'decrease',
                'spark' => $spark,
            ];
        });

        $this->counter = $data['current'];
        $this->percentageChange = $data['percentageChange'];
        $this->trend = $data['trend'];
        $this->sparklineData = $data['spark'];
    }

    public function refreshData()
    {
        $this->loadData();
    }

    public function generateSparklinePoints()
    {
        $width = 120;
        $height = 40;
        $points = [];
        
        $max = max($this->sparklineData);
        $min = min($this->sparklineData);
        
        // Handle case where all values are the same
        $range = max(0.1, $max - $min); // Prevent division by zero
        $step = $width / max(1, count($this->sparklineData) - 1); // Prevent division by zero
        
        foreach ($this->sparklineData as $index => $value) {
            $x = $index * $step;
            $y = $height - (($value - $min) / $range) * $height;
            $points[] = "$x,$y";
        }
        
        return !empty($points) ? implode(' ', $points) : "0,{$height} {$width},{$height}";
    }

    public function formattedCounter()
    {
        // Format large numbers as thousands (e.g., 2500 as 2.5k)
        if ($this->counter >= 1000) {
            return number_format($this->counter / 1000, 1) . 'k';
        }
        
        // Format numbers less than 1000 with no decimal places
        return number_format($this->counter);
    }

    public function render()
    {
        return view('livewire.order-count');
    }
}