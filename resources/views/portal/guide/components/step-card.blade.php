{{-- ステップカード共通コンポーネント --}}
<div class="bg-white dark:bg-gray-800 rounded-xl p-6 sm:p-8 shadow-md border border-gray-200 dark:border-gray-700">
    <div class="flex items-start gap-4 mb-4">
        <div class="{{ $bgColor }} p-3 sm:p-4 rounded-lg flex-shrink-0">
            <svg class="w-6 h-6 sm:w-8 sm:h-8 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                {!! $icon !!}
            </svg>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full {{ $badgeBg }} {{ $badgeText }} font-bold text-sm">
                    {{ $step }}
                </span>
                <h3 class="font-bold text-lg sm:text-xl text-gray-900 dark:text-white">{{ $title }}</h3>
            </div>
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 mb-4">{{ $description }}</p>
            
            @if($hasInstructions ?? false)
            <div class="space-y-4">
                {{ $slot }}
            </div>
            @endif
        </div>
    </div>
</div>
