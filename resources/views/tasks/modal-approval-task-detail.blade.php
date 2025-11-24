<div x-data="{ 
        show: false,
        description: @js($task->description ?? ''),
        originalDescription: @js($task->description ?? ''),
        isDirty: false,
        isSubmitting: false
    }"
    @open-approval-task-modal-{{ $task->id }}.window="show = true; description = originalDescription; isDirty = false;"
    @keydown.escape.window="show && (show = false)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title-{{ $task->id }}"
    role="dialog"
    aria-modal="true">
    
    {{-- オーバーレイ --}}
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity"
         @click="show = false"></div>

    {{-- モーダルコンテンツ --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="modal-panel-lg modal-glass rounded-2xl shadow-2xl transform transition-all w-full max-w-4xl"
             @click.stop>

            {{-- ヘッダー --}}
            <div class="approval-modal-header px-6 py-4 border-b border-gray-200/50 dark:border-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="approval-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="modal-title-{{ $task->id }}" class="text-lg font-bold text-gray-900 dark:text-white">
                            タスク詳細
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400">説明文の編集が可能です</p>
                    </div>
                </div>
                <button type="button"
                        @click="show = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ボディ --}}
            <div class="modal-body p-6 space-y-6">
                {{-- タスク情報 --}}
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-700/30">
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ $task->title }}</h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        {{-- 担当者 --}}
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">担当:</span>
                            <span class="text-gray-900 dark:text-white font-semibold">{{ $task->user->username }}</span>
                        </div>

                        {{-- 完了日時 --}}
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">完了:</span>
                            <span class="text-gray-900 dark:text-white font-semibold"><x-user-local-time :datetime="$task->completed_at" format="Y/m/d H:i" /></span>
                        </div>

                        {{-- 期限 --}}
                        @if($task->deadline)
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">期限:</span>
                                <span class="text-gray-900 dark:text-white font-semibold">{{ $task->deadline->format('Y/m/d') }}</span>
                            </div>
                        @endif

                        {{-- 報酬 --}}
                        @if($task->reward)
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">報酬:</span>
                                <span class="text-purple-600 dark:text-purple-400 font-bold">{{ number_format($task->reward) }}円</span>
                            </div>
                        @endif
                    </div>

                    {{-- タグ --}}
                    @if($task->tags->count() > 0)
                        <div class="mt-3 pt-3 border-t border-yellow-200 dark:border-yellow-700/30">
                            <div class="flex items-center gap-2 flex-wrap">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                                @foreach($task->tags as $tag)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-yellow-500/20 to-orange-500/20 text-yellow-800 dark:text-yellow-200 border border-yellow-500/30">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 説明文（編集可能） --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        説明文
                        <span x-show="isDirty" class="text-xs text-yellow-600 dark:text-yellow-400">(編集中)</span>
                    </label>
                    <textarea 
                        x-model="description"
                        @input="isDirty = (description !== originalDescription)"
                        rows="6"
                        maxlength="500"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:focus:ring-yellow-600 transition resize-none"
                        placeholder="タスクの説明や却下理由を入力してください..."></textarea>
                    <div class="mt-1 flex justify-between items-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            ※ 却下時には理由を記述することをお勧めします
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`${description ? description.length : 0}/500文字`"></p>
                    </div>
                </div>

                {{-- 添付画像 --}}
                @if($task->images->count() > 0)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                            </svg>
                            添付画像 ({{ $task->images->count() }}枚)
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($task->images as $image)
                                <div class="image-preview-card group relative aspect-square rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 hover:border-yellow-500 transition-all cursor-pointer"
                                     onclick="window.open('{{ Storage::url($image->file_path) }}', '_blank')">
                                    <img src="{{ Storage::url($image->file_path) }}" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                         alt="タスク画像">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- フッター --}}
            <div class="px-6 py-4 border-t border-gray-200/50 dark:border-gray-700/50 flex gap-3">
                <button type="button"
                        @click="show = false"
                        class="flex-1 px-6 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                    キャンセル
                </button>
                <button type="button"
                        @click="if (isDirty) { $refs.saveForm.submit(); }"
                        :disabled="!isDirty || isSubmitting"
                        :class="{ 
                            'opacity-50 cursor-not-allowed': !isDirty || isSubmitting,
                            'approval-btn-save': isDirty && !isSubmitting
                        }"
                        class="flex-1 px-6 py-3 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200 disabled:hover:shadow-lg disabled:transform-none">
                    <span x-show="!isSubmitting">保存する</span>
                    <span x-show="isSubmitting" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        保存中...
                    </span>
                </button>

                {{-- 非表示のフォーム --}}
                <form x-ref="saveForm"
                      method="POST"
                      action="{{ route('tasks.update-description', $task) }}"
                      @submit="isSubmitting = true"
                      class="hidden">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="description" x-model="description">
                </form>
            </div>
        </div>
    </div>
</div>