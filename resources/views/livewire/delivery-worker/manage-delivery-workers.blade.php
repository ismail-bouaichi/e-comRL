<div>
    <section class="mt-10">
        <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
            <!-- Success Message -->
            @if (session()->has('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <!-- Header -->
                <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                    <div class="w-full md:w-1/2">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Delivery Workers</h2>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="flex items-center justify-between p-4 border-t dark:border-gray-700">
                    <div class="flex flex-1 space-x-3">
                        <!-- Search -->
                        <div class="relative w-full max-w-md">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="text"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                   placeholder="Search workers...">
                        </div>

                        <!-- Status Filter -->
                        <select wire:model.live="statusFilter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Statuses</option>
                            <option value="available">Available</option>
                            <option value="on_delivery">On Delivery</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 cursor-pointer" wire:click="setSortBy('id')">
                                    ID
                                </th>
                                <th scope="col" class="px-4 py-3">Worker Name</th>
                                <th scope="col" class="px-4 py-3">Phone</th>
                                <th scope="col" class="px-4 py-3">Vehicle</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Current Order</th>
                                <th scope="col" class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($workers as $worker)
                                <tr wire:key="{{ $worker->id }}" class="border-b dark:border-gray-700">
                                    <!-- ID -->
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        #{{ $worker->id }}
                                    </td>

                                    <!-- Worker Name -->
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ $worker->user->name ?? 'N/A' }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $worker->user->email ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Phone -->
                                    <td class="px-4 py-3">
                                        {{ $worker->phone ?? 'N/A' }}
                                    </td>

                                    <!-- Vehicle -->
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            @if($worker->vehicle_type === 'bike') bg-green-100 text-green-800
                                            @elseif($worker->vehicle_type === 'car') bg-blue-100 text-blue-800
                                            @else bg-purple-100 text-purple-800
                                            @endif">
                                            {{ ucfirst($worker->vehicle_type) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3">
                                        <select wire:change="updateWorkerStatus({{ $worker->id }}, $event.target.value)"
                                                class="text-xs rounded px-2 py-1 border-0
                                                @if($worker->status === 'available') bg-green-100 text-green-800
                                                @elseif($worker->status === 'on_delivery') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                            <option value="available" {{ $worker->status === 'available' ? 'selected' : '' }}>Available</option>
                                            <option value="on_delivery" {{ $worker->status === 'on_delivery' ? 'selected' : '' }}>On Delivery</option>
                                            <option value="offline" {{ $worker->status === 'offline' ? 'selected' : '' }}>Offline</option>
                                        </select>
                                    </td>

                                    <!-- Current Order -->
                                    <td class="px-4 py-3">
                                        @if($worker->currentOrder)
                                            <a href="{{ route('orders.show', $worker->currentOrder->id) }}" 
                                               class="text-blue-600 hover:underline">
                                                Order #{{ $worker->currentOrder->id }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">None</span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3">
                                        @if($worker->status === 'available')
                                            <button wire:click="openAssignModal({{ $worker->id }})"
                                                    class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Assign Order
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">Not available</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No delivery workers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $workers->links() }}
                </div>
            </div>
        </div>
    </section>

    <!-- Assign Order Modal -->
    @if($showAssignModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
             wire:click="closeAssignModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800"
                 wire:click.stop>
                <div class="mt-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        Assign Worker to Order
                    </h3>
                    
                    <div class="mt-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select Order:
                        </label>
                        <select wire:model="selectedOrderId"
                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Choose an order...</option>
                            @foreach($availableOrders as $order)
                                <option value="{{ $order['id'] }}">
                                    Order #{{ $order['id'] }} - {{ $order['customer']['name'] ?? 'N/A' }}
                                    ({{ $order['status'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 mt-4">
                        <button wire:click="closeAssignModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Cancel
                        </button>
                        <button wire:click="assignToOrder"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Assign
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
