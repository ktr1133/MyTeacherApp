
<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/notifications.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/notifications/notification-search.js'])
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
                            data-sidebar-toggle="mobile"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- ヘッダーアイコンとタイトル --}}
                        <div class="flex items-center gap-3">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="dashboard-header-title text-lg font-bold">
                                    検索結果
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $operator === 'and' ? 'AND検索' : 'OR検索' }}: {{ implode(' ', $searchTerms) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- 一覧に戻るボタン --}}
                    <a href="{{ route('notification.index') }}" class="dashboard-btn-primary inline-flex items-center justify-center shrink-0 rounded-xl px-4 py-2.5 text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-semibold whitespace-nowrap">一覧に戻る</span>
                    </a>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-5xl mx-auto px-4 lg:px-6 py-6 lg:py-8">
                    {{-- 検索結果カード --}}
                    <div class="bento-card rounded-2xl shadow-xl p-6 lg:p-8">
                        @if($notifications->isEmpty())
                            {{-- 検索結果がない場合 --}}
                            <div class="text-center py-20">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full mb-6">
                                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">検索結果が見つかりませんでした</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">別のキーワードで検索してみてください</p>
                            </div>
                        @else
                            {{-- 検索結果件数表示 --}}
                            <div class="mb-6 flex items-center gap-3">
                                <div class="px-4 py-2 rounded-full bg-[#59B9C6] text-white text-sm font-bold shadow-lg">
                                    {{ $notifications->total() }} 件の結果
                                </div>
                            </div>

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
                                                    @if(!$isDeleted)
                                                        <span class="flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                            </svg>
                                                            管理者: {{ $template->sender->username ?? '不明' }}
                                                        </span>
                                                    @endif
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <x-user-local-time :datetime="$userNotification->created_at" format="Y/m/d H:i" />
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- バッジ --}}
                                            <div class="flex items-center gap-2">
                                                @if($template && !$isDeleted)
                                                    {{-- 優先度バッジ --}}
                                                    @if($template->priority === 'important')
                                                        <span class="notification-priority-badge notification-priority-important">重要</span>
                                                    @elseif($template->priority === 'normal')
                                                        <span class="notification-priority-badge notification-priority-normal">通常</span>
                                                    @else
                                                        <span class="notification-priority-badge notification-priority-info">情報</span>
                                                    @endif

                                                    {{-- 公式バッジ --}}
                                                    @if($template->source === 'admin')
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400">公式</span>
                                                    @endif
                                                @endif
                                            </div>

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
                                {{ $notifications->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>