<header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm {{ $isChildTheme ? 'child-theme' : '' }}"
        x-data="{ searchFocused: false, notificationCount: {{ $notificationCount ?? 0 }} }"
        @search-focused.window="searchFocused = $event.detail.focused">
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
                    @if (!$isChildTheme)
                        <h1 class="dashboard-header-title text-lg font-bold">
                            タスクリスト
                        </h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">タスクの登録と管理</p>
                    @else
                        <h1 class="dashboard-header-title text-lg font-bold">
                            ToDo
                        </h1>
                    @endif
                </div>
            </div>

            {{-- モバイル用タイトル（lg未満で表示） --}}
            <div class="flex lg:hidden items-center"
                x-show="!searchFocused"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">
                <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-2">
                    @if (!$isChildTheme)
                        <h1 class="dashboard-header-title text-base font-bold">
                            タスクリスト
                        </h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">タスクの登録と管理</p>
                    @else
                        <h1 class="dashboard-header-title text-base font-bold">
                            ToDo
                        </h1>
                    @endif
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
                    class="dashboard-btn-group inline-flex items-center justify-center shrink-0 rounded-full text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition h-10 w-10 sm:h-10 sm:w-auto sm:rounded-xl sm:px-4 sm:py-2.5 lg:px-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-4 sm:w-4 sm:mr-2" viewBox="0 0 20 20" fill="currentColor">
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

    {{-- モバイルフィルタ --}}
    <div class="lg:hidden px-4 pb-3 pt-2 border-t border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
        <x-task-filter />
    </div>

    {{-- タブナビゲーション --}}
    <div class="flex gap-2 border-t border-gray-200 dark:border-gray-700 px-4 lg:px-6 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
        <button 
            @click="activeTab = 'todo'"
            :class="activeTab === 'todo' ? 'border-[#59B9C6] text-[#59B9C6] dashboard-tab active' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 dashboard-tab'"
            class="px-4 py-3 border-b-2 font-semibold text-sm transition">
            @if (!$isChildTheme)
                未完了
            @else
                YET
            @endif
        </button>
        <button 
            @click="activeTab = 'completed'"
            :class="activeTab === 'completed' ? 'border-[#59B9C6] text-[#59B9C6] dashboard-tab active' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 dashboard-tab'"
            class="px-4 py-3 border-b-2 font-semibold text-sm transition">
            @if (!$isChildTheme)
                完了済
            @else
                DONE
            @endif
        </button>
    </div>
</header>