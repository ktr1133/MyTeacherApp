@props(['task'])

<div data-task-modal="{{ $task->id }}" class="hidden">
    {{-- モーダルオーバーレイ --}}
    <div 
        data-modal-overlay
        class="hidden fixed inset-0 z-[60] bg-gray-900/75 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        
        {{-- モーダルコンテンツ --}}
        <div 
            data-modal-content
            class="modal-content modal-panel bg-white dark:bg-gray-900 w-full max-w-2xl shadow-2xl rounded-2xl opacity-0 scale-95 transition-all duration-300">
            
            {{-- ヘッダー --}}
            <div class="px-6 py-4 border-b border-gray-200/50 dark:border-gray-700/50 modal-header-gradient flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
                        タスク編集
                    </h3>
                </div>
                <button 
                    data-close-modal
                    class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                    aria-label="閉じる">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- スクロール可能なコンテンツエリア --}}
            <div class="flex-1 overflow-y-auto px-6 py-6 modal-body custom-scrollbar">
                <form id="edit-task-form-{{ $task->id }}" method="POST" action="{{ route('tasks.update', $task->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- タイトル --}}
                    <div>
                        <label for="title-{{ $task->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            タスク名 <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title-{{ $task->id }}"
                            name="title"
                            value="{{ $task->title }}"
                            required
                            placeholder="タスク名を入力"
                            class="search-input-glow w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent transition text-sm placeholder-gray-400">
                    </div>

                    {{-- 詳細説明 --}}
                    <div>
                        <label for="description-{{ $task->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                            </svg>
                            詳細説明
                        </label>
                        <textarea 
                            id="description-{{ $task->id }}"
                            name="description"
                            rows="5"
                            placeholder="タスクの詳細な説明を入力してください"
                            class="search-input-glow w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent transition text-sm placeholder-gray-400 resize-none custom-scrollbar">{{ $task->description ?? '' }}</textarea>
                    </div>

                    {{-- スパンと期限のグリッド --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- スパン選択 --}}
                        <div>
                            <label for="span-{{ $task->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                スパン <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="span-{{ $task->id }}"
                                name="span"
                                required
                                class="search-input-glow w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent transition text-sm">
                                <option value="{{ config('const.task_spans.short') }}" @selected($task->span == config('const.task_spans.short'))>短期</option>
                                <option value="{{ config('const.task_spans.mid') }}" @selected($task->span == config('const.task_spans.mid'))>中期</option>
                                <option value="{{ config('const.task_spans.long') }}" @selected($task->span == config('const.task_spans.long'))>長期</option>
                            </select>
                        </div>

                        {{-- 期限 --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                期限
                            </label>
                            
                            {{-- 短期: 日付入力 --}}
                            <div data-due-date-short class="{{ $task->span == config('const.task_spans.short') ? '' : 'hidden' }}">
                                <input 
                                    type="date"
                                    name="due_date"
                                    value="{{ $task->due_date ? (is_string($task->due_date) ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : $task->due_date->format('Y-m-d')) : '' }}"
                                    min="{{ date('Y-m-d') }}"
                                    class="search-input-glow w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent transition text-sm">
                            </div>

                            {{-- 中期: 年選択 --}}
                            <div data-due-date-mid class="{{ $task->span == config('const.task_spans.mid') ? '' : 'hidden' }}">
                                <select 
                                    name="due_date"
                                    class="search-input-glow w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent transition text-sm">
                                    @php
                                        $currentYear = date('Y');
                                        $years = range($currentYear, $currentYear + 5);
                                        $taskDueYear = $task->due_date instanceof \Illuminate\Support\Carbon ? $task->due_date->year : (is_numeric($task->due_date) ? (int)$task->due_date : null);
                                    @endphp
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" @selected($taskDueYear == $year)>{{ $year }}年</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 長期: テキスト入力 --}}
                            <div data-due-date-long class="{{ $task->span == config('const.task_spans.long') ? '' : 'hidden' }}">
                                <input 
                                    type="text"
                                    name="due_date"
                                    value="{{ $task->due_date instanceof \Illuminate\Support\Carbon ? $task->due_date->format('Y-m-d') : $task->due_date }}"
                                    placeholder="例：5年後"
                                    class="search-input-glow w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent transition text-sm placeholder-gray-400">
                            </div>
                        </div>
                    </div>

                    {{-- 画像アップロード（グループタスクのみ） --}}
                    @if($task->requires_approval)
                        <div class="bento-card p-4 rounded-xl">
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                </svg>
                                画像 @if($task->requires_image)<span class="text-red-500">*必須</span>@endif
                            </label>
                            
                            {{-- 既存画像一覧 --}}
                            @if($task->images->count() > 0)
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    @foreach($task->images as $image)
                                        <div class="image-preview-card relative group">
                                            <img src="{{ Storage::url($image->file_path) }}" 
                                                 class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                            <button type="button"
                                                    onclick="event.preventDefault(); if(window.showConfirmDialog) { window.showConfirmDialog('この画像を削除しますか？', () => { document.getElementById('delete-image-form-{{ $image->id }}').submit(); }); } else { if(confirm('この画像を削除しますか？')) { document.getElementById('delete-image-form-{{ $image->id }}').submit(); } }"
                                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition shadow-lg">
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
                            <form id="upload-image-form-{{ $task->id }}" 
                                  method="POST" 
                                  action="{{ route('tasks.upload-image', $task) }}" 
                                  enctype="multipart/form-data"
                                  class="flex gap-2">
                                @csrf
                                <input type="file" name="image" accept="image/*" required
                                       class="flex-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-[#59B9C6] file:to-purple-600 file:text-white hover:file:from-[#4AA0AB] hover:file:to-purple-700 transition">
                                <button type="submit"
                                        class="dashboard-btn-primary px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-lg hover:shadow-xl transition">
                                    アップロード
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- タグ選択 --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            タグ
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags ?? [] as $tag)
                                <label class="tag-chip inline-flex items-center px-4 py-2 rounded-xl cursor-pointer transition {{ $task->tags->contains($tag->id) ? 'bg-gradient-to-r from-[#59B9C6] to-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                    <input 
                                        type="checkbox" 
                                        name="tags[]" 
                                        value="{{ $tag->id }}"
                                        @checked($task->tags->contains($tag->id))
                                        class="sr-only">
                                    <span class="text-sm font-medium">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            {{-- フッター --}}
            <div class="px-6 py-4 border-t border-gray-200/50 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center shrink-0 backdrop-blur-sm">
                {{-- 削除ボタン（左側） --}}
                <button 
                    type="button"
                    onclick="event.preventDefault(); if(window.showConfirmDialog) { window.showConfirmDialog('このタスクを削除しますか？', () => { document.getElementById('delete-task-form-{{ $task->id }}').submit(); }); } else { if(confirm('このタスクを削除しますか？')) { document.getElementById('delete-task-form-{{ $task->id }}').submit(); } }"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold text-red-600 hover:text-white hover:bg-red-600 rounded-lg border-2 border-red-600 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    削除
                </button>

                {{-- 右側のボタン群 --}}
                <div class="flex gap-3">
                    <button 
                        type="button"
                        data-close-modal
                        class="inline-flex items-center px-6 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-400 dark:hover:border-gray-500 transition">
                        キャンセル
                    </button>
                    <button 
                        type="button"
                        data-submit-form
                        class="dashboard-btn-primary inline-flex items-center px-6 py-2.5 text-sm font-semibold text-white rounded-xl shadow-lg hover:shadow-xl transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 削除用フォーム --}}
    <form id="delete-task-form-{{ $task->id }}" method="POST" action="{{ route('tasks.destroy') }}" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" name="task_id" value="{{ $task->id }}">
    </form>
</div>