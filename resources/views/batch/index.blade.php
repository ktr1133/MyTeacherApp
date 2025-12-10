<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/batch.css'])
    @endpush
    @php
        $avatarEvent = session('avatar_event');
        $timestamp = microtime(true);
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        
        logger('[Dashboard Blade] Rendering', [
            'avatar_event' => $avatarEvent,
            'timestamp' => $timestamp,
            'session_id' => session()->getId(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'request_path' => request()->path(),
            'referer' => request()->header('referer'),
            'user_agent' => request()->header('user-agent'),
            'all_session' => session()->all(), // セッション全体を確認
            'backtrace' => array_map(fn($t) => [
                'file' => $t['file'] ?? 'unknown',
                'line' => $t['line'] ?? '?',
                'function' => $t['function'] ?? 'unknown',
            ], $backtrace),
        ]);
    @endphp
    @push('scripts')
        {{-- ログインイベント発火 --}}
        <script>
            let avatarEventFired = false;
            let dispatchAttempts = 0;
            
            // DOMContentLoadedを直接使用
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('avatar_event'))
                    if (avatarEventFired) {
                        console.warn('[Dashboard] Avatar event already fired, skipping');
                        return;
                    }
                    
                    const waitForAlpineAvatar = setInterval(() => {
                        dispatchAttempts++;
                        
                        if (window.Alpine && typeof window.dispatchAvatarEvent === 'function') {
                            clearInterval(waitForAlpineAvatar);
                            avatarEventFired = true;

                            setTimeout(() => {
                                window.dispatchAvatarEvent('{{ session('avatar_event') }}');
                            }, 500);
                        }
                        
                        // 5秒（100回）でタイムアウト
                        if (dispatchAttempts > 100) {
                            clearInterval(waitForAlpineAvatar);
                            console.error('[Dashboard] Alpine initialization timeout', {
                                attempts: dispatchAttempts,
                            });
                        }
                    }, 50);
                @endif
            });
        </script>
    @endpush

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            data-sidebar-toggle="mobile"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h1 class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent truncate">
                                    <span class="hidden min-[413px]:inline">タスク自動作成の設定</span>
                                    <span class="min-[413px]:hidden">自動作成設定</span>
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400 hidden min-[413px]:block">スケジュール管理</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('group.edit') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="hidden sm:inline">グループ管理へ</span>
                        </a>

                        <a href="{{ route('batch.scheduled-tasks.create', ['group_id' => $groupId]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="hidden sm:inline">新規作成</span>
                        </a>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-12 space-y-6">
                    
                    {{-- ステータスメッセージ --}}
                    @if (session('success'))
                        <div class="bento-card rounded-xl p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700"
                            role="alert">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-800 dark:text-green-200">成功しました</p>
                                    <p class="text-sm text-green-600 dark:text-green-300">
                                        @if (session('status') === 'scheduled-task-created')
                                            スケジュールタスクを作成しました。
                                        @elseif (session('status') === 'scheduled-task-updated')
                                            スケジュールタスクを更新しました。
                                        @elseif (session('status') === 'scheduled-task-deleted')
                                            スケジュールタスクを削除しました。
                                        @elseif (session('status') === 'scheduled-task-paused')
                                            スケジュールタスクを一時停止しました。
                                        @elseif (session('status') === 'scheduled-task-resumed')
                                            スケジュールタスクを再開しました。
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- スケジュールタスク一覧 --}}
                    @include('batch.partials.scheduled-task-list')
                </div>
            </main>
        </div>
    </div>
</x-app-layout>