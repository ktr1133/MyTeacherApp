<div 
    x-data="{ showModal: false }"
    @open-tag-modal-{{ $tagId }}.window="showModal = true; document.body.classList.add('overflow-hidden')"
    @keydown.escape.window="showModal && (showModal=false, document.body.classList.remove('overflow-hidden'))">
    
    <div 
        x-show="showModal"
        x-transition.opacity
        @click="showModal=false; document.body.classList.remove('overflow-hidden')"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        style="display: none;">
        
        <div 
            @click.stop
            x-show="showModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col">
            
            <div class="px-6 py-4 border-b bg-[#59B9C6]/10 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $tagName }}</h3>
                    <span class="inline-flex items-center justify-center min-w-[2rem] h-6 px-2 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                        {{ $tasksOfTag->count() }}
                    </span>
                </div>
                <button 
                    @click="showModal=false; document.body.classList.remove('overflow-hidden')"
                    class="text-gray-500 hover:text-gray-700 transition p-1 rounded-full hover:bg-gray-200"
                    aria-label="閉じる">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                @if($tasksOfTag->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 auto-rows-fr">
                        @foreach($tasksOfTag as $task)
                            <div class="flex">
                                <x-task-card :task="$task" :tags="$allTags" class="w-full" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-sm text-gray-500">このタグに紐づくタスクはありません。</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>