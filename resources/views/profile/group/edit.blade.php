<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
        @vite(['resources/css/auth/register-validation.css'])
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
        @vite(['resources/js/profile/profile-validation.js'])
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
    <div x-data="{ showSidebar: false }" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
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
                            @click="showSidebar = true"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    グループ管理
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">チーム設定とメンバー管理</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('profile.edit') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="hidden sm:inline">戻る</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <button 
                                type="submit"
                                class="inline-flex items-center justify-center shrink-0 rounded-lg border border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/80 dark:hover:bg-gray-800/80 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition px-3 py-2 text-sm font-medium backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden sm:inline">ログアウト</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-12 space-y-6">
                    
                    {{-- ステータスメッセージ --}}
                    @if (session('status'))
                        <div 
                            x-data="{ show: true }" 
                            x-show="show" 
                            x-transition 
                            x-init="setTimeout(() => show = false, 3000)"
                            class="bento-card rounded-xl p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700"
                            role="alert">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-800 dark:text-green-200">成功しました</p>
                                    <p class="text-sm text-green-600 dark:text-green-300">
                                        @if (session('status') === 'group-updated')
                                            グループ情報を更新しました。
                                        @elseif (session('status') === 'member-added')
                                            メンバーを追加しました。
                                        @elseif (session('status') === 'permission-updated')
                                            権限を更新しました。
                                        @elseif (session('status') === 'master-transferred')
                                            グループマスターを譲渡しました。
                                        @elseif (session('status') === 'member-removed')
                                            メンバーを削除しました。
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- グループ基本情報 --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter">
                        <div class="px-6 py-4 border-b border-purple-500/20 dark:border-purple-500/30 bg-gradient-to-r from-purple-500/5 to-pink-50/50 dark:from-purple-500/10 dark:to-pink-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    グループ基本情報
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            @include('profile.group.partials.update-group-information')
                        </div>
                    </div>

                    {{-- メンバー一覧 --}}
                    @if ($group)
                        <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.1s;">
                            <div class="px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-sm font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                        メンバー一覧
                                    </h2>
                                </div>
                            </div>
                            <div class="p-6">
                                @include('profile.group.partials.member-list')
                            </div>
                        </div>
                    @endif

                    {{-- メンバー追加 --}}
                    @if ($group && Auth::user()->canEditGroup())
                        <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.2s;">
                            <div class="px-6 py-4 border-b border-green-500/20 dark:border-green-500/30 bg-gradient-to-r from-green-500/5 to-emerald-50/50 dark:from-green-500/10 dark:to-emerald-900/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-600 to-emerald-600 flex items-center justify-center shadow">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-sm font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                                        メンバー追加
                                    </h2>
                                </div>
                            </div>
                            <div class="p-6">
                                @include('profile.group.partials.add-member')
                            </div>
                        </div>

                        {{-- タスク自動作成の設定（新規追加） --}}
                        <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.3s;">
                            <div class="px-6 py-4 border-b border-indigo-500/20 dark:border-indigo-500/30 bg-gradient-to-r from-indigo-500/5 to-blue-50/50 dark:from-indigo-500/10 dark:to-blue-900/10">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center shadow">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
                                                タスク自動作成の設定
                                            </h2>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                                定期的に作成されるタスクを設定できます
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('batch.scheduled-tasks.index', ['group_id' => Auth::user()->group_id]) }}" 
                                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        管理画面へ
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-app-layout>