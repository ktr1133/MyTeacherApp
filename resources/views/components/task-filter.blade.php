<div class="flex flex-col md:flex-row gap-4 w-full">
    {{-- 検索バー (レスポンシブ対応) --}}
    <div class="relative w-full md:flex-1">
        <input type="search" 
               placeholder="タスクを検索... (#でタグ検索、空白でOR検索、&でAND検索)" 
               class="w-full py-2 pr-4 border rounded-lg focus:ring-[#59B9C6] focus:border-[#59B9C6] focus:outline-none"
               autocomplete="off">
        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    </div>
</div>

@vite(['resources/js/dashboard/task-search.js'])