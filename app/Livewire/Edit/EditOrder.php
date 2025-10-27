<?php

namespace App\Livewire\Edit;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use App\Models\DeliveryWorker;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class EditOrder extends Component
{
    public $order;
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

    public $orderDetailsData = [];

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

    public function mount($orderId)
    {
        $this->order = Order::with('orderDetails.product')->findOrFail($orderId);
        
        // Initialize orderData with existing order values
        $this->orderData = [
            'first_name' => $this->order->first_name,
            'last_name' => $this->order->last_name,
            'email' => $this->order->email,
            'phone' => $this->order->phone,
            'customer_id' => $this->order->customer_id,
            'delivery_worker_id' => $this->order->delivery_worker_id,
            'status' => $this->order->status,
            'session_id' => $this->order->session_id,
            'longitude' => $this->order->longitude,
            'latitude' => $this->order->latitude,
            'address' => '',
            'city' => '',
            'zip_code' => '',
        ];

        // Set shipping method based on existing shipping cost
        $this->shipping = $this->order->shipping_cost ?? 0;
        foreach ($this->shippingOptions as $method => $details) {
            if ($details['cost'] == $this->shipping) {
                $this->shippingMethod = $method;
                break;
            }
        }

        // Load order details
        foreach ($this->order->orderDetails as $detail) {
            $this->orderDetailsData[] = [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
            ];
            
            // Set address data from first order detail (they should all be the same)
            if (empty($this->orderData['address'])) {
                $this->orderData['address'] = $detail->address;
                $this->orderData['city'] = $detail->city;
                $this->orderData['zip_code'] = $detail->zip_code;
            }
        }

        // Initialize search options
        $this->loadSearchOptions();
        $this->recalculateTotals();
    }

    public function loadSearchOptions()
    {
        // Load customers
        $customers = User::whereHas('role', function($q) {
                $q->where('name', 'customer');
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);
            
        $this->customerOptions = $customers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            })
            ->toArray();
            
        // Load delivery workers from delivery_workers table with user relationship
        $deliveryWorkers = DeliveryWorker::with('user')
            ->limit(10)
            ->get();
            
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

        // Set selected names
        if ($this->orderData['customer_id']) {
            $customer = User::find($this->orderData['customer_id']);
            if ($customer) {
                $this->selectedCustomerName = $customer->name;
                $this->customerSearch = $customer->name;
            }
        }

        if ($this->orderData['delivery_worker_id']) {
            $delivery = DeliveryWorker::with('user')->find($this->orderData['delivery_worker_id']);
            if ($delivery && $delivery->user) {
                $this->selectedDeliveryName = $delivery->user->name . ' (' . $delivery->vehicle_type . ')';
                $this->deliverySearch = $delivery->user->name;
            }
        }
    }

    public function updatedShippingMethod()
    {
        $this->shipping = $this->shippingOptions[$this->shippingMethod]['cost'] ?? 0;
        $this->recalculateTotals();
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
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'customer');
        });
        
        if (!empty($this->customerSearch)) {
            $query->where('name', 'like', '%'.$this->customerSearch.'%');
        }
        
        $users = $query->limit(10)->get(['id', 'name', 'email']);
        
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
        $query = DeliveryWorker::with('user');
        
        if (!empty($this->deliverySearch)) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%'.$this->deliverySearch.'%');
            });
        }
        
        $workers = $query->limit(10)->get();
        
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
            $this->deliverySearch = $worker->user->name;
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
                'orderData.delivery_worker_id' => 'required|integer|exists:users,id',
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
        if (isset($this->orderDetailsData[$index]['id'])) {
            OrderDetail::findOrFail($this->orderDetailsData[$index]['id'])->delete();
        }

        unset($this->orderDetailsData[$index]);
        $this->orderDetailsData = array_values($this->orderDetailsData);
        $this->recalculateTotals();
    }

    public function render()
    {
        return view('livewire.edit.edit-order');
    }

    public function updateOrder()
    {
        $this->validate();
        $this->recalculateTotals();

        try {
            $this->order->update([
                'first_name' => $this->orderData['first_name'],
                'last_name' => $this->orderData['last_name'],
                'email' => $this->orderData['email'],
                'phone' => $this->orderData['phone'],
                'customer_id' => $this->orderData['customer_id'],
                'delivery_worker_id' => $this->orderData['delivery_worker_id'],
                'status' => $this->orderData['status'],
                'session_id' => $this->orderData['session_id'],
                'latitude' => $this->orderData['latitude'],
                'longitude' => $this->orderData['longitude'],
                'shipping_cost' => $this->shipping,
            ]);

            foreach ($this->orderDetailsData as $detailData) {
                if (isset($detailData['id'])) {
                    $detail = OrderDetail::findOrFail($detailData['id']);
                    $product = Product::find($detailData['product_id']);
                    if ($product) {
                        $detail->update([
                            'product_id' => $detailData['product_id'],
                            'quantity' => $detailData['quantity'],
                            'total_price' => $product->discounted_price * (int)$detailData['quantity'],
                            'city' => $this->orderData['city'],
                            'address' => $this->orderData['address'],
                            'zip_code' => $this->orderData['zip_code'],
                        ]);
                    }
                } else {
                    $product = Product::find($detailData['product_id']);
                    if ($product) {
                        OrderDetail::create([
                            'order_id' => $this->order->id,
                            'product_id' => $detailData['product_id'],
                            'quantity' => $detailData['quantity'],
                            'total_price' => $product->discounted_price * (int)$detailData['quantity'],
                            'city' => $this->orderData['city'],
                            'address' => $this->orderData['address'],
                            'zip_code' => $this->orderData['zip_code'],
                        ]);
                    }
                }
            }

            session()->flash('message', 'Order updated successfully.');
            return redirect()->route('orders');
        } catch (\Exception $e) {
            session()->flash('error', 'There was an error updating the order: ' . $e->getMessage());
        }
    }
}