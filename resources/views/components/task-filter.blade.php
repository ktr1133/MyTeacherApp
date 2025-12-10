<div class="flex flex-col md:flex-row gap-4 w-full">
    <div class="flex gap-2 flex-1">
        <div class="relative w-full flex-1 search-bar">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input 
                type="search" 
                placeholder="タスクを検索... (#でタグ検索、空白でOR検索、&でAND検索)" 
                class="search-input-glow w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent focus:outline-none transition-all duration-300 text-sm placeholder-gray-400 dark:placeholder-gray-500 dark:text-white"
                autocomplete="off"
                data-search-input
                onfocus="window.dispatchEvent(new CustomEvent('search-focused', { detail: { focused: true } }))"
                onblur="window.dispatchEvent(new CustomEvent('search-focused', { detail: { focused: false } }))">
        </div>
        
        {{-- グループタスク管理ボタン（lg未満で表示） --}}
        @if(Auth::user()->canEditGroup())
            <a href="{{ route('group-tasks.index') }}"
               class="lg:hidden flex items-center justify-center shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 hover:from-purple-200 hover:to-indigo-200 dark:hover:from-purple-800/40 dark:hover:to-indigo-800/40 border-2 border-purple-300 dark:border-purple-600 hover:border-purple-400 dark:hover:border-purple-500 transition-all duration-300 hover:shadow-lg hover:shadow-purple-200 dark:hover:shadow-purple-900/30 group"
               aria-label="{{ $isChildTheme ?? false ? 'クエストかんり' : 'グループタスク管理' }}">
                <svg class="w-6 h-6 text-purple-700 dark:text-purple-400 group-hover:text-purple-800 dark:group-hover:text-purple-300 group-hover:scale-110 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
        @endif
    </div>
</div>

@vite(['resources/js/dashboard/task-search.js'])