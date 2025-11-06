<div
     id="task-modal-wrapper"
     x-data="{ modalForceRerender: 0 }"
     class="modal fixed inset-0 z-50 items-center justify-center p-safe modal-overlay bg-gray-900 bg-opacity-75 hidden opacity-0 transition-opacity duration-300"
     data-modal-state="closed"
     >

    {{-- モーダルメインパネル --}}
    <div 
         id="task-modal-content"
         class="modal-content modal-panel bg-white shadow-2xl w-full"
    >
        {{-- ヘッダー --}}
        <div class="px-6 py-4 border-b flex justify-between items-center bg-[#59B9C6]/10">
            <h3 class="text-xl font-semibold text-gray-800">{{ __('タスク登録') }}</h3>
            <button 
                type="button"
                id="close-modal-btn" 
                class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] rounded p-1" 
                aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- コンテンツエリア (3つの状態) --}}
        <div class="p-6 flex-1 overflow-y-auto">
            {{-- 状態 1: 初期入力フォーム --}}
            <div id="modal-state-1" data-modal-view="input">
                <form id="task-form" method="POST" action="{{ route('tasks.store') }}">
                    @csrf
                    
                    {{-- タイトル入力 --}}
                    <div class="mb-4">
                        <label for="taskTitle" class="block text-sm font-medium text-gray-700">{{ __('タスク名') }}</label>
                        <input type="text" id="taskTitle" x-model="$store.dashboard.taskTitle" name="title" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring focus:ring-[#59B9C6]/50">
                    </div>

                    {{-- スパン選択 --}}
                    <div class="mb-4">
                        <label for="taskSpan" class="block text-sm font-medium text-gray-700">{{ __('Time Span') }}</label>
                        <select id="taskSpan" x-model.number="$store.dashboard.taskSpan" name="span" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring focus:ring-[#59B9C6]/50 pr-10">
                            <option value="{{ config('const.task_spans.short') }}">{{ __('短期') }}</option>
                            <option value="{{ config('const.task_spans.mid') }}">{{ __('中期') }}</option>
                            <option value="{{ config('const.task_spans.long') }}">{{ __('長期') }}</option>
                        </select>
                    </div>

                    {{-- 期限入力 (スパンに応じて表示切替) --}}
                    <div class="mb-4">
                        <label for="deadline" class="block text-sm font-medium text-gray-700">{{ __('期限') }}</label>
                        
                        {{-- 短期の場合：日付選択 --}}
                        <div x-show="$store.dashboard.taskSpan == {{ config('const.task_spans.short') }}">
                            <input type="date" 
                                id="due_date_short" 
                                name="due_date"
                                x-model="$store.dashboard.due_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring focus:ring-[#59B9C6]/50"
                                :min="new Date().toISOString().split('T')[0]">
                        </div>

                        {{-- 中期の場合：年選択 --}}
                        <div x-show="$store.dashboard.taskSpan == {{ config('const.task_spans.mid') }}">
                            <select name="due_date" 
                                    x-model="$store.dashboard.due_date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring focus:ring-[#59B9C6]/50 pr-10">
                                @php
                                    $currentYear = date('Y');
                                    $years = range($currentYear, $currentYear + 5);
                                @endphp
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}年</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 長期の場合：テキスト入力 --}}
                        <div x-show="$store.dashboard.taskSpan == {{ config('const.task_spans.long') }}">
                            <input type="text" 
                                name="due_date" 
                                x-model="$store.dashboard.due_date"
                                placeholder="例：5年後"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring focus:ring-[#59B9C6]/50">
                        </div>
                    </div>

                    {{-- タグ入力 --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('タグ') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <label class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 cursor-pointer transition">
                                    <input type="checkbox" 
                                        name="tags[]" 
                                        value="{{ $tag->id }}"
                                        x-model="$store.dashboard.selectedTags"
                                        class="form-checkbox h-4 w-4 text-[#59B9C6] focus:ring-[#59B9C6] border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ボタンエリア --}}
                    <div class="flex justify-end space-x-3 border-t pt-4">
                        <button type="submit" id="simple-register-btn" :disabled="!$store.dashboard.taskTitle"
                                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('登録') }}
                        </button>
                        <button type="button" id="decompose-btn" :disabled="!$store.dashboard.taskTitle"
                                class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#59B9C6] hover:bg-[#4AA0AB] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ __('AIで分解') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- 状態 2: AI提案レビュー --}}
            <div id="modal-state-2" 
                data-modal-view="decomposition" 
                style="display: none;">

                {{-- 生成されたタグと提案数の表示 --}}
                <div class="p-3 bg-blue-50 rounded-lg mb-4">
                    <p class="text-sm text-gray-700">
                        <strong>関連タグ:</strong> 
                        <span id="generated-tag-display" class="inline-block px-2 py-1 bg-blue-200 text-blue-800 rounded text-xs ml-2">タグなし</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        提案タスク数: <strong id="proposed-task-count">0</strong>件
                    </p>
                </div>

                {{-- 提案されたタスクのリスト（スクロール可能エリア） --}}
                <div class="max-h-[50vh] overflow-y-auto space-y-3 pr-2 mb-4" id="proposed-tasks-container">
                    {{-- 「タスクなし」メッセージ（JavaScript で制御） --}}
                    <div id="no-tasks-message" class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="font-medium">提案されたタスクがありません</p>
                    </div>
                    
                    {{-- タスクリスト（JavaScript で動的に生成） --}}
                    <div id="tasks-list"></div>
                </div>

                {{-- ボタンエリア --}}
                <div class="flex justify-end gap-3 border-t pt-4">
                    <button 
                        type="button"
                        id="cancel-decomposition-btn"
                        class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition text-sm font-medium">
                        キャンセル
                    </button>
                    <button 
                        type="button"
                        id="refine-proposal-btn"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition text-sm font-medium">
                        再提案
                    </button>
                    <button 
                        type="button"
                        id="adopt-proposal-btn"
                        class="px-4 py-2 bg-[#59B9C6] text-white rounded-md hover:bg-[#4AA0AB] transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        <span>この提案を受け入れる (<span id="adopt-task-count">0</span>件)</span>
                    </button>
                </div>
            </div>

            {{-- 状態 3: 再提案の観点入力 --}}
            <div id="modal-state-3" data-modal-view="refine" class="hidden">
                <div class="mb-4">
                    <label for="refinementPoints" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('再提案の観点') }}
                    </label>
                    <textarea 
                        id="refinementPoints" 
                        x-model="$store.dashboard.refinementPoints"
                        rows="4"
                        placeholder="改善してほしい点や追加してほしい観点を入力してください"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring focus:ring-[#59B9C6]/50"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 border-t pt-4">
                    <button 
                        type="button"
                        id="cancel-refine-btn"
                        class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition">
                        {{ __('キャンセル') }}
                    </button>
                    <button 
                        type="button"
                        id="submit-refine-btn" 
                        :disabled="!$store.dashboard.refinementPoints"
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#59B9C6] hover:bg-[#4AA0AB] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('再提案を依頼') }}
                    </button>
                </div>
            </div>
        </div>
        
        {{-- ローディングオーバーレイ --}}
        <div id="modal-loading-overlay" class="absolute inset-0 bg-white bg-opacity-95 items-center justify-center z-10 rounded-xl hidden">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 text-[#59B9C6] mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-700 font-medium">AIでタスクを分解しています...</p>
            </div>
        </div>
    </div>
</div>