<div>
    <section class="mt-10">
        <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="flex items-center justify-between p-4">
                    <!-- Search & Filter Section -->
                    <div class="flex">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-800 dark:text-white "
                                   placeholder="Search" required>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <div class="flex space-x-3 items-center">
                            <label class="w-60 text-sm font-medium text-gray-900 dark:text-white ">Status Type:</label>
                            <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-800 dark:text-white">
                                <option value="" class="dark:text-white">All</option>
                                <option value="paid" class="dark:text-white">paid</option>
                                <option value="onProgress" class="dark:text-white">onProgress</option>
                                <option value="complete" class="dark:text-white">complete</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @include('livewire.tables.includes.table-sortable-th', ['name' => 'first_name', 'displayName' => 'Customer Name'])
                                @include('livewire.tables.includes.table-sortable-th', ['name' => 'delivery_worker_id', 'displayName' => 'Delivery Worker'])
                                @include('livewire.tables.includes.table-sortable-th', ['name' => 'status', 'displayName' => 'Status'])
                                <th scope="col" class="px-4 py-3">Is Assigned</th>
                                <th scope="col" class="px-4 py-3">Order Details</th>
                                <th scope="col" class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr wire:key="{{ $order->id }}">
                                    <!-- Customer Info -->
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $order->customer->name }}
                                    </td>
                                    
                                    <!-- Delivery Worker Info -->
                                    <td class="px-4 py-3">
                                        @if($order->deliveryWorker)
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ $order->deliveryWorker->user->name }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ ucfirst($order->deliveryWorker->vehicle_type) }} • 
                                                    <span class="
                                                        @if($order->deliveryWorker->status === 'available') text-green-600
                                                        @elseif($order->deliveryWorker->status === 'on_delivery') text-blue-600
                                                        @else text-gray-600
                                                        @endif
                                                    ">
                                                        {{ ucfirst(str_replace('_', ' ', $order->deliveryWorker->status)) }}
                                                    </span>
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-yellow-600 dark:text-yellow-400 text-sm">
                                                Not assigned
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <!-- Order Status -->
                                    <td class="px-4 py-3">{{ $order->status }}</td>
                                    
                                    <!-- Is Assigned -->
                                    <td class="px-4 py-3">{{ $order->is_assigned }}</td>
                                    
                                    <!-- Order Details with Expand/Collapse -->
                                    <td class="px-4 py-3">
                                        <div x-data="{ open: false }">
                                            <button @click="open = !open" class="text-blue-600 hover:text-blue-800">
                                                <span x-show="!open">Show Details</span>
                                                <span x-show="open">Hide Details</span>
                                            </button>
                                            
                                            <ul x-show="open" class="mt-2">
                                                @foreach ($order->orderDetails->take(2) as $detail) <!-- Show first 2 items -->
                                                    <li>
                                                        Product ID: {{ $detail->product_id }},
                                                        Price: {{ $detail->total_price }},
                                                        City: {{ Str::limit($detail->city, 15, '...') }},
                                                    </li>
                                                @endforeach
                                                
                                                @if ($order->orderDetails->count() > 2)
                                                    <li>...and {{ $order->orderDetails->count() - 2 }} more</li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="px-4 py-3 flex space-x-4">
                                        <a onclick="confirm('Are you sure you want to delete this order?') || event.stopImmediatePropagation()" wire:click="delete({{ $order->id }})" class="text-red-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                                                <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('orders.edit', $order->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('orders.show', $order->id) }}" class="text-yellow-500 hover:text-yellow-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" fill="currentColor" class="w-6 h-6">
                                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                                <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" />
                                              </svg>
                                              
                                              </svg>
                                              
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="p-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </section>
</div>
