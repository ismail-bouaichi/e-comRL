<div class="bg-white p-6">
    <h2 class="text-xl font-semibold mb-4">Create Discount</h2>

    <div class="space-y-4">
        <!-- Name Field -->
        <div>
            <label class="text-sm">Name</label>
            <input 
                type="text" 
                wire:model="name" 
                class="w-full mt-1 rounded-md border-gray-300"
            >
        </div>

        <!-- Code and Type Row -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm">Code</label>
                <div class="flex gap-2 mt-1">
                    <input 
                        type="text" 
                        wire:model="code" 
                        class="w-full rounded-md border-gray-300"
                        readonly
                    >
                    <button 
                        wire:click="generateCode"
                        class="px-3 py-2 bg-blue-500 text-white rounded-md"
                    >
                        Generate
                    </button>
                </div>
            </div>

            <div>
                <label class="text-sm">Discount Type</label>
                <select 
                    wire:model="discount_type" 
                    class="w-full mt-1 rounded-md border-gray-300"
                >
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
            </div>
        </div>

        <!-- Value and Start Date Row -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm">Discount Value ($)</label>
                <input 
                    type="number" 
                    wire:model="discount_value" 
                    class="w-full mt-1 rounded-md border-gray-300"
                >
            </div>

            <div>
                <label class="text-sm">Start Date</label>
                <input 
                    type="date" 
                    wire:model="start_date" 
                    class="w-full mt-1 rounded-md border-gray-300"
                >
            </div>
        </div>

        <!-- End Date and Status Row -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm">End Date</label>
                <input 
                    type="date" 
                    wire:model="end_date" 
                    class="w-full mt-1 rounded-md border-gray-300"
                >
            </div>

            <div class="flex items-center mt-6">
                <label class="text-sm mr-2">Is Active</label>
                <input 
                    type="checkbox" 
                    wire:model="is_active" 
                    class="rounded border-gray-300 text-blue-600"
                >
            </div>
        </div>

        <div class="flex items-center">
            <label for="product_ids" class="w-1/3 block text-sm font-medium text-gray-700">Product IDs:</label>
            <div x-data="{ open: false }" class="w-2/3 relative">
                <!-- Main Select Button -->
                <button 
                    @click="open = !open"
                    type="button"
                    class="relative w-full rounded-md border border-gray-300 bg-white pl-3 pr-10 py-2 text-left focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                >
                    <span class="block truncate text-gray-600">
                        {{ count($product_ids) }} products selected
                    </span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
        
                <!-- Dropdown -->
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    class="absolute mt-1 w-full rounded-md bg-white shadow-lg"
                    style="display: none; min-width: 100%; z-index: 50;"
                >
                    <!-- Search Box -->
                    <div class="p-2 border-b border-gray-200">
                        <div class="relative">
                            <input 
                                wire:model.live.debounce.300ms="search"
                                type="text" 
                                class="w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-500 sm:text-sm"
                                placeholder="Search products..."
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
        
                    <!-- Options List -->
                    <ul class="max-h-36 overflow-auto py-1">
                        @forelse($products as $product)
                            <li class="relative">
                                <label 
                                    class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 cursor-pointer"
                                    wire:key="product-{{ $product->id }}"
                                >
                                    <div class="flex items-center">
                                        <input 
                                            type="checkbox"
                                            wire:model.live="product_ids"
                                            value="{{ $product->id }}"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="ml-3 text-gray-900">{{ $product->name }}</span>
                                    </div>
                                    <span class="text-gray-500">${{ number_format($product->price, 2) }}</span>
                                </label>
                            </li>
                        @empty
                            <li class="px-3 py-2 text-gray-500 text-center">No products found</li>
                        @endforelse
                    </ul>
        
                    <!-- Footer -->
                    @if(count($product_ids) > 0)
                        <div class="border-t border-gray-200 px-3 py-2 bg-gray-50 text-sm text-gray-500 flex justify-between items-center">
                            <span>{{ count($product_ids) }} selected</span>
                            <button 
                                wire:click="clearSelection"
                                type="button"
                                class="text-blue-500 hover:text-blue-600"
                            >
                                Clear selection
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Submit Button - Aligned Right -->
        <div class="flex justify-end mt-4">
            <button 
                wire:click="createDiscount"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md"
            >
                Create Discount
            </button>
        </div>
    </div>
</div>