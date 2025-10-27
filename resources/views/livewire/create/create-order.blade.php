<div class="bg-white p-6 rounded-lg shadow-lg max-w-5xl mx-auto my-10" x-data="{ openCustomer:false, openDelivery:false }">
    @if (session()->has('message'))
        <div class="alert alert-success" aria-live="polite">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger" aria-live="polite">
            {{ session('error') }}
        </div>
    @endif

    @php
        $stepCount = count($steps);
    @endphp
    <div class="flex items-center justify-center space-x-4 my-8">
        @for ($i = 1; $i <= $stepCount; $i++)
            <div class="flex items-center">
                <div class="rounded-full h-8 w-8 flex items-center justify-center {{ $currentStep >= $i ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                    {{ $i }}
                </div>
                <div class="text-sm ml-2">
                    Step {{ $i }}
                </div>
            </div>
            @if ($i < $stepCount)
                <div class="flex-grow h-0.5 {{ $currentStep > $i ? 'bg-blue-500' : 'bg-gray-300' }}"></div>
            @endif
        @endfor
    </div>
    <form wire:submit.prevent="submitForm">
        @foreach ($steps as $key => $step)
            <div class="{{ $currentStep === $key ? 'block' : 'hidden' }} space-y-10">
                <div class="border-b border-gray-200 pb-8">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">{{ $step }}</h2>
                    @if ($currentStep === $key)
                        @if ($key === 1)
                            <div class="mt-8 grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-12">
                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                                    <input type="text" wire:model.defer="orderData.first_name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                    @error('orderData.first_name')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <input type="text" wire:model.defer="orderData.last_name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                    @error('orderData.last_name')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" wire:model.defer="orderData.email" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                    @error('orderData.email')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" wire:model.defer="orderData.phone" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                    @error('orderData.phone')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <!-- Customer search -->
                                <div class="sm:col-span-6 relative" x-data="{ open:false }" @click.outside="open=false">
                                    <label class="block text-sm font-medium text-gray-700">Customer</label>
                                    <div class="relative">
                                        <input type="text" x-on:focus="open=true" wire:model.debounce.400ms="customerSearch" placeholder="Search customer..." class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm pr-8" />
                                        @if($orderData['customer_id'])
                                            <button type="button" wire:click="clearCustomer" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                    @error('orderData.customer_id')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                    <ul x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-md shadow max-h-52 overflow-auto text-sm">
                                        @forelse($customerOptions as $opt)
                                            <li class="px-3 py-2 hover:bg-indigo-50 cursor-pointer" wire:click="selectCustomer({{ $opt['id'] }})" @click="open=false">{{ $opt['name'] }} <span class="text-gray-400">(#{{ $opt['id'] }})</span></li>
                                        @empty
                                            <li class="px-3 py-2 text-gray-400">No results</li>
                                        @endforelse
                                    </ul>
                                    @if($orderData['customer_id'])<p class="mt-1 text-xs text-green-600">Selected: {{ $selectedCustomerName ?? 'ID: ' . $orderData['customer_id'] }}</p>@endif
                                </div>

                                <!-- Delivery worker search -->
                                <div class="sm:col-span-6 relative" x-data="{ open:false }" @click.outside="open=false">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Delivery Worker 
                                        <span class="text-gray-400 font-normal">(Optional)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" x-on:focus="open=true" wire:model.debounce.400ms="deliverySearch" placeholder="Search delivery worker..." class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm pr-8" />
                                        @if($orderData['delivery_worker_id'])
                                            <button type="button" wire:click="clearDelivery" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                    @error('orderData.delivery_worker_id')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                    <ul x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-md shadow max-h-52 overflow-auto text-sm">
                                        @forelse($deliveryOptions as $opt)
                                            <li class="px-3 py-2 hover:bg-indigo-50 cursor-pointer flex justify-between items-center" wire:click="selectDelivery({{ $opt['id'] }})" @click="open=false">
                                                <div>
                                                    <span class="font-medium">{{ $opt['name'] }}</span>
                                                    <span class="text-gray-400 text-xs ml-1">({{ $opt['vehicle_type'] ?? 'N/A' }})</span>
                                                    <br>
                                                    <span class="text-gray-500 text-xs">{{ $opt['email'] ?? '' }}</span>
                                                </div>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                                    {{ $opt['status'] === 'available' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $opt['status'] === 'on_delivery' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $opt['status'] === 'offline' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                    {{ ucfirst($opt['status'] ?? 'unknown') }}
                                                </span>
                                            </li>
                                        @empty
                                            <li class="px-3 py-2 text-gray-400">No results</li>
                                        @endforelse
                                    </ul>
                                    @if($orderData['delivery_worker_id'])<p class="mt-1 text-xs text-green-600">Selected: {{ $selectedDeliveryName ?? 'ID: ' . $orderData['delivery_worker_id'] }}</p>@endif
                                </div>
                            </div>
                        @elseif ($key === 2)
                            <div class="mt-6 space-y-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-800">Order Lines</h3>
                                    <button type="button" wire:click.prevent="addOrderDetail" class="px-3 py-2 rounded-md bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-500">Add Line</button>
                                </div>
                                <div class="space-y-4">
                                    @foreach ($orderDetailsData as $index => $detail)
                                        @php($opts = $productOptions[$index] ?? [])
                                        <div class="border rounded-md p-4 bg-gray-50 relative">
                                            <div class="grid grid-cols-12 gap-3 items-start">
                                                <div class="col-span-5" x-data="{ open:false }" @click.outside="open=false">
                                                    <label class="block text-xs font-medium text-gray-600">Product</label>
                                                    <input type="text" placeholder="Search product..." x-on:focus="open=true" wire:model.debounce.400ms="productSearch.{{ $index }}" wire:input="searchProducts({{ $index }})" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                                    @error('orderDetailsData.'.$index.'.product_id')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                                    <ul x-show="open" x-transition class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-md shadow max-h-48 overflow-auto text-sm">
                                                        @forelse($opts as $p)
                                                            <li class="px-3 py-2 hover:bg-indigo-50 cursor-pointer" wire:click="selectProduct({{ $index }}, {{ $p['id'] }})" @click="open=false">{{ $p['name'] }} - ${{ number_format($p['price'],2) }}</li>
                                                        @empty
                                                            <li class="px-3 py-2 text-gray-400">No products</li>
                                                        @endforelse
                                                    </ul>
                                                    @if($detail['product_id'])<p class="mt-1 text-xs text-green-600">Selected ID: {{ $detail['product_id'] }}</p>@endif
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-xs font-medium text-gray-600">Qty</label>
                                                    <input type="number" min="1" wire:model.lazy="orderDetailsData.{{ $index }}.quantity" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                                    @error('orderDetailsData.'.$index.'.quantity')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                                </div>
                                                <div class="col-span-3 flex flex-col justify-end">
                                                    <span class="text-xs font-medium text-gray-500">Line Total</span>
                                                    <span class="text-sm font-semibold text-gray-800">
                                                        ${{ number_format($this->getLineTotal($index), 2) }}
                                                    </span>
                                                </div>
                                                <div class="col-span-2 flex justify-end">
                                                    <button type="button" wire:click.prevent="removeOrderDetail({{ $index }})" class="mt-5 px-2 py-1 rounded bg-red-500 text-white text-xs hover:bg-red-600">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Address / Map selection -->
                                <div class="grid grid-cols-12 gap-4 pt-4 border-t">
                                    <div class="col-span-4">
                                        <label class="block text-xs font-medium text-gray-600">Address</label>
                                        <input type="text" wire:model.live="orderData.address" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                        @error('orderData.address')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-span-3">
                                        <label class="block text-xs font-medium text-gray-600">City</label>
                                        <input type="text" wire:model.live="orderData.city" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                        @error('orderData.city')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-600">Zip</label>
                                        <input type="text" wire:model.live="orderData.zip_code" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                        @error('orderData.zip_code')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-span-3 flex items-end">
                                        <button type="button" x-on:click.prevent="$dispatch('open-modal', 'chose-location')" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">Choose Location</button>
                                    </div>
                                    
                                    <!-- Hidden inputs for coordinates -->
                                    <input type="hidden" wire:model="orderData.longitude" />
                                    <input type="hidden" wire:model="orderData.latitude" />
                                    
                                    <!-- Display coordinates if available -->
                                    @if($orderData['longitude'] && $orderData['latitude'])
                                        <div class="col-span-12 text-xs text-green-600">
                                            Location saved: {{ number_format($orderData['latitude'], 6) }}, {{ number_format($orderData['longitude'], 6) }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Totals -->
                                <div class="mt-6 bg-white border rounded p-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Subtotal</span>
                                        <div class="font-semibold">${{ number_format($subtotal,2) }}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Shipping</span>
                                        <div class="font-semibold">${{ number_format($shipping,2) }}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Total</span>
                                        <div class="font-semibold">${{ number_format($grandTotal,2) }}</div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($key === 3)
                            <div class="mt-6 space-y-6">
                                <h3 class="text-lg font-semibold text-gray-800">Shipping & Review</h3>
                                
                                <!-- Shipping Method Selection -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium text-gray-700">Select Shipping Method</h4>
                                    @foreach ($shippingOptions as $method => $details)
                                        <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $shippingMethod === $method ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                            <input type="radio" wire:model.live="shippingMethod" value="{{ $method }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                            <div class="ml-3 flex-1">
                                                <div class="flex justify-between items-center">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">{{ $details['name'] }}</div>
                                                        <div class="text-xs text-gray-500">{{ $details['days'] }}</div>
                                                    </div>
                                                    <div class="text-sm font-semibold text-gray-900">
                                                        @if($details['cost'] > 0)
                                                            ${{ number_format($details['cost'], 2) }}
                                                        @else
                                                            Free
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                    @error('shippingMethod')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <!-- Order Summary -->
                                <div class="mt-6 bg-gray-50 border rounded-lg p-6">
                                    <h4 class="text-sm font-medium text-gray-700 mb-4">Order Summary</h4>
                                    
                                    <!-- Customer Info -->
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Customer:</span>
                                            <span>{{ $orderData['first_name'] }} {{ $orderData['last_name'] }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Email:</span>
                                            <span>{{ $orderData['email'] }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Address:</span>
                                            <span>{{ $orderData['address'] }}, {{ $orderData['city'] }} {{ $orderData['zip_code'] }}</span>
                                        </div>
                                    </div>

                                    <!-- Order Items -->
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <h5 class="text-xs font-medium text-gray-700 mb-2">Items:</h5>
                                        @foreach ($orderDetailsData as $index => $detail)
                                            @if($detail['product_id'])
                                                @php($product = \App\Models\Product::find($detail['product_id']))
                                                @if($product)
                                                    <div class="flex justify-between text-xs text-gray-600">
                                                        <span>{{ $product->name }} (x{{ $detail['quantity'] }})</span>
                                                        <span>${{ number_format($this->getLineTotal($index), 2) }}</span>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>

                                    <!-- Totals -->
                                    <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Subtotal:</span>
                                            <span>${{ number_format($subtotal, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Shipping:</span>
                                            <span>${{ number_format($shipping, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-2">
                                            <span>Total:</span>
                                            <span>${{ number_format($grandTotal, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="mt-8 flex items-center justify-between">
                        <button type="button" wire:click.prevent="previousStep" @if ($key === 1) disabled @endif class="text-sm font-medium text-gray-600 disabled:opacity-30 hover:text-gray-800">Previous</button>
                        
                        @if ($key === $totalSteps)
                            <button type="button" wire:click.prevent="submitForm" class="rounded-md bg-green-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Create Order</button>
                        @else
                            <button type="button" wire:click.prevent="nextStep" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Next</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-10 flex items-center justify-end gap-x-6">
            <button type="button" wire:click="testDebug" class="px-3 py-2 text-xs bg-gray-500 text-white rounded">Debug</button>
            <x-primary-button wire:click="generateSessionId">Generate Ref</x-primary-button>
            @if($currentStep === $totalSteps)
                <x-primary-button wire:click="submitForm" class="bg-green-600 hover:bg-green-500">{{ __('Create Order') }}</x-primary-button>
            @endif
        </div>
    </form>


    <div>
        <x-modal name="chose-location" focusable>
           
            <div class="p-2" x-data="{ 
                handleMapUpdate: function(event) {
                    console.log('Map update received:', event.detail);
                    $wire.call('updateAddress', event.detail);
                }
            }" @map-location-selected.window="handleMapUpdate($event)">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Pick a delivery location') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Search or click on the map to set the order destination. Coordinates and address will fill automatically.') }}
                </p>
                <div class="mt-2">
                    @livewire('map.map')
                    
                    <!-- Show temporary selection -->
                    @if($tempLatitude && $tempLongitude)
                        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                            <strong>Location selected:</strong> {{ $tempAddress }}, {{ $tempCity }} {{ $tempZipCode }}<br>
                            <em>Coordinates: {{ number_format($tempLatitude, 6) }}, {{ number_format($tempLongitude, 6) }}</em><br>
                            <span class="text-yellow-700">Click "Save Location" to confirm this selection.</span>
                        </div>
                    @endif
                </div>
    <div class="mt-6 flex justify-between">
        <x-secondary-button x-on:click="$dispatch('close')">
            {{ __('Cancel') }}
        </x-secondary-button>
        
        <x-primary-button wire:click="saveLocationFromMap" x-on:click="$dispatch('close')">
            {{ __('Save Location') }}
        </x-primary-button>
    </div>
            </div>

        </x-modal>
    <div class="mt-4 text-xs text-gray-400">* All pricing automatically calculated from product catalog & discounts.</div>
    </div>
    
</div>
