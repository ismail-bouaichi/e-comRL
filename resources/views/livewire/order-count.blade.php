<div class="bg-white rounded-lg p-4 shadow text-black relative overflow-hidden dark:bg-gray-800 dark:text-white" wire:poll.60s="refreshData">
    {{-- Card header with metric title --}}
    <div class="flex items-start justify-between mb-1">
    <h2 class="text-sm font-semibold">
        @if($type === 'revenue')
            Revenue
        @elseif($type === 'customers')
            New customers
        @else
            New orders
        @endif
    </h2>
    <button wire:click="refreshData" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100 flex items-center" title="Refresh now">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0019 5l1 1M4 5l1 1a9 9 0 0014 12" />
        </svg>
    </button>
    </div>

    {{-- Main counter value --}}
    <div class="text-3xl font-bold mb-2">
        @if($type === 'revenue')
            ${{ $this->formattedCounter() }}
        @else
            {{ $this->formattedCounter() }}
        @endif
    </div>

    {{-- Trend indicators and sparkline --}}
    <div class="flex items-center text-xs">
        <span class="{{ $trend === 'increase' ? 'text-green-400' : 'text-red-400' }} mr-2 flex items-center">
            {{ abs(round($percentageChange)) }}% {{ $trend }}
            
            {{-- Arrow icon based on trend --}}
            <svg class="inline-block w-3 h-3 ml-1" viewBox="0 0 20 20" fill="currentColor">
                @if($trend === 'increase')
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                @else
                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                @endif
            </svg>
        </span>

        {{-- Sparkline chart --}}
        <svg class="w-30 h-10 mt-1" aria-hidden="true" role="img">
            <polyline
                fill="none"
                stroke="{{ $trend === 'increase' ? '#4ade80' : '#f87171' }}"
                stroke-width="1.5"
                points="{{ $this->generateSparklinePoints() }}"
            />
        </svg>
    </div>

    {{-- Loading indicator when data is refreshing --}}
    <div wire:loading class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center dark:bg-gray-800 dark:bg-opacity-50">
        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    {{-- Colored bottom border based on trend --}}
    <div class="absolute bottom-0 left-0 right-0 h-1 {{ $trend === 'increase' ? 'bg-green-400' : 'bg-red-400' }}"></div>
</div>