<div
    id="group-task-detail-modal-{{ $task->id }}"
    x-data="{ 
        showModal: false,
        title: {{ Js::from($task->title) }},
        description: {{ Js::from($task->description ?? '') }},
        span: {{ $task->span ?? config('const.task_spans.mid') }},
        due_date: {{ Js::from($task->due_date ?? '') }},
        selectedTags: {{ Js::from($task->tags->pluck('id')->toArray()) }},
        previewImages: [], // プレビュー画像の配列
        selectedFiles: [], // 選択されたファイルオブジェクト
        
        open() {
            this.showModal = true;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.showModal = false;
            document.body.classList.remove('overflow-hidden');
            // プレビューをクリア
            this.previewImages = [];
            this.selectedFiles = [];
        },
        submit() {
            document.getElementById('edit-task-form-{{ $task->id }}').submit();
        },
        
        // 画像選択時の処理
        handleImageSelect(event) {
            const files = Array.from(event.target.files);
            const existingCount = {{ $task->images()->count() }};
            const maxFiles = 3 - existingCount;
            
            // 枚数制限チェック
            if (files.length > maxFiles) {
                alert(`画像は最大${maxFiles}枚までアップロードできます。`);
                event.target.value = '';
                return;
            }
            
            // ファイルオブジェクトを保存
            this.selectedFiles = files;
            
            // プレビュー画像を生成
            this.previewImages = [];
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewImages.push({
                        url: e.target.result,
                        name: file.name,
                        size: (file.size / 1024).toFixed(2) + ' KB',
                        index: index
                    });
                };
                reader.readAsDataURL(file);
            });
        },
        
        // プレビュー画像を削除
        removePreviewImage(index) {
            this.previewImages = this.previewImages.filter((_, i) => i !== index);
            this.selectedFiles = this.selectedFiles.filter((_, i) => i !== index);
            
            // input要素をリセット（単純なクリアでは不十分）
            const input = document.getElementById('approval-images-{{ $task->id }}');
            if (input) {
                input.value = '';
            }
        }
    }"
    @open-task-modal-{{ $task->id }}.window="open()"
    @keydown.escape.window="showModal && close()"
>

    <div
        x-show="showModal"
        x-transition.opacity
        @click="close()"
        class="fixed inset-0 z-50 bg-gray-900/75 backdrop-blur-sm flex items-center justify-center p-4"
        style="display: none;"
    >
        {{-- コンテンツ --}}
        <div
            @click.stop
            x-show="showModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="modal-content modal-panel bg-white dark:bg-gray-900 w-full max-w-2xl shadow-2xl rounded-2xl"
        >
            {{-- ヘッダー --}}
            <div class="px-6 py-4 border-b flex justify-between items-center bg-purple-600/10">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">グループタスク詳細</h3>
                    <p class="text-sm text-gray-600 mt-1">編集はできません</p>
                </div>
                <button 
                    @click="close()"
                    class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                    aria-label="閉じる">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- スクロール可能なコンテンツエリア --}}
            <div class="flex-1 overflow-y-auto px-6 py-6 modal-body custom-scrollbar">
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
    
                {{-- 既存画像一覧（すべての状態で表示） --}}
                @if($task->images->count() > 0)
                    <div class="mb-6">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">
                            添付済み画像（{{ $task->images->count() }}枚）
                        </h5>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($task->images as $image)
                                <div class="relative group">
                                    <img src="{{ Storage::url($image->file_path) }}" 
                                         class="w-full h-32 object-cover rounded-lg border cursor-pointer"
                                         onclick="window.open('{{ Storage::url($image->file_path) }}', '_blank')"
                                         alt="タスク画像">
                                    
                                    {{-- 未完了の場合のみ削除ボタンを表示 --}}
                                    @if(!$task->isPendingApproval() && !$task->isApproved())
                                        <button type="button"
                                                onclick="if(confirm('この画像を削除しますか？')) { document.getElementById('delete-image-form-{{ $image->id }}').submit(); }"
                                                class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                
                                {{-- 画像削除フォーム --}}
                                @if(!$task->isPendingApproval() && !$task->isApproved())
                                    <form id="delete-image-form-{{ $image->id }}" 
                                          method="POST" 
                                          action="{{ route('tasks.delete-image', $image) }}" 
                                          class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 画像選択・プレビュー（未完了の場合のみ） --}}
                @if(!$task->isPendingApproval() && !$task->isApproved())
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
                                @change="handleImageSelect($event)"
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
                        <div x-show="previewImages.length > 0" x-transition class="mt-4">
                            <h6 class="text-sm font-medium text-gray-700 mb-2">プレビュー</h6>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="(preview, index) in previewImages" :key="index">
                                    <div class="relative group">
                                        <img :src="preview.url" 
                                             class="w-full h-32 object-cover rounded-lg border"
                                             :alt="preview.name">
                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs p-1 rounded-b-lg">
                                            <p class="truncate" x-text="preview.name"></p>
                                            <p x-text="preview.size"></p>
                                        </div>
                                        <button type="button"
                                                @click="removePreviewImage(index)"
                                                class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
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
            <div class="px-6 py-3 border-t bg-white shrink-0">
                @if(!$task->isPendingApproval() && !$task->isApproved())
                    <form method="POST" 
                          action="{{ route('tasks.request-approval', $task) }}"
                          enctype="multipart/form-data"
                          onsubmit="return confirm('このタスクの完了を申請しますか？')">
                        @csrf
                        
                        {{-- 選択された画像を送信 --}}
                        <input type="file" 
                               name="images[]" 
                               multiple 
                               accept="image/*"
                               x-ref="hiddenFileInput"
                               class="hidden"
                               @change="handleImageSelect($event)">
                        
                        <button type="submit"
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!canSubmit()"
                                x-init="
                                    canSubmit = () => {
                                        const requiresImage = {{ $task->requires_image ? 'true' : 'false' }};
                                        const existingImages = {{ $task->images()->count() }};
                                        const newImages = previewImages.length;
                                        
                                        if (requiresImage && existingImages === 0 && newImages === 0) {
                                            return false;
                                        }
                                        
                                        return true;
                                    }
                                ">
                            完了申請する
                        </button>
                        
                        {{-- バリデーションエラー表示 --}}
                        @if($task->requires_image && $task->images()->count() === 0)
                            <p class="text-xs text-red-500 mt-2 text-center" x-show="previewImages.length === 0">
                                ⚠️ 画像のアップロードが必要です
                            </p>
                        @endif
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>