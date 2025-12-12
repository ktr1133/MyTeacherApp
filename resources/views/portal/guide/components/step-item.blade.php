{{-- インラインステップ項目コンポーネント --}}
<div class="flex items-start gap-3 sm:gap-4">
    <div class="w-8 h-8 sm:w-10 sm:h-10 {{ $bgColor }} text-white rounded-full flex items-center justify-center font-bold flex-shrink-0 mt-0.5 sm:mt-1 text-sm sm:text-base">
        {{ $number }}
    </div>
    <div class="flex-1 min-w-0">
        <h4 class="font-bold text-gray-900 dark:text-white mb-1 sm:mb-2 text-base sm:text-lg">{{ $title }}</h4>
        @if(isset($content))
            <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                {!! $content !!}
            </div>
        @endif
        @if(isset($slot) && !empty(trim($slot)))
            <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
