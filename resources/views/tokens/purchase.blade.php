<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/purchase.css'])
    @endpush

    <div x-data="{ 
        showSidebar: false, 
        activeTab: 'packages',
        toggleSidebar() {
            this.showSidebar = !this.showSidebar;
        }
    }" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-[100dvh] token-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾（大人向けのみ） --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="token-floating-decoration absolute top-20 left-10 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl"></div>
                <div class="token-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 token-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3">
                        {{-- モバイルメニューボタン --}}
                        <button
                            type="button"
                            data-sidebar-toggle="mobile"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- ヘッダータイトル --}}
                        <div class="flex items-center gap-3">
                            <div class="token-header-icon w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center shadow-lg">
                                @if(!$isChildTheme)
                                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <span class="text-2xl lg:text-3xl">🪙</span>
                                @endif
                            </div>
                            <div>
                                <h1 class="dashboard-header-title text-lg font-bold">
                                    @if(!$isChildTheme)
                                        トークン購入
                                    @else
                                        コインを買う
                                    @endif
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400 hidden sm:block">
                                    @if(!$isChildTheme)
                                        追加トークンの購入
                                    @endif
                                </p>
                            </div>
                            {{-- 子どもの場合：承認が必要な旨の注意アイコン --}}
                            @if (Auth::user()->requiresPurchaseApproval())
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        @if($isChildTheme)
                                            おうちの人の「いいよ」が必要だよ！
                                        @else
                                            親の承認が必要です
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        @if($isChildTheme)
                                            自分でコインを買うことはできません。
                                        @else
                                            トークンを購入する際は、親ユーザーの承認が必要です。購入リクエストを送信すると、親に通知が届きます。
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 右側のボタン --}}
                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ route('tokens.history') }}" 
                           class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="hidden sm:inline">
                                @if(!$isChildTheme)
                                    履歴を見る
                                @else
                                    りれき
                                @endif
                            </span>
                        </a>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-7xl mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    {{-- タブ切り替え（子どもの場合のみ） --}}
                    @if(Auth::user()->isChild())
                        <div class="tab-container mb-6">
                            {{-- タブヘッダー --}}
                            <div class="tab-header">
                                <button 
                                    @click="activeTab = 'packages'"
                                    :class="activeTab === 'packages' ? 'active' : ''"
                                    class="tab-button">
                                    @if($isChildTheme)
                                        <span class="coin-emoji">🪙</span>
                                        <span>買う</span>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                        <span>パッケージ一覧</span>
                                    @endif
                                </button>
                                
                                <button 
                                    @click="activeTab = 'pending'"
                                    :class="activeTab === 'pending' ? 'active' : ''"
                                    class="tab-button">
                                    @if($isChildTheme)
                                        <span class="emoji">⏳</span>
                                        <span>お願い中</span>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>承認待ち</span>
                                    @endif
                                    @if($pendingRequests->isNotEmpty())
                                        <span class="badge-count">{{ $pendingRequests->count() }}</span>
                                    @endif
                                </button>
                            </div>

                            {{-- タブコンテンツ: パッケージ一覧 --}}
                            <div x-show="activeTab === 'packages'" x-transition class="tab-content">
                                @include('tokens.partials.package-list', [
                                    'packages'     => $packages, 
                                    'balance'      => $balance, 
                                    'isChildTheme' => $isChildTheme,
                                    'user'         => Auth::user()
                                ])
                            </div>

                            {{-- タブコンテンツ: 承認待ちリクエスト --}}
                            <div x-show="activeTab === 'pending'" x-transition class="tab-content">
                                @include('tokens.partials.pending-requests', [
                                    'pendingRequests' => $pendingRequests, 
                                    'isChildTheme' => $isChildTheme
                                ])
                            </div>
                        </div>
                    @else
                        {{-- 親の場合：パッケージ一覧のみ表示 --}}
                        @include('tokens.partials.package-list', [
                            'packages' => $packages, 
                            'balance' => $balance, 
                            'isChildTheme' => $isChildTheme,
                            'user' => Auth::user()
                        ])
                    @endif
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/tokens/purchase.js'])
    @endpush
</x-app-layout>