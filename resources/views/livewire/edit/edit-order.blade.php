<div>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Edit Order #{{ $order->id }}</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Update order information and details</p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="updateOrder" class="mx-auto max-w-3xl space-y-8 px-4 pb-12 sm:px-6 lg:px-8">
        @foreach ($steps as $key => $stepName)
            <div class="rounded-lg bg-white dark:bg-gray-800 shadow">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $currentStep >= $key ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                            {{ $key }}
                        </div>
                        <div class="ml-4">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">{{ $stepName }}</h3>
                        </div>
                    </div>

                    @if ($currentStep === $key)
                        @if ($key === 1)
                            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                <!-- First Name -->
                                <div class="sm:col-span-3">
                                    <label for="first_name" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">First name</label>
                                    <div class="mt-2">
                                        <input type="text" wire:model.live="orderData.first_name" id="first_name" autocomplete="given-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    @error('orderData.first_name')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <!-- Last Name -->
                                <div class="sm:col-span-3">
                                    <label for="last_name" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Last name</label>
                                    <div class="mt-2">
                                        <input type="text" wire:model.live="orderData.last_name" id="last_name" autocomplete="family-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    @error('orderData.last_name')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <!-- Email -->
                                <div class="sm:col-span-3">
                                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Email address</label>
                                    <div class="mt-2">
                                        <input id="email" wire:model.live="orderData.email" type="email" autocomplete="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    @error('orderData.email')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <!-- Phone -->
                                <div class="sm:col-span-3">
                                    <label for="phone" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Phone number</label>
                                    <div class="mt-2">
                                        <input type="tel" wire:model.live="orderData.phone" id="phone" autocomplete="tel" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
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
                                            <li class="px-3 py-2 hover:bg-indigo-50 cursor-pointer" wire:click="selectCustomer({{ $opt['id'] }})" @click="open=false">{{ $opt['name'] }} <span class="text-gray-400">({{ $opt['email'] }})</span></li>
                                        @empty
                                            <li class="px-3 py-2 text-gray-400">No results</li>
                                        @endforelse
                                    </ul>
                                    @if($orderData['customer_id'])<p class="mt-1 text-xs text-green-600">Selected: {{ $selectedCustomerName ?? 'ID: ' . $orderData['customer_id'] }}</p>@endif
                                </div>

                                <!-- Delivery worker search -->
                                <div class="sm:col-span-6 relative" x-data="{ open:false }" @click.outside="open=false">
                                    <label class="block text-sm font-medium text-gray-700">Delivery Worker</label>
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
                                            <li class="px-3 py-2 hover:bg-indigo-50 cursor-pointer" wire:click="selectDelivery({{ $opt['id'] }})" @click="open=false">{{ $opt['name'] }} <span class="text-gray-400">(#{{ $opt['id'] }})</span></li>
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
                                                    <label class="block text-xs font-medium text-gray-700">Product</label>
                                                    <input type="text" x-on:focus="open=true" wire:model.debounce.300ms="productSearch.{{ $index }}" wire:input="searchProducts({{ $index }})" placeholder="Search products..." class="mt-1 w-full rounded border-gray-300 text-sm" />
                                                    @error("orderDetailsData.{$index}.product_id")<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                                    <ul x-show="open" x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded shadow max-h-40 overflow-auto text-xs">
                                                        @forelse($opts as $product)
                                                            <li class="px-2 py-1 hover:bg-blue-50 cursor-pointer" wire:click="selectProduct({{ $index }}, {{ $product['id'] }})" @click="open=false">{{ $product['name'] }} <span class="text-gray-400">${{ number_format($product['price'], 2) }}</span></li>
                                                        @empty
                                                            <li class="px-2 py-1 text-gray-400">No results</li>
                                                        @endforelse
                                                    </ul>
                                                    @if($detail['product_id'])
                                                        @php($product = \App\Models\Product::find($detail['product_id']))
                                                        @if($product)
                                                            <p class="mt-1 text-xs text-green-600">Selected: {{ $product->name }}</p>
                                                        @endif
                                                    @endif
                                                </div>

                                                <div class="col-span-2">
                                                    <label class="block text-xs font-medium text-gray-700">Quantity</label>
                                                    <input type="number" wire:model.live="orderDetailsData.{{ $index }}.quantity" min="1" class="mt-1 w-full rounded border-gray-300 text-sm" />
                                                    @error("orderDetailsData.{$index}.quantity")<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                                </div>

                                                <div class="col-span-3">
                                                    <label class="block text-xs font-medium text-gray-700">Line Total</label>
                                                    <div class="mt-1 text-sm font-semibold text-gray-900">${{ number_format($this->getLineTotal($index), 2) }}</div>
                                                </div>

                                                <div class="col-span-2 flex items-end">
                                                    <button type="button" wire:click.prevent="removeOrderDetail({{ $index }})" class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Address Fields -->
                                <div class="mt-6 border-t pt-6">
                                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Delivery Address</h3>
                                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                                        <div class="sm:col-span-6">
                                            <label for="address" class="block text-sm font-medium leading-6 text-gray-900">Street address</label>
                                            <div class="mt-2">
                                                <input type="text" wire:model.live="orderData.address" id="address" autocomplete="street-address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            @error('orderData.address')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                        </div>

                                        <div class="sm:col-span-2 sm:col-start-1">
                                            <label for="city" class="block text-sm font-medium leading-6 text-gray-900">City</label>
                                            <div class="mt-2">
                                                <input type="text" wire:model.live="orderData.city" id="city" autocomplete="address-level2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            @error('orderData.city')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label for="zip" class="block text-sm font-medium leading-6 text-gray-900">ZIP / Postal code</label>
                                            <div class="mt-2">
                                                <input type="text" wire:model.live="orderData.zip_code" id="zip" autocomplete="postal-code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            @error('orderData.zip_code')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                        </div>

                                        <div class="sm:col-span-2">
                                            <x-modal name="location-modal" :show="false" max-width="4xl">
                                                <div class="p-6" x-data="{
                                                    handleMapUpdate: function(event) {
                                                        console.log('Map update received:', event.detail);
                                                        $wire.call('updateAddress', event.detail);
                                                    }
                                                }" @map-location-selected.window="handleMapUpdate($event)">
                                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                                        {{ __('Pick a delivery location') }}
                                                    </h2>
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                        {{ __('Click on the map to select the delivery location.') }}
                                                    </p>

                                                    <div class="mt-6">
                                                        @livewire('map.map')
                                                    </div>

                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <x-secondary-button x-on:click="$dispatch('close')">
                                                            {{ __('Cancel') }}
                                                        </x-secondary-button>

                                                        <x-primary-button wire:click="saveLocationFromMap" x-on:click="$dispatch('close')">
                                                            {{ __('Save Location') }}
                                                        </x-primary-button>
                                                    </div>
                                                </div>
                                            </x-modal>

                                            <label class="block text-sm font-medium leading-6 text-gray-900">Location</label>
                                            <div class="mt-2">
                                                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'location-modal')" type="button" class="w-full justify-center">
                                                    {{ __('Pick Location') }}
                                                </x-primary-button>
                                            </div>

                                            <!-- Hidden inputs for coordinates -->
                                            <input type="hidden" wire:model="orderData.latitude" />
                                            <input type="hidden" wire:model="orderData.longitude" />
                                        </div>
                                    </div>

                                    @if($orderData['latitude'] && $orderData['longitude'])
                                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                                            <p class="text-sm text-green-700">
                                                <span class="font-medium">✓ Location saved:</span>
                                                Location saved: {{ number_format($orderData['latitude'], 6) }}, {{ number_format($orderData['longitude'], 6) }}
                                            </p>
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
                            <button type="button" wire:click.prevent="updateOrder" class="rounded-md bg-green-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Update Order</button>
                        @else
                            <button type="button" wire:click.prevent="nextStep" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Next</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-10 flex items-center justify-end gap-x-6">
            @if($currentStep === $totalSteps)
                <x-primary-button wire:click="updateOrder" class="bg-green-600 hover:bg-green-500">{{ __('Update Order') }}</x-primary-button>
            @endif
        </div>
    </form>
</div>
                                </div>
                            </div>
                                </div>
                                <!-- Add other fields for order details -->
                            </div>
                            <button type="button" wire:click="removeOrderDetail({{ $index }})" class="mt-2 bg-red-500 text-white px-4 py-2 rounded">Remove</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addOrderDetail" class="bg-green-500 text-white px-4 py-2 rounded">Add Product</button>
                </div>
            @elseif ($currentStep == 3)
                <div>
                    <h3 class="text-xl font-semibold mb-3">Shipping Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="shipping_cost">Shipping Cost</label>
                            <input type="number" id="shipping_cost" wire:model="shipping_cost" class="form-input mt-1 block w-full" step="0.01">
                            @error('shipping_cost') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <!-- Add other shipping fields -->
                    </div>
                </div>
            @endif
    
            <div class="mt-6 flex justify-between">
                @if ($currentStep > 1)
                    <button type="button" wire:click="previousStep" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Previous</button>
                @else
                    <div></div>
                @endif
    
                @if ($currentStep < $totalSteps)
                    <button type="button" wire:click="nextStep" class="bg-blue-500 text-white px-4 py-2 rounded">Next</button>
                @else
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Update Order</button>
                @endif
            </div>
        </form>
    </div>

</div>
