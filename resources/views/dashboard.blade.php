<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/avatar/avatar.css'])
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
        {{-- スクリプト読み込み --}}
        @vite(['resources/js/dashboard/dashboard.js'])
        @if(Auth::user()->canEditGroup())
            @vite(['resources/js/dashboard/group-task.js'])
        @endif
        
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

    <div x-data="{ 
        showTaskModal: false, 
        showDecompositionModal: false, 
        showRefineModal: false,
        isProposing: false,
        taskTitle: '',
        taskSpan: 'mid', 
        refinementPoints: '',
        decompositionProposal: null,
        showSidebar: false,
        activeTab: 'todo',
        
        startDecomposition: function(isRefinement = false) {
            if (typeof window.decomposeTask === 'function') {
                window.decomposeTask.call(this, isRefinement);
            }
        },
        confirmProposal: function() {
            if (typeof window.acceptProposal === 'function') {
                window.acceptProposal.call(this);
            }
        }
    }" class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm"
                    x-data="{ searchFocused: false, notificationCount: {{ $notificationCount ?? 0 }} }"
                    @search-focused.window="searchFocused = $event.detail.focused">
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

                        {{-- ヘッダーアイコンとタイトル（検索フォーカス時に非表示） --}}
                        <div class="hidden lg:flex items-center gap-3"
                            x-show="!searchFocused"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-x-4"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-x-0"
                            x-transition:leave-end="opacity-0 -translate-x-4">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="dashboard-header-title text-lg font-bold">
                                    タスクリスト
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">タスクの登録と管理</p>
                            </div>
                        </div>
                        
                        {{-- 検索欄（lg以上で表示、フォーカス時に幅拡大） --}}
                        <div class="hidden lg:flex flex-1 min-w-0"
                            :class="searchFocused ? '' : 'max-w-md'">
                            <x-task-filter />
                        </div>
                    </div>

                    {{-- ボタン群（検索フォーカス時に非表示） --}}
                    <div class="flex items-center gap-2"
                        x-show="!searchFocused"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-x-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-x-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-x-4">

                        {{-- タスク登録ボタン --}}
                        <button 
                            id="open-task-modal-btn"
                            class="dashboard-btn-primary inline-flex items-center justify-center shrink-0 rounded-full text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition h-10 w-10 sm:h-10 sm:w-auto sm:rounded-xl sm:px-4 sm:py-2.5 lg:px-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-4 sm:w-4 sm:mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="hidden sm:inline-block text-sm font-semibold whitespace-nowrap">タスク登録</span>
                        </button>

                        {{-- グループタスク登録ボタン --}}
                        @if(Auth::user()->canEditGroup())
                            <button 
                                id="open-group-task-modal-btn"
                                class="dashboard-btn-group inline-flex items-center justify-center shrink-0 rounded-full text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition h-10 w-10 sm:h-10 sm:w-auto sm:rounded-xl sm:px-4 sm:py-2.5 lg:px-5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-4 sm:w-4 sm:mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                                <span class="hidden sm:inline-block text-sm font-semibold whitespace-nowrap">グループタスク</span>
                            </button>
                        @endif

                        {{-- お知らせ通知ボタン --}}
                        <a href="{{ route('notification.index') }}"
                        class="notification-btn relative p-2.5 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-gray-800 dark:to-gray-700 hover:from-amber-100 hover:to-orange-100 dark:hover:from-gray-700 dark:hover:to-gray-600 border border-amber-200 dark:border-gray-600 hover:border-amber-300 dark:hover:border-gray-500 transition-all duration-300 hover:shadow-lg group"
                        aria-label="お知らせ通知">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            
                            {{-- 未読バッジ（0件なら非表示） --}}
                            <span x-show="notificationCount > 0"
                                x-transition
                                class="notification-badge absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1.5 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold rounded-full shadow-lg ring-2 ring-white dark:ring-gray-900"
                                x-text="notificationCount > 99 ? '99+' : notificationCount">
                            </span>
                        </a>
                    </div>

                    {{-- ユーザードロップダウン（検索フォーカス時に非表示） --}}
                    <div class="hidden sm:flex sm:items-center sm:ms-6"
                        x-show="!searchFocused"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-4">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-gray-500 dark:text-gray-400 bg-white/50 dark:bg-gray-800/50 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/80 dark:hover:bg-gray-800/80 focus:outline-none transition backdrop-blur-sm">
                                    <div>{{ Auth::user()->username }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('アカウント') }}
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('ログアウト') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>

                {{-- モバイルフィルタ（変更なし） --}}
                <div class="lg:hidden px-4 pb-3 pt-2 border-t border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
                    <x-task-filter />
                </div>

                {{-- タブナビゲーション（変更なし） --}}
                <div class="flex gap-2 border-t border-gray-200 dark:border-gray-700 px-4 lg:px-6 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
                    <button 
                        @click="activeTab = 'todo'"
                        :class="activeTab === 'todo' ? 'border-[#59B9C6] text-[#59B9C6] dashboard-tab active' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 dashboard-tab'"
                        class="px-4 py-3 border-b-2 font-semibold text-sm transition">
                        未完了
                    </button>
                    <button 
                        @click="activeTab = 'completed'"
                        :class="activeTab === 'completed' ? 'border-[#59B9C6] text-[#59B9C6] dashboard-tab active' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 dashboard-tab'"
                        class="px-4 py-3 border-b-2 font-semibold text-sm transition">
                        完了済
                    </button>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    @php
                        $todoTasks = $tasks->where('is_completed', false);
                        $completedTasks = $tasks->where('is_completed', true);

                        $bucketizeTasks = function($taskCollection) use ($tags) {
                            $bucketMap = [];
                            foreach ($taskCollection as $t) {
                                if ($t->tags && $t->tags->count() > 0) {
                                    foreach ($t->tags as $tg) {
                                        $bid = $tg->id;
                                        if (!isset($bucketMap[$bid])) {
                                            $bucketMap[$bid] = [
                                                'id' => $tg->id,
                                                'name' => $tg->name,
                                                'tasks' => collect(),
                                            ];
                                        }
                                        $bucketMap[$bid]['tasks']->push($t);
                                    }
                                } else {
                                    if (!isset($bucketMap[0])) {
                                        $bucketMap[0] = [
                                            'id' => 0,
                                            'name' => '未分類',
                                            'tasks' => collect(),
                                        ];
                                    }
                                    $bucketMap[0]['tasks']->push($t);
                                }
                            }
                            return collect($bucketMap)->sortByDesc(fn($b) => $b['tasks']->count())->values();
                        };

                        $todoBuckets = $bucketizeTasks($todoTasks);
                        $completedBuckets = $bucketizeTasks($completedTasks);
                    @endphp

                    {{-- 未完了タブ --}}
                    <div x-show="activeTab === 'todo'" x-transition class="task-card-enter">
                        @if($todoBuckets->isEmpty())
                            <div class="empty-state text-center py-16 bento-card rounded-2xl shadow-lg">
                                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">未完了のタスクがありません</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">新しいタスクを登録してみましょう</p>
                            </div>
                        @else
                            @include('dashboard.partials.task-bento-layout', ['buckets' => $todoBuckets, 'tags' => $tags, 'prefix' => 'todo'])
                        @endif
                    </div>

                    {{-- 完了済タブ --}}
                    <div x-show="activeTab === 'completed'" x-transition class="task-card-enter">
                        @if($completedBuckets->isEmpty())
                            <div class="empty-state text-center py-16 bento-card rounded-2xl shadow-lg">
                                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">完了済のタスクがありません</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">タスクを完了するとここに表示されます</p>
                            </div>
                        @else
                            @include('dashboard.partials.task-bento-layout', ['buckets' => $completedBuckets, 'tags' => $tags, 'prefix' => 'completed'])
                        @endif
                    </div>
                </div>
            </main>
        </div>

        @include('dashboard.modal-dashboard-task')
        @if(Auth::user()->canEditGroup())
            @include('dashboard.modal-group-task')
        @endif
    </div>
</x-app-layout>