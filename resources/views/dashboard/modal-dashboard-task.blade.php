<div
     id="task-modal-wrapper"
     class="modal fixed inset-0 z-50 flex items-center justify-center p-4 {{ $isChildTheme ? 'modal-child-theme' : '' }} modal-overlay bg-gray-900/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300"
     data-modal-state="closed">

    {{-- モーダルメインパネル --}}
    <div 
         id="task-modal-content"
         class="modal-content bg-white dark:bg-gray-900 w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all duration-300 translate-y-4 scale-95 shadow-2xl rounded-2xl">
        
        {{-- ヘッダー - テーマ別デザイン --}}
        <div class="modal-header px-6 py-4 border-b {{ $isChildTheme ? 'border-amber-800/30 bg-gradient-to-r from-amber-500 to-orange-500' : 'border-[#59B9C6]/20 bg-gradient-to-r from-[#59B9C6]/10 to-blue-50' }} flex justify-between items-center shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $isChildTheme ? 'bg-gradient-to-br from-amber-600 to-orange-600' : 'bg-gradient-to-br from-[#59B9C6] to-blue-600' }} flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold {{ $isChildTheme ? 'text-white' : 'bg-gradient-to-r from-[#59B9C6] to-blue-600 bg-clip-text text-transparent' }}">
                        {{ $isChildTheme ? 'クエスト登録' : 'タスク登録' }}
                    </h3>
                    <p class="text-xs {{ $isChildTheme ? 'text-amber-100' : 'text-[#59B9C6]' }}">
                        個人タスクを作成
                    </p>
                </div>
            </div>
            <button type="button"
                    id="close-modal-btn" 
                    class="p-2 {{ $isChildTheme ? 'text-white hover:bg-amber-700/50' : 'text-gray-500 hover:text-gray-700 hover:bg-[#59B9C6]/10' }} rounded-lg transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- スクロール可能なコンテンツエリア --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar {{ $isChildTheme ? 'bg-gradient-to-br from-amber-50/50 to-orange-50/50' : '' }}">
            {{-- 状態 1: 初期入力フォーム --}}
            <div id="modal-state-1" data-modal-view="input">
                <form id="task-form" method="POST" action="{{ route('tasks.store') }}" class="space-y-4">
                    @csrf
                    
                    {{-- タイトル入力 --}}
                    <div>
                        <label for="taskTitle" class="block text-sm font-semibold {{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }} mb-2">
                            <svg class="w-4 h-4 inline-block mr-1 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            {{ $isChildTheme ? 'クエスト名' : 'タスク名' }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="taskTitle" 
                               name="title" 
                               required
                               placeholder="{{ $isChildTheme ? '例：お部屋の掃除' : '例：レポート作成' }}"
                               class="w-full px-4 py-2.5 border {{ $isChildTheme ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500' : 'border-[#59B9C6]/30 dark:border-[#59B9C6]/40 focus:ring-[#59B9C6] focus:border-transparent' }} rounded-lg bg-white dark:bg-gray-800 focus:ring-2 transition text-sm placeholder-gray-400">
                    </div>

                    {{-- スパン選択 --}}
                    <div>
                        <label for="taskSpan" class="block text-sm font-semibold {{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }} mb-2">
                            <svg class="w-4 h-4 inline-block mr-1 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            期間 <span class="text-red-500">*</span>
                        </label>
                        <select id="taskSpan" 
                                name="span" 
                                required
                                class="w-full px-4 py-2.5 border {{ $isChildTheme ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500' : 'border-[#59B9C6]/30 dark:border-[#59B9C6]/40 focus:ring-[#59B9C6] focus:border-transparent' }} rounded-lg bg-white dark:bg-gray-800 focus:ring-2 transition text-sm">
                            <option value="{{ config('const.task_spans.short') }}">{{ !$isChildTheme ? '短期' : 'すぐにやる' }}</option>
                            <option value="{{ config('const.task_spans.mid') }}" selected>{{ !$isChildTheme ? '中期' : '今年中' }}</option>
                            <option value="{{ config('const.task_spans.long') }}">{{ !$isChildTheme ? '長期' : 'いつかやる' }}</option>
                        </select>
                    </div>

                    {{-- 期限入力 (スパンに応じて表示切替) --}}
                    <div>
                        <label class="block text-sm font-semibold {{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }} mb-2">
                            <svg class="w-4 h-4 inline-block mr-1 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            {{ !$isChildTheme ? '期限' : 'しめきり' }}
                        </label>
                        
                        {{-- 短期の場合：日付選択 --}}
                        <div id="due-date-short-container" 
                             class="due-date-field {{ $isChildTheme ? 'due-date-field-child' : '' }}" 
                             style="display: none;">
                            <input type="date" 
                                id="due_date_short" 
                                name="due_date"
                                disabled
                                class="w-full px-4 py-2.5 border {{ $isChildTheme ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500' : 'border-[#59B9C6]/30 dark:border-[#59B9C6]/40 focus:ring-[#59B9C6] focus:border-transparent' }} rounded-lg bg-white dark:bg-gray-800 focus:ring-2 transition text-sm">
                        </div>

                        {{-- 中期の場合：年選択 --}}
                        <div id="due-date-mid-container" 
                             class="due-date-field {{ $isChildTheme ? 'due-date-field-child' : '' }}" 
                             style="display: block;">
                            <select id="due_date_mid"
                                    name="due_date" 
                                    class="w-full px-4 py-2.5 border {{ $isChildTheme ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500' : 'border-[#59B9C6]/30 dark:border-[#59B9C6]/40 focus:ring-[#59B9C6] focus:border-transparent' }} rounded-lg bg-white dark:bg-gray-800 focus:ring-2 transition text-sm">
                                @php
                                    $currentYear = date('Y');
                                    $years = range($currentYear, $currentYear + 5);
                                @endphp
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}年</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 長期の場合：テキスト入力 --}}
                        <div id="due-date-long-container" 
                             class="due-date-field {{ $isChildTheme ? 'due-date-field-child' : '' }}" 
                             style="display: none;">
                            <input type="text" 
                                id="due_date_long"
                                name="due_date" 
                                disabled
                                placeholder="例：5年後"
                                class="w-full px-4 py-2.5 border {{ $isChildTheme ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500' : 'border-[#59B9C6]/30 dark:border-[#59B9C6]/40 focus:ring-[#59B9C6] focus:border-transparent' }} rounded-lg bg-white dark:bg-gray-800 focus:ring-2 transition text-sm placeholder-gray-400">
                        </div>
                    </div>

                    {{-- タグ入力 --}}
                    <div>
                        <label class="block text-sm font-semibold {{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }} mb-2">
                            <svg class="w-4 h-4 inline-block mr-1 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            タグ
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <label class="task-tag-chip inline-flex items-center px-3 py-1.5 rounded-lg cursor-pointer transition {{ $isChildTheme ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" data-tag-id="{{ $tag->id }}">
                                    <input type="checkbox" 
                                        name="tags[]" 
                                        value="{{ $tag->id }}"
                                        class="sr-only tag-checkbox">
                                    <span class="text-xs font-medium">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            {{-- 状態 2: AI提案レビュー --}}
            <div id="modal-state-2" 
                data-modal-view="decomposition" 
                style="display: none;">

                {{-- 生成されたタグと提案数の表示 --}}
                <div class="{{ $isChildTheme ? 'bg-gradient-to-br from-amber-100 to-orange-100 border-amber-300' : 'bg-gradient-to-br from-[#59B9C6]/10 to-blue-50 dark:from-[#59B9C6]/20 dark:to-blue-900/20 border-[#59B9C6]/20' }} p-4 rounded-lg mb-4 border">
                    <p class="text-sm {{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }}">
                        <strong class="flex items-center gap-2">
                            <svg class="w-4 h-4 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            関連タグ:
                        </strong> 
                        <span id="generated-tag-display" class="inline-block px-2 py-1 {{ $isChildTheme ? 'bg-amber-500' : 'bg-[#59B9C6]' }} text-white rounded text-xs ml-2">タグなし</span>
                    </p>
                    <p class="text-xs {{ $isChildTheme ? 'text-amber-800' : 'text-gray-600 dark:text-gray-400' }} mt-2">
                        提案タスク数: <strong id="proposed-task-count" class="{{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}">0</strong>件
                    </p>
                </div>

                {{-- 提案されたタスクのリスト --}}
                <div class="max-h-[45vh] overflow-y-auto space-y-3 mb-4 custom-scrollbar" id="proposed-tasks-container">
                    <div id="no-tasks-message" class="text-center py-8 {{ $isChildTheme ? 'text-amber-800 border-amber-300 bg-amber-50' : 'text-gray-500 border-[#59B9C6]/30 bg-[#59B9C6]/5' }} border-2 border-dashed rounded-lg">
                        <svg class="w-12 h-12 mx-auto mb-2 {{ $isChildTheme ? 'text-amber-400' : 'text-[#59B9C6]/50' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="font-medium">提案されたタスクがありません</p>
                    </div>
                    <div id="tasks-list"></div>
                </div>
            </div>

            {{-- 状態 3: 再提案の観点入力 --}}
            <div id="modal-state-3" data-modal-view="refine" class="hidden">
                <div class="mb-4">
                    <label for="refinementPoints" class="block text-sm font-semibold {{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }} mb-2">
                        <svg class="w-4 h-4 inline-block mr-1 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                        再提案の観点
                    </label>
                    <textarea 
                        id="refinementPoints"
                        rows="4"
                        placeholder="改善してほしい点や追加してほしい観点を入力してください"
                        class="w-full px-4 py-2.5 border {{ $isChildTheme ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500' : 'border-[#59B9C6]/30 dark:border-[#59B9C6]/40 focus:ring-[#59B9C6] focus:border-transparent' }} rounded-lg bg-white dark:bg-gray-800 focus:ring-2 transition text-sm placeholder-gray-400 resize-none custom-scrollbar"></textarea>
                </div>
            </div>
        </div>

        {{-- フッター --}}
        <div class="px-6 py-4 border-t {{ $isChildTheme ? 'border-amber-300 bg-gradient-to-r from-amber-100/50 to-orange-100/50' : 'border-[#59B9C6]/20 dark:border-[#59B9C6]/30 bg-gradient-to-r from-[#59B9C6]/5 to-blue-50/50 dark:from-[#59B9C6]/10 dark:to-blue-900/10' }} flex justify-end gap-3 shrink-0">
            {{-- 状態1のボタン --}}
            <div id="state-1-buttons" class="flex gap-3">
                <button type="submit" form="task-form" id="simple-register-btn" disabled
                        class="inline-flex justify-center items-center px-5 py-2 border-2 {{ $isChildTheme ? 'border-amber-500 text-amber-700 bg-white hover:bg-amber-50' : 'border-[#59B9C6] text-[#59B9C6] bg-white dark:bg-gray-800 hover:bg-[#59B9C6]/10 dark:hover:bg-[#59B9C6]/20' }} text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ !$isChildTheme ? '登録' : 'つくる'}}
                </button>
                <button type="button" id="decompose-btn" disabled
                        class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-sm font-semibold rounded-lg text-white {{ $isChildTheme ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700' : 'bg-gradient-to-r from-[#59B9C6] to-blue-600 hover:from-[#4AA0AB] hover:to-blue-700' }} shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $isChildTheme ? 'focus:ring-green-500' : 'focus:ring-[#59B9C6]' }} transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    {{ !$isChildTheme ? 'AIで分解' : 'こまかくする'}}
                </button>
            </div>

            {{-- 状態2のボタン --}}
            <div id="state-2-buttons" class="flex gap-3" style="display: none;">
                <button type="button" id="cancel-decomposition-btn"
                        class="inline-flex justify-center items-center px-5 py-2 border-2 {{ $isChildTheme ? 'border-amber-300 text-amber-800 bg-white hover:bg-amber-50' : 'border-[#59B9C6]/30 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-[#59B9C6]/10 dark:hover:bg-[#59B9C6]/20' }} text-sm font-semibold rounded-lg transition">
                    キャンセル
                </button>
                <button type="button" id="refine-proposal-btn"
                        class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 shadow-lg hover:shadow-xl transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    再提案
                </button>
                <button type="button" id="adopt-proposal-btn"
                        class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-sm font-semibold rounded-lg text-white {{ $isChildTheme ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700' : 'bg-gradient-to-r from-[#59B9C6] to-blue-600 hover:from-[#4AA0AB] hover:to-blue-700' }} shadow-lg hover:shadow-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>この提案を受け入れる (<span id="adopt-task-count">0</span>件)</span>
                </button>
            </div>

            {{-- 状態3のボタン --}}
            <div id="state-3-buttons" class="flex gap-3" style="display: none;">
                <button type="button" id="cancel-refine-btn"
                        class="inline-flex justify-center items-center px-5 py-2 border-2 {{ $isChildTheme ? 'border-amber-300 text-amber-800 bg-white hover:bg-amber-50' : 'border-[#59B9C6]/30 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-[#59B9C6]/10 dark:hover:bg-[#59B9C6]/20' }} text-sm font-semibold rounded-lg transition">
                    キャンセル
                </button>
                <button type="button" id="submit-refine-btn"
                        disabled
                        class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-sm font-semibold rounded-lg text-white {{ $isChildTheme ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:ring-green-500' : 'bg-gradient-to-r from-[#59B9C6] to-blue-600 hover:from-[#4AA0AB] hover:to-blue-700 focus:ring-[#59B9C6]' }} shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    再提案を依頼
                </button>
            </div>
        </div>
        
        {{-- ローディングオーバーレイ --}}
        <div id="modal-loading-overlay" class="absolute inset-0 {{ $isChildTheme ? 'bg-amber-50/95' : 'bg-white/95 dark:bg-gray-900/95' }} backdrop-blur-sm items-center justify-center z-10 rounded-2xl hidden">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 {{ $isChildTheme ? 'text-amber-600' : 'text-[#59B9C6]' }} mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p id="modal-loading-text" class="{{ $isChildTheme ? 'text-amber-900' : 'text-gray-700 dark:text-gray-300' }} font-medium">
                    {{ $isChildTheme ? 'クエストを分解しています...' : 'AIでタスクを分解しています...' }}
                </p>
                <p class="text-sm {{ $isChildTheme ? 'text-amber-700' : 'text-gray-500 dark:text-gray-400' }} mt-2">しばらくお待ちください</p>
            </div>
        </div>
    </div>
</div>