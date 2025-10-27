<!-- resources/views/components/header-component.blade.php -->
<div class="flex items-center justify-between p-6 text-white max-w-6xl mx-auto my-2">
    <div class="text-gray-400">
        @foreach ($breadcrumb as $item)
            <a href="{{ $item['url'] }}" class="hover:text-white">{{ $item['label'] }}</a>
            @if (!$loop->last)
                <span class="mx-2">/</span>
            @endif
        @endforeach
        <h1 class="text-xl font-bold text-white">{{ $breadcrumb[count($breadcrumb) - 1]['label'] }}</h1>
    </div>

    @if ($showButton)

    <x-primary-button href="{{ $buttonLink }}">
        {{ $buttonText }}
    </x-primary-button>

    @endif
    
</div>
