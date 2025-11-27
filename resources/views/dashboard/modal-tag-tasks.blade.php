@props(['tagId', 'tagName', 'tasksOfTag', 'allTags', 'isChildTheme' => false])
<div 
    data-tag-tasks-modal="{{ $tagId }}"
    data-tasks="{{ json_encode($tasksOfTag->values()->toArray()) }}"
    class="hidden">
    
    <div 
        data-modal-overlay
        style="display: none;"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 opacity-0 transition-opacity duration-300">
        
        <div 
            data-modal-content
            class="modal-glass rounded-xl sm:rounded-2xl shadow-2xl w-full max-w-7xl max-h-[95vh] sm:max-h-[90vh] flex flex-col opacity-0 scale-95 transition-all duration-300">
            
            {{-- ヘッダー --}}
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200/50 dark:border-gray-700/50 bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 flex items-center justify-center shadow-lg shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 12a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2H5zM12 5a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V5zM12 14a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ $tagName }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            <span data-task-count>{{ $tasksOfTag->count() }}</span> 件のタスク
                        </p>
                    </div>
                </div>
                <button 
                    data-close-modal
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition p-1.5 sm:p-2 rounded-full hover:bg-gray-200/50 dark:hover:bg-gray-700/50 shrink-0"
                    aria-label="閉じる">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- スパンフィルタータブ --}}
            <div class="px-4 sm:px-6 py-2 sm:py-3 border-b border-gray-200/50 dark:border-gray-700/50 bg-white/50 dark:bg-gray-900/50 shrink-0">
                <div class="flex gap-1.5 sm:gap-2 overflow-x-auto scrollbar-hide">
                    <button 
                        data-span-filter="all"
                        aria-pressed="true"
                        class="tag-modal-filter tag-modal-filter-active px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition whitespace-nowrap">
                        すべて
                        <span class="ml-1.5 opacity-75" data-filter-count>({{ $tasksOfTag->count() }})</span>
                    </button>
                    <button 
                        data-span-filter="{{ config('const.task_spans.short') }}"
                        aria-pressed="false"
                        class="tag-modal-filter tag-modal-filter-inactive px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition whitespace-nowrap">
                        <span class="inline-block w-2 h-2 rounded-full bg-[#9DD9C0] mr-1.5"></span>
                        短期
                        <span class="ml-1.5 opacity-75" data-filter-count>({{ $tasksOfTag->where('span', config('const.task_spans.short'))->count() }})</span>
                    </button>
                    <button 
                        data-span-filter="{{ config('const.task_spans.mid') }}"
                        aria-pressed="false"
                        class="tag-modal-filter tag-modal-filter-inactive px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition whitespace-nowrap">
                        <span class="inline-block w-2 h-2 rounded-full bg-[#7BC9A8] mr-1.5"></span>
                        中期
                        <span class="ml-1.5 opacity-75" data-filter-count>({{ $tasksOfTag->where('span', config('const.task_spans.mid'))->count() }})</span>
                    </button>
                    <button 
                        data-span-filter="{{ config('const.task_spans.long') }}"
                        aria-pressed="false"
                        class="tag-modal-filter tag-modal-filter-inactive px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition whitespace-nowrap">
                        <span class="inline-block w-2 h-2 rounded-full bg-[#59B9C6] mr-1.5"></span>
                        長期
                        <span class="ml-1.5 opacity-75" data-filter-count>({{ $tasksOfTag->where('span', config('const.task_spans.long'))->count() }})</span>
                    </button>
                </div>
            </div>

            {{-- タスク一覧 --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar p-3 sm:p-4 md:p-6 rounded-b-xl sm:rounded-b-2xl">
                @if($tasksOfTag->isNotEmpty())
                    <div class="tag-tasks-grid">
                        @foreach($tasksOfTag as $task)
                            <div data-task-card data-task-span="{{ $task->span }}">
                                <x-task-card :task="$task" :tags="$allTags" :isChildTheme="$isChildTheme" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 sm:py-16">
                        <svg class="mx-auto h-12 w-12 sm:h-16 sm:w-16 text-gray-400 dark:text-gray-600 mb-3 sm:mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400">このタグに紐づくタスクはありません</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>