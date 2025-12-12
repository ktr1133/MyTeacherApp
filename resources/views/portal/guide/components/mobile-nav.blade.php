{{-- モバイル用アコーディオンナビゲーション --}}
<section class="md:hidden mobile-nav-sticky bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="px-4 sm:px-6">
        <button 
            data-mobile-nav-toggle
            aria-expanded="false"
            aria-label="目次を開く"
            class="w-full py-4 flex items-center justify-between text-left hover:text-[#59B9C6] transition"
        >
            <span class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                目次
            </span>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        
        <nav data-mobile-nav class="hidden pb-4 space-y-2" aria-label="ページ内ナビゲーション">
            @foreach($sections as $section)
            <a href="#{{ $section['id'] }}" 
               data-nav-link 
               class="nav-link-transition nav-inactive block py-3 px-4 rounded-lg border-l-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 {{ $section['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        {!! $section['icon'] !!}
                    </svg>
                    <span class="font-medium">{{ $section['title'] }}</span>
                </div>
            </a>
            @endforeach
        </nav>
    </div>
</section>
