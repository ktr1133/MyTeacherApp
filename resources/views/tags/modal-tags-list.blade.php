<div id="tag-task-modal"
     class="modal fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay bg-gray-900/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300"
     data-modal-state="closed">
    
    <div class="modal-content bg-white dark:bg-gray-900 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all duration-300 translate-y-4 scale-95 shadow-2xl rounded-2xl">
        
        {{-- ヘッダー --}}
        <div class="tag-modal-header px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h3 id="modal-title" class="text-lg font-bold bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-transparent">
                        タグのタスク管理
                    </h3>
                    <p class="text-xs text-blue-600 dark:text-blue-400">タスクの紐付けと管理</p>
                </div>
            </div>
            <button type="button" id="close-tag-task-modal" 
                    class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-blue-500/10 dark:hover:bg-blue-500/20 rounded-lg transition" 
                    aria-label="閉じる">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- スクロール可能なコンテンツエリア --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar">
            
            {{-- タグ情報 --}}
            <div class="mb-6 p-4 bg-gradient-to-br from-blue-500/10 to-purple-50 dark:from-blue-500/20 dark:to-purple-900/20 rounded-lg border border-blue-500/20">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    選択中のタグ
                </div>
                <div id="current-tag-badge" 
                     class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg">
                    <!-- filled by JS -->
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- 紐づくタスク一覧 --}}
                <div class="bento-card rounded-xl overflow-hidden border border-blue-500/20 dark:border-blue-500/30">
                    <div class="px-4 py-3 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            関連するタスク
                        </h4>
                        <span id="linked-count" 
                              class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow">
                              0
                        </span>
                    </div>
                    <ul id="linked-tasks" class="max-h-[45vh] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 custom-scrollbar">
                        <li class="p-8 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-sm">タスクがありません</p>
                        </li>
                    </ul>
                </div>

                {{-- タスクを追加 --}}
                <div class="bento-card rounded-xl overflow-hidden border border-blue-500/20 dark:border-blue-500/30">
                    <div class="px-4 py-3 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10">
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            タスクを追加
                        </h4>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="available-task-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                追加するタスクを選択
                            </label>
                            <select id="available-task-select"
                                    class="w-full px-4 py-2.5 border border-blue-500/30 dark:border-blue-500/40 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                <option value="">タスクを選択してください</option>
                            </select>
                        </div>
                        
                        <button id="attach-task-btn"
                                class="w-full inline-flex items-center justify-center rounded-lg text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 shadow-lg hover:shadow-xl px-5 py-2.5 text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            追加する
                        </button>
                        
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                            <p class="text-xs text-blue-600 dark:text-blue-400 flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span>一覧に無い場合はタスク画面から新規作成してください。</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- フッター --}}
        <div class="px-6 py-4 border-t border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10 flex justify-end shrink-0">
            <button type="button" id="close-tag-task-modal-bottom"
                    class="inline-flex items-center justify-center px-5 py-2 border-2 border-blue-500/30 text-sm font-semibold rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-blue-500/10 dark:hover:bg-blue-500/20 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                閉じる
            </button>
        </div>
    </div>
</div>