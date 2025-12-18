<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/group-tasks/index.js'])
    @endpush

    <x-layouts.avatar-event-common />
    <x-confirm-dialog />
    <x-alert-dialog />

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm {{ $isChildTheme ? 'child-theme' : '' }}">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            data-sidebar-toggle="mobile"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                @if(!$isChildTheme)
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        グループタスク管理
                                    </h1>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">作成したグループタスクの編集</p>
                                @else
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        グループタスク
                                    </h1>
                                @endif
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">{{ !$isChildTheme ? 'タスクリストへ戻る' : 'ToDoにもどる' }}</span>
                    </a>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl shadow-sm">
                            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl shadow-sm">
                            <ul class="list-disc list-inside text-red-800 dark:text-red-200">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($groupTasks->isEmpty())
                        <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                            <div class="p-12 text-center">
                                <div class="dashboard-header-icon w-16 h-16 rounded-xl flex items-center justify-center shadow-lg mx-auto">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-gray-100">{{ !$isChildTheme ? 'グループタスクがありません' : 'まだないよ' }}</h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ !$isChildTheme ? '編集可能なグループタスクはまだ作成されていません。' : 'グループタスクをつくってみよう！' }}</p>
                            </div>
                        </div>
                    @else
                        {{-- Bento-style グリッドレイアウト --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6">
                            @foreach($groupTasks as $groupTask)
                                <div class="bento-card group relative rounded-2xl shadow-lg hover:shadow-2xl p-4 lg:p-6 transition-all duration-300 cursor-pointer"
                                     onclick="window.location.href='{{ route('group-tasks.edit', $groupTask['group_task_id'] ?? '') }}';">
                                    
                                    {{-- ヘッダー --}}
                                    <div class="flex items-start justify-between mb-3 lg:mb-4 gap-2">
                                        <div class="flex items-center gap-2 lg:gap-3 min-w-0 flex-1 overflow-hidden">
                                            <span class="inline-flex items-center justify-center w-8 h-8 lg:w-10 lg:h-10 rounded-lg lg:rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 text-white shadow-lg flex-shrink-0">
                                                <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                                </svg>
                                            </span>
                                            <h3 class="text-base lg:text-lg font-bold text-gray-900 dark:text-white truncate min-w-0">
                                                {{ $groupTask['title'] ?? '-' }}
                                            </h3>
                                        </div>
                                    </div>

                                    {{-- タスク情報 --}}
                                    <div class="space-y-2 mb-4">
                                        {{-- 割当人数 --}}
                                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                            </svg>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $groupTask['assigned_count'] ?? 0 }}人</span>
                                            <span>に割当</span>
                                        </div>

                                        {{-- 期限 --}}
                                        @if(!empty($groupTask['due_date']))
                                            @php
                                                // due_dateがdatetime形式かどうかをチェック
                                                try {
                                                    $dueDate = \Carbon\Carbon::parse($groupTask['due_date']);
                                                    $isOverdue = $dueDate->isPast();
                                                    $isToday = $dueDate->isToday();
                                                    $isTomorrow = $dueDate->isTomorrow();
                                                    $isValidDate = true;
                                                } catch (\Exception $e) {
                                                    // パース失敗時は日本語テキストとして扱う
                                                    $isValidDate = false;
                                                }
                                            @endphp
                                            <div class="flex items-center gap-2 text-sm {{ $isValidDate && $isOverdue ? 'text-red-600 dark:text-red-400' : ($isValidDate && $isToday ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400') }}">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="font-medium">
                                                    @if($isValidDate)
                                                        {{ $dueDate->format('Y/m/d') }}
                                                        @if($isOverdue)
                                                            (期限切れ)
                                                        @elseif($isToday)
                                                            (今日)
                                                        @elseif($isTomorrow)
                                                            (明日)
                                                        @endif
                                                    @else
                                                        {{ $groupTask['due_date'] }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endif

                                        {{-- 報酬 --}}
                                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4 flex-shrink-0 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($groupTask['reward'] ?? 0) }}</span>
                                            <span>ポイント</span>
                                        </div>
                                    </div>

                                    {{-- アクションボタン --}}
                                    <div class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <a href="{{ route('group-tasks.edit', $groupTask['group_task_id'] ?? '') }}"
                                           onclick="event.stopPropagation();"
                                           class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 rounded-lg hover:shadow-lg transition-all duration-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>編集</span>
                                        </a>
                                        <button type="button"
                                                onclick="event.stopPropagation(); confirmDelete('{{ $groupTask['group_task_id'] ?? '' }}');"
                                                class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 hover:shadow-lg transition-all duration-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <span>削除</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    {{-- 削除確認フォーム --}}
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        function confirmDelete(groupTaskId) {
            if (confirm('このグループタスクを削除してもよろしいですか？\n関連する全てのタスクが削除されます。')) {
                const form = document.getElementById('delete-form');
                form.action = `/group-tasks/${groupTaskId}`;
                form.submit();
            }
        }
    </script>
</x-app-layout>
