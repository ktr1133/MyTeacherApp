<div id="group-task-detail-modal-{{ $task->id }}" class="fixed inset-0 z-[70] hidden">
    {{-- オーバーレイ --}}
    <div class="modal-overlay fixed inset-0 bg-gray-900/75 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        {{-- モーダルコンテンツ --}}
        <div class="modal-content bg-white dark:bg-gray-900 w-full max-w-2xl shadow-2xl rounded-2xl opacity-0 scale-95 transform transition-all duration-300 flex flex-col max-h-[90vh]">
            {{-- ヘッダー --}}
            <div class="px-6 py-4 border-b flex justify-between items-center bg-purple-600/10 shrink-0">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">グループタスク詳細</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">編集はできません</p>
                </div>
                <button 
                    class="modal-close-btn p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                    aria-label="閉じる">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- スクロール可能なコンテンツエリア --}}
            <div class="flex-1 overflow-y-auto px-6 py-6 custom-scrollbar">
                {{-- タスク情報 --}}
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">{{ $task->title }}</h4>
                    
                    @if($task->description)
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">{{ $task->description }}</p>
                    @endif
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">期間:</span>
                            <span class="font-medium">{{ $task->getSpanLabel() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">期限:</span>
                            @if($task->due_date)
                                <span class="font-medium"><x-user-local-time :datetime="$task->due_date" format="Y/m/d" /></span>
                            @else
                                <span class="font-medium">未設定</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-600">承認:</span>
                            <span class="font-medium">
                                @if($task->isPendingApproval())
                                    <span class="text-yellow-600">承認待ち</span>
                                @elseif($task->isApproved())
                                    <span class="text-green-600">承認済み</span>
                                @else
                                    <span class="text-gray-500">未申請</span>
                                @endif
                            </span>
                        </div>
                        @if($task->requires_image)
                            <div>
                                <span class="text-gray-600">画像:</span>
                                <span class="font-medium text-red-500">必須</span>
                            </div>
                        @endif
                    </div>
                </div>
    
                {{-- 既存画像一覧（すべての状態で表示） --}}
                @if($task->images->count() > 0)
                    <div class="mb-6">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">
                            アップロード済み画像 
                            <span class="text-xs text-gray-500">({{ $task->images->count() }}/3枚)</span>
                        </h5>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($task->images as $image)
                                <div class="relative group">
                                    <img src="{{ Storage::disk('s3')->url($image->file_path) }}" 
                                         alt="Task Image" 
                                         class="w-full h-48 sm:h-40 object-cover rounded-lg border-2 border-gray-200 cursor-pointer hover:border-purple-500 hover:shadow-lg transition-all duration-200"
                                         onclick="openImageModal('{{ Storage::disk('s3')->url($image->file_path) }}')">
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition rounded-lg pointer-events-none"></div>
                                    <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
                                        <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                        クリックで拡大
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 画像選択・プレビュー（未完了の場合のみ） --}}
                @if(!$task->isPendingApproval() && !$task->isApproved() && !$task->completed_at)
                    <div class="mb-6">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">
                            画像を追加 
                            @if($task->requires_image)
                                <span class="text-red-500">*必須</span>
                            @endif
                            <span class="text-xs text-gray-500">
                                （最大{{ 3 - $task->images()->count() }}枚まで追加可能）
                            </span>
                        </h5>
                        
                        {{-- ファイル選択 --}}
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-500 transition">
                            <input 
                                type="file" 
                                id="approval-images-{{ $task->id }}"
                                name="images[]" 
                                accept="image/*" 
                                multiple
                                form="approval-form-{{ $task->id }}"
                                data-existing-count="{{ $task->images()->count() }}"
                                class="hidden"
                                @if($task->requires_image && $task->images()->count() === 0) required @endif
                            >
                            <label for="approval-images-{{ $task->id }}" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-semibold text-purple-600">クリックして画像を選択</span>
                                    または ドラッグ&ドロップ
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    PNG, JPG, GIF 形式 (最大10MB)
                                </p>
                            </label>
                        </div>

                        {{-- プレビュー --}}
                        <div class="mt-4 hidden">
                            <h6 class="text-sm font-medium text-gray-700 mb-2">プレビュー</h6>
                            <div id="preview-container-{{ $task->id }}" class="grid grid-cols-3 gap-2"></div>
                        </div>
                    </div>
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
            <div class="px-6 py-3 border-t dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
                @if(!$task->isPendingApproval() && !$task->isApproved() && !$task->completed_at)
                    <form method="POST" 
                          id="approval-form-{{ $task->id }}"
                          action="{{ route('tasks.request-approval', $task) }}"
                          enctype="multipart/form-data"
                          onsubmit="event.preventDefault(); if(window.showConfirmDialog) { window.showConfirmDialog('このタスクの完了を申請しますか？', () => { event.target.submit(); }); } else { if(confirm('このタスクの完了を申請しますか？')) { event.target.submit(); } }">
                        @csrf
                        
                        <button type="submit"
                                class="submit-approval-btn w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed transition"
                                data-requires-image="{{ $task->requires_image ? 'true' : 'false' }}"
                                data-existing-images="{{ $task->images()->count() }}">
                            完了申請する
                        </button>
                        
                        {{-- バリデーションエラー表示 --}}
                        @if($task->requires_image && $task->images()->count() === 0)
                            <p class="text-xs text-red-500 mt-2 text-center image-required-warning">
                                ⚠️ 画像のアップロードが必要です
                            </p>
                        @endif
                    </form>
                @elseif($task->completed_at)
                    <div class="text-center py-3">
                        <p class="text-sm text-gray-600">
                            <svg class="w-5 h-5 inline text-green-600 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            このタスクは完了済みです
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            グループタスクは一度完了すると未完了に戻せません
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 画像拡大表示モーダル --}}
<div id="image-modal-{{ $task->id }}" class="fixed inset-0 z-[80] hidden bg-black/90 backdrop-blur-sm" onclick="closeImageModal({{ $task->id }})">
    <div class="flex items-center justify-center min-h-screen p-4">
        <button class="absolute top-4 right-4 text-white hover:text-gray-300 transition p-2 rounded-full hover:bg-white/10" aria-label="閉じる">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img id="image-modal-img-{{ $task->id }}" src="" alt="拡大画像" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" onclick="event.stopPropagation()">
    </div>
</div>

<script>
    function openImageModal(imageUrl) {
        const taskId = {{ $task->id }};
        const modal = document.getElementById(`image-modal-${taskId}`);
        const img = document.getElementById(`image-modal-img-${taskId}`);
        
        img.src = imageUrl;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function closeImageModal(taskId) {
        const modal = document.getElementById(`image-modal-${taskId}`);
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>
