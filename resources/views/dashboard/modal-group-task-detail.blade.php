<div
     id="group-task-detail-modal-{{ $task->id }}"
     class="modal fixed inset-0 z-50 items-center justify-center p-safe modal-overlay bg-gray-900 bg-opacity-75 hidden opacity-0 transition-opacity duration-300"
     data-modal-state="closed"
     x-data="{ showModal: false }"
     @open-task-modal-{{ $task->id }}.window="showModal = true; 
         setTimeout(() => {
             $el.classList.remove('hidden');
             $el.classList.add('flex');
             setTimeout(() => {
                 $el.classList.add('opacity-100');
                 $el.querySelector('.modal-content').classList.remove('translate-y-4', 'scale-95');
                 $el.querySelector('.modal-content').classList.add('translate-y-0', 'scale-100');
             }, 10);
         }, 10);"
     @click.self="showModal = false; 
         $el.classList.remove('opacity-100');
         $el.querySelector('.modal-content').classList.remove('translate-y-0', 'scale-100');
         $el.querySelector('.modal-content').classList.add('translate-y-4', 'scale-95');
         setTimeout(() => { $el.classList.add('hidden'); $el.classList.remove('flex'); }, 300);">

    <div class="modal-content modal-panel bg-white w-full max-w-2xl mx-auto overflow-hidden transform transition-all duration-300 translate-y-4 scale-95">
        {{-- ヘッダー --}}
        <div class="px-6 py-4 border-b flex justify-between items-center bg-purple-600/10">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">グループタスク詳細</h3>
                <p class="text-sm text-gray-600 mt-1">編集はできません</p>
            </div>
            <button 
                type="button"
                @click="showModal = false; 
                    $el.closest('.modal').classList.remove('opacity-100');
                    $el.closest('.modal').querySelector('.modal-content').classList.remove('translate-y-0', 'scale-100');
                    $el.closest('.modal').querySelector('.modal-content').classList.add('translate-y-4', 'scale-95');
                    setTimeout(() => { $el.closest('.modal').classList.add('hidden'); $el.closest('.modal').classList.remove('flex'); }, 300);"
                class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-600 rounded p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- コンテンツ --}}
        <div class="p-6 flex-1 overflow-y-auto">
            {{-- タスク情報 --}}
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3">{{ $task->title }}</h4>
                
                @if($task->description)
                    <p class="text-sm text-gray-700 mb-4">{{ $task->description }}</p>
                @endif
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">期限:</span>
                        <span class="font-medium">{{ $task->due_date ? $task->due_date->format('Y/m/d') : '未設定' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">報酬:</span>
                        <span class="font-medium text-purple-600">{{ number_format($task->reward) }}円</span>
                    </div>
                    <div>
                        <span class="text-gray-600">ステータス:</span>
                        @if($task->isPendingApproval())
                            <span class="font-medium text-yellow-600">承認待ち</span>
                        @elseif($task->isApproved())
                            <span class="font-medium text-green-600">承認済み</span>
                        @else
                            <span class="font-medium text-gray-600">未完了</span>
                        @endif
                    </div>
                    @if($task->requires_image)
                        <div>
                            <span class="text-gray-600">画像:</span>
                            <span class="font-medium text-red-500">必須</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 画像アップロード --}}
            @if(!$task->isPendingApproval() && !$task->isApproved())
                <div class="mb-6">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">
                        画像 @if($task->requires_image)<span class="text-red-500">*必須</span>@endif
                    </h5>
                    
                    {{-- 既存画像一覧 --}}
                    @if($task->images->count() > 0)
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            @foreach($task->images as $image)
                                <div class="relative group">
                                    <img src="{{ Storage::url($image->file_path) }}" 
                                         class="w-full h-32 object-cover rounded-lg border cursor-pointer"
                                         onclick="window.open('{{ Storage::url($image->file_path) }}', '_blank')">
                                    <button type="button"
                                            onclick="if(confirm('この画像を削除しますか？')) { document.getElementById('delete-image-form-{{ $image->id }}').submit(); }"
                                            class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <form id="delete-image-form-{{ $image->id }}" 
                                      method="POST" 
                                      action="{{ route('tasks.delete-image', $image) }}" 
                                      class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endforeach
                        </div>
                    @endif
                    
                    {{-- アップロードフォーム --}}
                    <form method="POST" 
                          action="{{ route('tasks.upload-image', $task) }}" 
                          enctype="multipart/form-data"
                          class="flex gap-2">
                        @csrf
                        <input type="file" name="image" accept="image/*" required
                               class="flex-1 text-sm border border-gray-300 rounded-lg p-2">
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm whitespace-nowrap">
                            アップロード
                        </button>
                    </form>
                </div>
            @endif

            {{-- 添付済み画像（承認待ち・承認済みの場合） --}}
            @if($task->isPendingApproval() || $task->isApproved())
                @if($task->images->count() > 0)
                    <div class="mb-6">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">添付画像</h5>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($task->images as $image)
                                <img src="{{ Storage::url($image->file_path) }}" 
                                     class="w-full h-32 object-cover rounded-lg border cursor-pointer"
                                     onclick="window.open('{{ Storage::url($image->file_path) }}', '_blank')">
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- タグ表示 --}}
            @if($task->tags->count() > 0)
                <div class="mb-6">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">タグ</h5>
                    <div class="flex flex-wrap gap-2">
                        @foreach($task->tags as $tag)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        {{-- 完了申請ボタン --}}
        <div class="px-6 py-3 border-t bg-white shrink-0">
            @if(!$task->isPendingApproval() && !$task->isApproved() && $task->canComplete())
                <form method="POST" action="{{ route('tasks.request-approval', $task) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('このタスクの完了を申請しますか？')"
                            class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                        完了申請する
                    </button>
                </form>
            @elseif(!$task->canComplete())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    画像のアップロードが必要です
                </div>
            @endif
        </div>
    </div>
</div>