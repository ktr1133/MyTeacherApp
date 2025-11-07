<div class="flex flex-col md:flex-row gap-4 w-full">
    <div class="relative w-full md:flex-1 search-bar">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input 
            type="search" 
            placeholder="タスクを検索... (#でタグ検索、空白でOR検索、&でAND検索)" 
            class="search-input-glow w-full pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent focus:outline-none transition text-sm placeholder-gray-400 dark:placeholder-gray-500 dark:text-white"
            autocomplete="off">
    </div>
</div>

@vite(['resources/js/dashboard/task-search.js'])