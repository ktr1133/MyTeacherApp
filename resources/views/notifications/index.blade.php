<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/notifications.css'])
    @endpush

    @php
        $avatarEvent = session('avatar_event');
    @endphp

    @push('scripts')
        @vite(['resources/js/notifications/notifications.js'])
    @endpush

    <div x-data="{ showSidebar: false }" class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">
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

                        {{-- ヘッダーアイコンとタイトル --}}
                        <div class="flex items-center gap-3">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="dashboard-header-title text-lg font-bold">
                                    お知らせ
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">通知の確認と管理</p>
                            </div>
                        </div>
                    </div>

                    {{-- すべて既読ボタン --}}
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notification.read-all') }}">
                            @csrf
                            <button type="submit" class="dashboard-btn-primary inline-flex items-center justify-center shrink-0 rounded-xl px-4 py-2.5 text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm font-semibold whitespace-nowrap">すべて既読</span>
                            </button>
                        </form>
                    @endif

                    {{-- ユーザードロップダウン --}}
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
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
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('ログアウト') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-5xl mx-auto px-4 lg:px-6 py-6 lg:py-8">
                    {{-- 成功メッセージ --}}
                    @if(session('success'))
                        <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-5 py-4 rounded-xl relative notification-alert shadow-lg backdrop-blur-sm" role="alert">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- 通知一覧カード --}}
                    <div class="bento-card rounded-2xl shadow-xl p-6 lg:p-8">
                        @if($notifications->isEmpty())
                            {{-- 通知がない場合 --}}
                            <div class="text-center py-20">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full mb-6">
                                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                </div>
                                <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">通知はありません</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">新しい通知があるとここに表示されます</p>
                            </div>
                        @else
                            {{-- 未読件数表示 --}}
                            @if($unreadCount > 0)
                                <div class="mb-8 flex items-center gap-3">
                                    <div class="notification-unread-badge px-4 py-2 rounded-full text-white text-sm font-bold shadow-lg">
                                        <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                        </svg>
                                        未読 {{ $unreadCount }} 件
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-3">
                                @foreach($notifications as $userNotification)
                                    @php
                                        $template = $userNotification->template;
                                        $isRead = $userNotification->is_read;
                                        $isDeleted = $template === null || $template->trashed();
                                    @endphp

                                    <a href="{{ route('notification.show', $userNotification->id) }}" 
                                       class="block notification-list-card rounded-xl p-5 transition-all duration-300 
                                       {{ $isRead ? 'bg-white dark:bg-gray-800/50' : 'notification-list-card-unread' }} 
                                       border border-gray-200 dark:border-gray-700 hover:shadow-lg group">
                                        
                                        <div class="flex items-center gap-4">
                                            {{-- 未読インジケーター --}}
                                            <div class="flex-shrink-0">
                                                @if(!$isRead)
                                                    <div class="notification-unread-dot w-2.5 h-2.5 bg-[#59B9C6] rounded-full"></div>
                                                @else
                                                    <div class="w-2.5 h-2.5"></div>
                                                @endif
                                            </div>

                                            {{-- タイトル --}}
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1 group-hover:text-[#59B9C6] dark:group-hover:text-[#7DD3DB] transition truncate">
                                                    @if($isDeleted)
                                                        <span class="text-gray-400 dark:text-gray-500">[削除された通知]</span>
                                                    @else
                                                        {{ $template->title }}
                                                    @endif
                                                </h3>
                                                <div class="flex items-center gap-3 text-xs text-gray-600 dark:text-gray-400">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                        </svg>
                                                        管理者: {{ $template->sender->username ?? '不明' }}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                        </svg>
                                                        {{ $userNotification->created_at->format('Y/m/d H:i') }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- 優先度バッジ --}}
                                            @if($template && !$isDeleted)
                                                <div class="flex-shrink-0">
                                                    @if($template->priority === 'important')
                                                        <span class="notification-priority-badge notification-priority-important">
                                                            重要
                                                        </span>
                                                    @elseif($template->priority === 'normal')
                                                        <span class="notification-priority-badge notification-priority-normal">
                                                            通常
                                                        </span>
                                                    @else
                                                        <span class="notification-priority-badge notification-priority-info">
                                                            情報
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- 矢印アイコン --}}
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-gray-400 group-hover:text-[#59B9C6] group-hover:translate-x-1 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                            {{-- ページネーション --}}
                            <div class="mt-8">
                                {{ $notifications->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>