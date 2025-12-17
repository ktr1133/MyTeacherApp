<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/notifications.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/notifications/notifications.js'])
    @endpush

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">
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
                            data-sidebar-toggle="mobile"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- ヘッダーアイコンとタイトル --}}
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl hidden sm:flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                @if (!$isChildTheme)
                                    <h1 class="dashboard-header-title text-lg font-bold truncate">
                                        通知詳細
                                    </h1>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">お知らせの内容</p>
                                @else
                                    <h1 class="dashboard-header-title text-lg font-bold truncate">
                                        くわしいおしらせ
                                    </h1>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 一覧に戻るボタン --}}
                    <a href="{{ route('notifications.index') }}" 
                       class="inline-flex items-center px-2 sm:px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">
                            @if (!$isChildTheme)
                                一覧に戻る
                            @else
                                もどる
                            @endif
                        </span>
                    </a>

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
                <div class="max-w-4xl mx-auto px-4 lg:px-6 py-6 lg:py-8">
                    @if($isDeleted)
                        {{-- 削除された通知 --}}
                        <div class="bento-card rounded-2xl shadow-xl p-8 lg:p-12 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">通知が削除されました</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-8">この通知は管理者によって削除されました。</p>
                            <a href="{{ route('notifications.index') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                </svg>
                                一覧に戻る
                            </a>
                        </div>
                    @else
                        {{-- 通知詳細カード --}}
                        <div class="notification-detail-card bento-card rounded-2xl shadow-xl overflow-hidden">
                            {{-- ヘッダー部分 --}}
                            <div class="notification-detail-header p-6 lg:p-8">
                                <div class="sm:flex sm:items-start sm:gap-4 mb-6">
                                    {{-- アイコン --}}
                                    <div class="hidden sm:block flex-shrink-0">
                                        <div class="w-14 h-14 bg-white/20 dark:bg-black/20 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                                            @if($template->isAdminNotification())
                                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M11 15h2V9h3l-4-5-4 5h3v6m-7 7h16v-2H4v2z"/>
                                                </svg>
                                            @else
                                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- タイトルと優先度 --}}
                                    <div class="flex-1">
                                        <div class="flex items-start gap-3 mb-3">
                                            <h2 class="text-2xl lg:text-3xl font-bold text-white leading-tight flex-1">
                                                {{ $template->title }}
                                            </h2>
                                            @if($template->priority === 'important')
                                                <span class="notification-detail-priority notification-detail-priority-important">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    重要
                                                </span>
                                            @elseif($template->priority === 'normal')
                                                <span class="notification-detail-priority notification-detail-priority-normal">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                    </svg>
                                                    通常
                                                </span>
                                            @else
                                                <span class="notification-detail-priority notification-detail-priority-info">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                    </svg>
                                                    情報
                                                </span>
                                            @endif
                                        </div>

                                        {{-- メタ情報 --}}
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/90">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                                管理者: {{ $template->sender->username }}
                                            </span>
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                </svg>
                                                <x-user-local-time :datetime="$notification->created_at" format="Y年m月d日 H:i" />
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 本文部分 --}}
                            <div class="notification-detail-body p-6 lg:p-8">
                                <div class="prose prose-lg dark:prose-invert max-w-none">
                                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $template->message }}</p>
                                </div>

                                {{-- 編集履歴 --}}
                                @if($template->updated_by)
                                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                            </svg>
                                            最終更新: {{ $template->updatedBy->username }} (<x-user-local-time :datetime="$template->updated_at" format="Y/m/d H:i" />)
                                        </p>
                                    </div>
                                @endif

                                {{-- アクションボタン --}}
                                @if($template->action_url || $template->official_page_url || $template->type === 'parent_link_request')
                                    <div class="mt-8 flex flex-wrap gap-3">
                                        {{-- 親子紐付けリクエスト: 承認・拒否ボタン --}}
                                        @if($template->type === 'parent_link_request')
                                            <form method="POST" action="{{ route('notification.approve-parent-link', $notification->id) }}" class="flex-1 min-w-[200px]">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
                                                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    @if (!$isChildTheme)
                                                        承認する
                                                    @else
                                                        しょうにんする
                                                    @endif
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="{{ route('notification.reject-parent-link', $notification->id) }}" 
                                                  class="flex-1 min-w-[200px]"
                                                  onsubmit="return confirm('@if (!$isChildTheme)本当に拒否しますか？\n\nCOPPA法により、13歳未満の方は保護者の管理が必要です。拒否するとアカウントが削除されます。@elseほんとうにきょひしますか？\n\nきょひするとアカウントがさくじょされます。@endif');">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
                                                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    @if (!$isChildTheme)
                                                        拒否する
                                                    @else
                                                        きょひする
                                                    @endif
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($template->action_url)
                                            <a href="{{ $template->action_url }}" 
                                               target="_blank"
                                               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition transform hover:scale-105">
                                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                                {{ $template->action_text ?? '詳細を見る' }}
                                            </a>
                                        @endif

                                        @if($template->official_page_url)
                                            <a href="{{ $template->official_page_url }}" 
                                               target="_blank"
                                               class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm">
                                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                公式発表
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-app-layout>