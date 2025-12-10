<div
     id="group-task-modal-wrapper"
     class="modal fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay bg-gray-900/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300"
     data-modal-state="closed">

    {{-- モーダルメインパネル --}}
    <div 
         id="group-task-modal-content"
         class="modal-content bg-white dark:bg-gray-900 w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all duration-300 translate-y-4 scale-95 shadow-2xl rounded-2xl">

        {{-- ヘッダー - パープルテーマ --}}
        <div class="px-6 py-4 border-b border-purple-200/50 dark:border-purple-700/50 flex justify-between items-center shrink-0 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        グループタスク登録
                    </h3>
                    <p class="text-xs text-purple-600 dark:text-purple-400">メンバーに割り当てるタスクを作成</p>
                </div>
            </div>
            <button 
                type="button"
                id="close-group-modal-btn" 
                class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition" 
                aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- スクロール可能なコンテンツエリア --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar">
            <form id="group-task-form" method="POST" action="{{ route('tasks.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="is_group_task" value="1">
                
                {{-- 画像必須設定 --}}
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-4 rounded-xl border border-purple-200/50 dark:border-purple-700/50">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="requires_image" value="1"
                               class="mt-0.5 w-5 h-5 text-purple-600 focus:ring-purple-600 rounded transition">
                        <div class="flex-1">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                </svg>
                                完了時に画像添付を必須にする
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">タスク完了時に証拠画像のアップロードが必要になります</p>
                        </div>
                    </label>
                </div>

                {{-- 承認必須設定 --}}
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 p-4 rounded-xl border border-amber-200/50 dark:border-amber-700/50">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="requires_approval" value="1" checked
                               class="mt-0.5 w-5 h-5 text-amber-600 focus:ring-amber-600 rounded transition">
                        <div class="flex-1">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                完了時に承認を必須にする（推奨）
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">タスク完了時に親の承認が必要になります。チェックを外すと即座に完了扱いになります。</p>
                        </div>
                    </label>
                </div>
                
                {{-- 担当者選択 --}}
                <div>
                    <label for="assignedUserId" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        担当者（任意）
                    </label>
                    <select id="assignedUserId" name="assigned_user_id"
                            class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm">
                        <option value="">未割り当て</option>
                        @if(Auth::user()->group)
                            @foreach(Auth::user()->group->users as $member)
                                <option value="{{ $member->id }}">{{ $member->username }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- タスク選択方式 --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        タスク作成方法
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="group-task-chip inline-flex items-center justify-center px-4 py-2.5 rounded-lg cursor-pointer transition border-2">
                            <input type="radio" name="task_mode" value="new" checked class="sr-only">
                            <span class="text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                新規作成
                            </span>
                        </label>
                        <label class="group-task-chip inline-flex items-center justify-center px-4 py-2.5 rounded-lg cursor-pointer transition border-2">
                            <input type="radio" name="task_mode" value="template" class="sr-only">
                            <span class="text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                                </svg>
                                テンプレート
                            </span>
                        </label>
                    </div>
                </div>

                {{-- 新規作成フォーム --}}
                <div id="new-task-form" class="space-y-4">
                    <div>
                        <label for="groupTaskTitle" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            タスク名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="groupTaskTitle" name="title" required
                               placeholder="例：部屋の掃除"
                               class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm placeholder-gray-400">
                    </div>

                    <div>
                        <label for="groupTaskDescription" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            説明
                        </label>
                        <textarea id="groupTaskDescription" name="description" rows="3"
                                  placeholder="タスクの詳細な説明を入力してください"
                                  class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm placeholder-gray-400 resize-none custom-scrollbar"></textarea>
                    </div>
                </div>

                {{-- テンプレート選択フォーム --}}
                <div id="template-task-form" style="display: none;" class="space-y-4">
                    <div>
                        <label for="taskTemplate" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            過去のグループタスクから選択
                        </label>
                        <select id="taskTemplate" name="template_task_id"
                                class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm">
                            <option value="">選択してください</option>
                            @foreach($groupTaskTemplates ?? [] as $template)
                                <option value="{{ $template->id }}" data-title="{{ $template->title }}" data-description="{{ $template->description }}" data-reward="{{ $template->reward }}">
                                    {{ $template->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- プレビューエリア --}}
                    <div id="template-preview" class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-4 rounded-lg border border-purple-200/50 dark:border-purple-700/50" style="display: none;">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            プレビュー
                        </h4>
                        <div class="space-y-1.5 text-sm">
                            <p><strong class="text-gray-700 dark:text-gray-300">タイトル:</strong> <span id="preview-title" class="text-gray-600 dark:text-gray-400"></span></p>
                            <p><strong class="text-gray-700 dark:text-gray-300">説明:</strong> <span id="preview-description" class="text-gray-600 dark:text-gray-400"></span></p>
                        </div>
                    </div>
                </div>

                {{-- 期限と報酬 --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="groupDueDate" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <span class="pr-8">期限 <span class="text-red-500">*</span></span>
                        </label>
                        <input type="date" id="groupDueDate" name="due_date" required
                               class="px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm"
                               min="{{ date('Y-m-d') }}">
                    </div>

                    <div>
                        <label for="taskReward" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                            </svg>
                            <span class="pr-8">報酬 <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <input type="number" id="taskReward" name="reward" min="0" step="1" required
                                   class="w-full px-4 py-2.5 pr-12 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm"
                                   placeholder="0">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">円</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- タグ --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        タグ
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <label class="group-tag-chip inline-flex items-center px-3 py-1.5 rounded-lg cursor-pointer transition">
                                <input type="checkbox" 
                                    name="tags[]" 
                                    value="{{ $tag->name }}"
                                    class="sr-only">
                                <span class="text-xs font-medium">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>

        {{-- フッター --}}
        <div class="px-6 py-4 border-t border-purple-200/50 dark:border-purple-700/50 bg-gradient-to-r from-purple-50/50 to-pink-50/50 dark:from-purple-900/10 dark:to-pink-900/10 flex justify-end gap-3 shrink-0">
            <button type="button" id="cancel-group-task-btn"
                    class="inline-flex justify-center items-center px-5 py-2 border-2 border-purple-300 dark:border-purple-600 text-sm font-semibold rounded-lg text-purple-700 dark:text-purple-300 bg-white dark:bg-gray-800 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition">
                キャンセル
            </button>
            <button type="submit" form="group-task-form" id="register-group-task-btn"
                    class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                登録
            </button>
        </div>
    </div>
</div>