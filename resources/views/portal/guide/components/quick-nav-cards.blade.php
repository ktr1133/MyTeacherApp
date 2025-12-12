{{-- クイックナビゲーションカード（デスクトップ専用） --}}
<section class="md:hidden mb-12">
    <div class="portal-container">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            @foreach($sections as $section)
            <a href="#{{ $section['id'] }}" 
               class="group block bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:scale-105">
                <div class="flex items-center gap-4 mb-3">
                    <div class="{{ $section['bgColor'] }} p-3 rounded-lg">
                        <svg class="w-6 h-6 {{ $section['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            {!! $section['icon'] !!}
                        </svg>
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-[#59B9C6] transition">
                        {{ $section['title'] }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $section['description'] }}</p>
            </a>
            @endforeach
        </div>
    </div>
</section>
