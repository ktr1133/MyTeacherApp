<header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm {{ $isChildTheme ? 'child-theme' : '' }}"
        data-notification-count="{{ $notificationCount ?? 0 }}">
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

            {{-- デスクトップ用タイトル（検索フォーカス時に非表示） --}}
            <div class="hidden lg:flex items-center gap-3" data-header-desktop-title>
                <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    @if (!$isChildTheme)
                        <h1 class="dashboard-header-title text-lg font-bold">
                            {{-- 1024～1133pxでは「ToDo」、1134px以上で「タスクリスト」 --}}
                            <span class="hidden min-[1134px]:inline">タスクリスト</span>
                            <span class="inline min-[1134px]:hidden">ToDo</span>
                        </h1>
                        {{-- 1024～1133pxでは副題非表示 --}}
                        <p class="text-xs text-gray-600 dark:text-gray-400 hidden min-[1134px]:block">タスクの登録と管理</p>
                    @else
                        <h1 class="dashboard-header-title text-lg font-bold">
                            ToDo
                        </h1>
                    @endif
                </div>
            </div>

            {{-- モバイル用タイトル（lg未満で表示） --}}
            <div class="flex lg:hidden items-center" data-header-mobile-title>
                <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-2">
                    @if (!$isChildTheme)
                        {{-- 幅342px以下で「ToDo」に切り替え (xs = 343px) --}}
                        <h1 class="dashboard-header-title text-base font-bold">
                            <span class="hidden xs:inline">タスクリスト</span>
                            <span class="inline xs:hidden">ToDo</span>
                        </h1>
                        {{-- 幅372px以下で副題を非表示 (xxs = 373px) --}}
                        <p class="text-xs text-gray-600 dark:text-gray-400 hidden xxs:block">タスクの登録と管理</p>
                    @else
                        <h1 class="dashboard-header-title text-base font-bold">
                            ToDo
                        </h1>
                    @endif
                </div>
            </div>
            
            {{-- 検索ボタン（lg以上で表示） --}}
            <button 
                type="button"
                data-open-search-modal
                class="hidden lg:flex items-center gap-2 xl-:p-2.5 xl-:rounded-xl min-[1134px]:px-4 min-[1134px]:py-2 min-[1134px]:rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                aria-label="検索">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                {{-- 1024～1133pxではテキスト非表示 --}}
                <span class="text-sm text-gray-500 dark:text-gray-400 hidden min-[1134px]:inline">タスクを検索...</span>
            </button>
        </div>

        {{-- ボタン群 --}}
        <div class="flex items-center gap-2">

            {{-- タスク登録ボタン --}}
            <button 
                id="open-task-modal-btn"
                class="dashboard-btn-primary inline-flex items-center justify-center rounded-xl text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition px-3 py-2.5 sm:px-4 lg:px-5 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <span class="hidden sm:inline-block text-sm font-semibold whitespace-nowrap">
                    @if (!$isChildTheme)
                        タスク登録
                    @else
                        つくる
                    @endif
                </span>
            </button>

            {{-- グループタスク登録ボタン --}}
            @if(Auth::user()->canEditGroup())
                <button 
                    id="open-group-task-modal-btn"
                    class="dashboard-btn-group inline-flex items-center justify-center rounded-xl text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition px-3 py-2.5 sm:px-4 lg:px-5 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                    <span class="hidden sm:inline-block text-sm font-semibold whitespace-nowrap">
                        @if (!$isChildTheme)
                            グループタスク
                        @else
                            クエスト
                        @endif
                    </span>
                </button>
            @endif

            {{-- グループタスク管理ボタン（lg以上で表示） --}}
            @if(Auth::user()->canEditGroup())
                <a href="{{ route('group-tasks.index') }}"
                   class="hidden lg:inline-flex items-center justify-center p-2.5 rounded-xl bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 hover:from-purple-200 hover:to-indigo-200 dark:hover:from-purple-800/40 dark:hover:to-indigo-800/40 border-2 border-purple-300 dark:border-purple-600 hover:border-purple-400 dark:hover:border-purple-500 transition-all duration-300 hover:shadow-lg hover:shadow-purple-200 dark:hover:shadow-purple-900/30 group"
                   aria-label="{{ !$isChildTheme ? 'グループタスク管理' : 'クエストかんり' }}">
                    <svg class="w-6 h-6 text-purple-700 dark:text-purple-400 group-hover:text-purple-800 dark:group-hover:text-purple-300 group-hover:scale-110 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
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
                <span class="notification-badge absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1.5 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold rounded-full shadow-lg ring-2 ring-white dark:ring-gray-900"
                    style="display: {{ ($notificationCount ?? 0) > 0 ? '' : 'none' }}">
                    {{ ($notificationCount ?? 0) > 99 ? '99+' : ($notificationCount ?? 0) }}
                </span>
            </a>
        </div>

        {{-- ユーザードロップダウン（検索フォーカス時に非表示） --}}
        <div class="hidden sm:flex sm:items-center sm:ms-6" data-header-user-dropdown>
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

    {{-- モバイルフィルタ（blur効果を削除） --}}
    <div class="lg:hidden px-4 pb-3 pt-2 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
        <x-task-filter />
    </div>
</header>

{{-- 検索モーダル --}}
<x-search-modal :isChildTheme="$isChildTheme" />

@vite(['resources/js/dashboard/header.js'])