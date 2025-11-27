<x-app-layout>
    @push('styles')
        @vite(['resources/css/admin/common.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl admin-floating-decoration"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-[#59B9C6]/10 rounded-full blur-3xl admin-floating-decoration" style="animation-delay: -10s;"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-pink-500/5 rounded-full blur-3xl admin-floating-decoration" style="animation-delay: -15s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
            
            {{-- ヘッダー --}}
            <header class="admin-header shrink-0 shadow-sm">
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
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    トークン統計
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">システム全体のトークン利用状況</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline-block px-3 py-1.5 bg-gradient-to-r from-purple-500 to-pink-600 text-white text-xs font-bold rounded-full">
                            管理者
                        </span>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツエリア --}}
            <main class="flex-1 overflow-auto px-4 lg:px-6 py-4 lg:py-6">

                {{-- 統計概要カード --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    {{-- 総ユーザー数 --}}
                    <div class="admin-card stat-card admin-fade-in group hover:shadow-xl transition-all duration-300">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">総ユーザー数</p>
                                    <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div 
                                    class="stat-value-container flex items-baseline gap-2 cursor-help"
                                    data-full-value="{{ number_format($stats['total_users']) }} 人"
                                >
                                    <span 
                                        class="stat-value font-bold text-gray-900 dark:text-white transition-all leading-none"
                                        :class="getStatFontSize({{ $stats['total_users'] }})"
                                        x-text="formatStatValue({{ $stats['total_users'] }})"
                                    ></span>
                                    <span 
                                        class="text-gray-500 dark:text-gray-400 flex-shrink-0 font-medium"
                                        :class="getUnitFontSize({{ $stats['total_users'] }})"
                                    >人</span>
                                </div>
                            </div>
                            <div class="h-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full mt-3"></div>
                        </div>
                    </div>

                    {{-- 総トークン残高 --}}
                    <div class="admin-card stat-card admin-fade-in group hover:shadow-xl transition-all duration-300" style="animation-delay: 0.1s;">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">総残高</p>
                                    <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-gradient-to-br from-amber-500 to-yellow-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div 
                                    class="stat-value-container flex items-baseline gap-2 cursor-help"
                                    data-full-value="{{ number_format($stats['total_balance']) }} トークン"
                                >
                                    <span 
                                        class="stat-value font-bold text-gray-900 dark:text-white transition-all leading-none"
                                        :class="getStatFontSize({{ $stats['total_balance'] }})"
                                        x-text="formatStatValue({{ $stats['total_balance'] }})"
                                    ></span>
                                    <span 
                                        class="text-gray-500 dark:text-gray-400 flex-shrink-0 font-medium whitespace-nowrap"
                                        :class="getUnitFontSize({{ $stats['total_balance'] }})"
                                    >トークン</span>
                                </div>
                            </div>
                            <div class="h-1 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full mt-3"></div>
                        </div>
                    </div>

                    {{-- 今月の消費量 --}}
                    <div class="admin-card stat-card admin-fade-in group hover:shadow-xl transition-all duration-300" style="animation-delay: 0.2s;">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">今月の消費</p>
                                    <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                    </div>
                                </div>
                                <div 
                                    class="stat-value-container flex items-baseline gap-2 cursor-help"
                                    data-full-value="{{ number_format($stats['monthly_consumed']) }} トークン"
                                >
                                    <span 
                                        class="stat-value font-bold text-gray-900 dark:text-white transition-all leading-none"
                                        :class="getStatFontSize({{ $stats['monthly_consumed'] }})"
                                        x-text="formatStatValue({{ $stats['monthly_consumed'] }})"
                                    ></span>
                                    <span 
                                        class="text-gray-500 dark:text-gray-400 flex-shrink-0 font-medium whitespace-nowrap"
                                        :class="getUnitFontSize({{ $stats['monthly_consumed'] }})"
                                    >トークン</span>
                                </div>
                            </div>
                            <div class="h-1 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full mt-3"></div>
                        </div>
                    </div>

                    {{-- 累計消費量 --}}
                    <div class="admin-card stat-card admin-fade-in group hover:shadow-xl transition-all duration-300" style="animation-delay: 0.3s;">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">累計消費</p>
                                    <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div 
                                    class="stat-value-container flex items-baseline gap-2 cursor-help"
                                    data-full-value="{{ number_format($stats['total_consumed']) }} トークン"
                                >
                                    <span 
                                        class="stat-value font-bold text-gray-900 dark:text-white transition-all leading-none"
                                        :class="getStatFontSize({{ $stats['total_consumed'] }})"
                                        x-text="formatStatValue({{ $stats['total_consumed'] }})"
                                    ></span>
                                    <span 
                                        class="text-gray-500 dark:text-gray-400 flex-shrink-0 font-medium whitespace-nowrap"
                                        :class="getUnitFontSize({{ $stats['total_consumed'] }})"
                                    >トークン</span>
                                </div>
                            </div>
                            <div class="h-1 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full mt-3"></div>
                        </div>
                    </div>
                </div>

                {{-- 売上統計 --}}
                <div class="admin-card admin-fade-in mb-6" style="animation-delay: 0.4s;">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">売上統計</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">トークン課金の売上情報</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-teal-200 dark:border-teal-700/30">
                                <div class="flex items-center gap-3 mb-3">
                                    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-teal-700 dark:text-teal-300">総売上</span>
                                </div>
                                <div 
                                    class="stat-value-container cursor-help"
                                    data-full-value="¥{{ number_format($stats['total_revenue']) }}"
                                >
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl">¥</span>
                                        <span 
                                            class="stat-value font-bold text-teal-900 dark:text-teal-100 transition-all leading-none"
                                            :class="getStatFontSize({{ $stats['total_revenue'] }})"
                                            x-text="formatStatValue({{ $stats['total_revenue'] }})"
                                        ></span>
                                    </div>
                                </div>
                                <p class="text-xs text-teal-600 dark:text-teal-400 mt-2">累計売上金額</p>
                            </div>

                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700/30">
                                <div class="flex items-center gap-3 mb-3">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">平均単価</span>
                                </div>
                                @php
                                    $avgRevenue = $stats['total_users'] > 0 ? round($stats['total_revenue'] / $stats['total_users']) : 0;
                                @endphp
                                <div 
                                    class="stat-value-container cursor-help"
                                    data-full-value="¥{{ number_format($avgRevenue) }}"
                                >
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl">¥</span>
                                        <span 
                                            class="stat-value font-bold text-purple-900 dark:text-purple-100 transition-all leading-none"
                                            :class="getStatFontSize({{ $avgRevenue }})"
                                            x-text="formatStatValue({{ $avgRevenue }})"
                                        ></span>
                                    </div>
                                </div>
                                <p class="text-xs text-purple-600 dark:text-purple-400 mt-2">ユーザーあたりの平均売上</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 詳細情報へのリンク --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('admin.token-users') }}" class="admin-card admin-fade-in group hover:shadow-xl transition-all duration-300" style="animation-delay: 0.5s;">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition">ユーザー別トークン</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">各ユーザーの詳細を確認</p>
                                    </div>
                                </div>
                                <svg class="w-6 h-6 text-gray-400 group-hover:text-orange-600 group-hover:translate-x-2 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.payment-history') }}" class="admin-card admin-fade-in group hover:shadow-xl transition-all duration-300" style="animation-delay: 0.6s;">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-400 transition">課金履歴</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">決済履歴を確認</p>
                                    </div>
                                </div>
                                <svg class="w-6 h-6 text-gray-400 group-hover:text-pink-600 group-hover:translate-x-2 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>