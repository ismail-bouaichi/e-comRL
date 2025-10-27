<?php

namespace App\Livewire\Create;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use App\Models\DeliveryWorker;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CreateOrder extends Component
{
    public $currentStep = 1;
    public $totalSteps = 3;
    public $steps = [
        1 => 'Customer Information',
        2 => 'Order Details',
        3 => 'Shipping & Review',
    ];

    public $orderData = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'customer_id' => null,
        'delivery_worker_id' => null,
        'status' => 'unpaid',
        'session_id' => '',
        'longitude' => null,
        'latitude' => null,
        'address' => '',
        'city' => '',
        'zip_code' => '',
    ];

    public $orderDetailsData = [
        [
            'product_id' => null,
            'quantity' => 1,
        ]
    ];

    // Collections for selects
    public $customerSearch = '';
    public $deliverySearch = '';
    public $productSearch = [];
    public $customerOptions = [];
    public $deliveryOptions = [];
    public $productOptions = [];
    public $selectedCustomerName = '';
    public $selectedDeliveryName = '';

    // Temporary coordinates from map (before saving)
    public $tempLatitude = null;
    public $tempLongitude = null;
    public $tempAddress = '';
    public $tempCity = '';
    public $tempZipCode = '';

    public $subtotal = 0;
    public $shipping = 0;
    public $shippingMethod = 'standard';
    public $shippingOptions = [
        'standard' => ['name' => 'Standard Delivery', 'cost' => 5.00, 'days' => '3-5 business days'],
        'express' => ['name' => 'Express Delivery', 'cost' => 15.00, 'days' => '1-2 business days'],
        'overnight' => ['name' => 'Overnight Delivery', 'cost' => 25.00, 'days' => 'Next business day'],
        'pickup' => ['name' => 'Store Pickup', 'cost' => 0.00, 'days' => 'Ready in 2 hours']
    ];
    public $grandTotal = 0;

    protected $rules = [
        'orderData.first_name' => 'required|string',
        'orderData.last_name' => 'required|string',
        'orderData.email' => 'required|email',
        'orderData.phone' => 'required|string',
        'orderData.customer_id' => 'required|integer|exists:users,id',
        'orderData.delivery_worker_id' => 'nullable|integer|exists:delivery_workers,id',
        'orderData.session_id' => 'nullable|string',
        'orderData.address' => 'required|string',
        'orderData.city' => 'required|string',
        'orderData.zip_code' => 'required|string',
        'orderDetailsData.*.product_id' => 'required|integer|exists:products,id',
        'orderDetailsData.*.quantity' => 'required|integer|min:1',
        'orderData.longitude' => 'nullable|numeric',
        'orderData.latitude' => 'nullable|numeric',
        'shippingMethod' => 'required|string|in:standard,express,overnight,pickup',
    ];

    protected $listeners = ['updateAddress'];

    public function updatedShippingMethod()
    {
        $this->shipping = $this->shippingOptions[$this->shippingMethod]['cost'] ?? 0;
        $this->recalculateTotals();
    }

    public function mount()
    {
        // Initialize search arrays with some default data
        Log::info('CreateOrder component mounting');
        
        // Set default shipping cost
        $this->shipping = $this->shippingOptions[$this->shippingMethod]['cost'];
        
        $customers = User::whereHas('role', function($q) {
                $q->where('name', 'customer');
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);
            
        Log::info('Found customers', ['count' => $customers->count()]);
            
        $this->customerOptions = $customers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            })
            ->toArray();
            
        $deliveryWorkers = DeliveryWorker::with('user')
            ->limit(10)
            ->get();
            
        Log::info('Found delivery workers', ['count' => $deliveryWorkers->count()]);
            
        $this->deliveryOptions = $deliveryWorkers->map(function($worker) {
                return [
                    'id' => $worker->id,
                    'name' => $worker->user->name ?? 'Unknown',
                    'email' => $worker->user->email ?? '',
                    'phone' => $worker->phone,
                    'vehicle_type' => $worker->vehicle_type,
                    'status' => $worker->status
                ];
            })
            ->toArray();
    }

    public function updateAddress(...$args)
    {
        Log::info('UpdateAddress called with args:', $args);
        
        try {
            // Handle different argument formats
            if (count($args) === 1 && is_array($args[0])) {
                // If passed as a single array (from Alpine.js or event dispatch)
                $data = $args[0];
                $address = $data['address'] ?? '';
                $city = $data['city'] ?? '';
                $zipCode = $data['zipCode'] ?? '';
                $longitude = $data['longitude'] ?? null;
                $latitude = $data['latitude'] ?? null;
            } else {
                // If passed as individual parameters
                $address = $args[0] ?? '';
                $city = $args[1] ?? '';
                $zipCode = $args[2] ?? '';
                $longitude = $args[3] ?? null;
                $latitude = $args[4] ?? null;
            }
            
            $this->tempAddress = $address;
            $this->tempCity = $city;
            $this->tempZipCode = $zipCode;
            $this->tempLongitude = $longitude;
            $this->tempLatitude = $latitude;
            
            Log::info('Address fields updated:', [
                'address' => $this->tempAddress,
                'city' => $this->tempCity,
                'zip' => $this->tempZipCode,
                'longitude' => $this->tempLongitude,
                'latitude' => $this->tempLatitude
            ]);
            
            // Force immediate UI update
            $this->dispatch('$refresh');
            
        } catch (\Exception $e) {
            Log::error('UpdateAddress error:', ['message' => $e->getMessage(), 'args' => $args]);
        }
    }

    public function saveLocationFromMap()
    {
        // Save the temporary coordinates to the actual order data
        $this->orderData['address'] = $this->tempAddress;
        $this->orderData['city'] = $this->tempCity;
        $this->orderData['zip_code'] = $this->tempZipCode;
        $this->orderData['longitude'] = $this->tempLongitude;
        $this->orderData['latitude'] = $this->tempLatitude;
        
        Log::info('Location saved from map', [
            'lat' => $this->orderData['latitude'],
            'lon' => $this->orderData['longitude'],
            'address' => $this->orderData['address'],
            'city' => $this->orderData['city'],
            'zip' => $this->orderData['zip_code']
        ]);
        
        // Clear temporary data
        $this->tempAddress = '';
        $this->tempCity = '';
        $this->tempZipCode = '';
        $this->tempLongitude = null;
        $this->tempLatitude = null;
        
        // Force Livewire to update the form fields
        $this->dispatch('$refresh');
        
        session()->flash('message', 'Location saved successfully!');
        $this->recalculateTotals();
    }

    public function updatedCustomerSearch()
    {
        // Debug: Log the search term
        Log::info('Customer search updated', ['term' => $this->customerSearch]);
        
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'customer');
        });
        
        if (!empty($this->customerSearch)) {
            $query->where('name', 'like', '%'.$this->customerSearch.'%');
        }
        
        $users = $query->limit(10)->get(['id', 'name', 'email']);
        
        Log::info('Customer search results', ['count' => $users->count(), 'users' => $users->toArray()]);
        
        $this->customerOptions = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];
        })->toArray();
    }

    public function updatedDeliverySearch()
    {
        // Debug: Log the search term
        Log::info('Delivery search updated', ['term' => $this->deliverySearch]);
        
        $query = DeliveryWorker::with('user');
        
        if (!empty($this->deliverySearch)) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%'.$this->deliverySearch.'%');
            });
        }
        
        $workers = $query->limit(10)->get();
        
        Log::info('Delivery search results', ['count' => $workers->count(), 'workers' => $workers->toArray()]);
        
        $this->deliveryOptions = $workers->map(function($worker) {
            return [
                'id' => $worker->id,
                'name' => $worker->user->name ?? 'Unknown',
                'email' => $worker->user->email ?? '',
                'phone' => $worker->phone,
                'vehicle_type' => $worker->vehicle_type,
                'status' => $worker->status
            ];
        })->toArray();
    }

    public function searchProducts($index)
    {
        $term = $this->productSearch[$index] ?? '';
        $this->productOptions[$index] = Product::when($term, fn($q) => $q->where('name','like','%'.$term.'%'))
            ->limit(10)
            ->get(['id','name','price'])
            ->toArray();
    }

    public function selectCustomer($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->orderData['customer_id'] = $id;
            $this->selectedCustomerName = $user->name;
            $this->customerSearch = $user->name;
        }
    }

    public function selectDelivery($id)
    {
        $worker = DeliveryWorker::with('user')->find($id);
        if ($worker && $worker->user) {
            $this->orderData['delivery_worker_id'] = $id;
            $this->selectedDeliveryName = $worker->user->name . ' (' . $worker->vehicle_type . ')';
            $this->deliverySearch = $worker->user->name . ' (' . $worker->vehicle_type . ')';
        }
    }

    public function clearCustomer()
    {
        $this->orderData['customer_id'] = null;
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
    }

    public function clearDelivery()
    {
        $this->orderData['delivery_worker_id'] = null;
        $this->selectedDeliveryName = '';
        $this->deliverySearch = '';
    }

    public function selectProduct($index, $productId)
    {
        $this->orderDetailsData[$index]['product_id'] = $productId;
        $this->recalculateTotals();
    }

    public function updatedOrderDetailsData()
    {
        $this->recalculateTotals();
    }

    private function recalculateTotals()
    {
        $this->subtotal = 0;
        foreach ($this->orderDetailsData as $line) {
            if (!empty($line['product_id']) && $line['quantity'] > 0) {
                $product = Product::find($line['product_id']);
                if ($product) {
                    // Use model accessor discounted_price
                    $this->subtotal += $product->discounted_price * (int)$line['quantity'];
                }
            }
        }
        $this->grandTotal = $this->subtotal + $this->shipping;
    }

    public function getLineTotal($index)
    {
        $detail = $this->orderDetailsData[$index] ?? null;
        if (!$detail || empty($detail['product_id']) || !$detail['quantity']) {
            return 0;
        }
        
        $product = Product::find($detail['product_id']);
        if (!$product) {
            return 0;
        }
        
        return $product->discounted_price * (int)$detail['quantity'];
    }

    public function testSearch()
    {
        Log::info('Test search method called');
        $this->customerOptions = [
            ['id' => 1, 'name' => 'Test Customer', 'email' => 'test@example.com'],
            ['id' => 2, 'name' => 'Another Customer', 'email' => 'another@example.com']
        ];
    }

    public function testDebug()
    {
        Log::info('Current orderData', $this->orderData);
        Log::info('Temp data', [
            'tempLat' => $this->tempLatitude,
            'tempLon' => $this->tempLongitude,
            'tempAddress' => $this->tempAddress
        ]);
    }

    public function render()
    {
        return view('livewire.create.create-order');
    }

    public function generateSessionId()
    {
        if (empty($this->orderData['session_id'])) {
            $this->orderData['session_id'] = strtoupper(Str::random(10));
        }
    }

    public function nextStep()
    {
        $this->validateStep($this->currentStep);
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->recalculateTotals();
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        $this->validateStep($this->currentStep);
        $this->currentStep = $step;
    }

    public function validateStep($step)
    {
        if ($step == 1) {
            $this->validate([
                'orderData.first_name' => 'required|string',
                'orderData.last_name' => 'required|string',
                'orderData.email' => 'required|email',
                'orderData.phone' => 'required|string',
                'orderData.customer_id' => 'required|integer|exists:users,id',
                'orderData.delivery_worker_id' => 'nullable|integer|exists:delivery_workers,id',
            ]);
        } elseif ($step == 2) {
            $this->validate([
                'orderDetailsData.*.product_id' => 'required|integer|exists:products,id',
                'orderDetailsData.*.quantity' => 'required|integer|min:1',
                'orderData.address' => 'required|string',
                'orderData.city' => 'required|string',
                'orderData.zip_code' => 'required|string',
            ]);
        } elseif ($step == 3) {
            $this->validate([
                'shippingMethod' => 'required|string|in:standard,express,overnight,pickup',
            ]);
        }
    }

    public function addOrderDetail()
    {
        $this->orderDetailsData[] = [
            'product_id' => null,
            'quantity' => 1,
        ];
    }

    public function removeOrderDetail($index)
    {
        unset($this->orderDetailsData[$index]);
        $this->orderDetailsData = array_values($this->orderDetailsData);
    }

    public function submitForm()
    {
        $this->validate();
        $this->recalculateTotals();

        try {
            $orderData = $this->orderData;
            if (empty($orderData['session_id'])) {
                $orderData['session_id'] = strtoupper(Str::random(10));
            }
            // Persist only allowed fillable fields of Order
            $order = Order::create([
                'first_name' => $orderData['first_name'],
                'last_name' => $orderData['last_name'],
                'email' => $orderData['email'],
                'phone' => $orderData['phone'],
                'customer_id' => $orderData['customer_id'],
                'delivery_worker_id' => $orderData['delivery_worker_id'],
                'status' => 'unpaid',
                'session_id' => $orderData['session_id'],
                'latitude' => $orderData['latitude'],
                'longitude' => $orderData['longitude'],
                'shipping_cost' => $this->shipping,
            ]);

            foreach ($this->orderDetailsData as $detail) {
                if (!$detail['product_id']) { continue; }
                $product = Product::find($detail['product_id']);
                if (!$product) { continue; }
                $lineTotal = $product->discounted_price * (int)$detail['quantity'];
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $detail['product_id'],
                    'total_price' => $lineTotal,
                    'quantity' => $detail['quantity'],
                    'city' => $orderData['city'],
                    'address' => $orderData['address'],
                    'zip_code' => $orderData['zip_code'],
                ]);
            }
            session()->flash('message', 'Order created successfully.');
            return redirect()->route('orders');
        } catch (\Exception $e) {
            session()->flash('error', 'There was an error creating the order: ' . $e->getMessage());
        }
    }
}