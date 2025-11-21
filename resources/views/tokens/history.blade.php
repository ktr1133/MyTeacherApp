<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/history.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/tokens/history.js'])
    @endpush

    <div x-data="tokenHistory()"
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-[100dvh] {{ $isChildTheme ? 'token-gradient-bg-child' : 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800' }} relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/5 rounded-full blur-3xl"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 {{ $isChildTheme ? 'bg-amber-50/80 dark:bg-gray-900/80' : 'bg-white/80 dark:bg-gray-900/80' }} backdrop-blur-sm shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            @click="toggleSidebar()"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="{{ $isChildTheme ? 'dashboard-header-icon' : '' }} w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center shadow-lg {{ $isChildTheme ? '' : 'bg-gradient-to-br from-blue-500 to-purple-600' }}">
                                @if($isChildTheme)
                                    <span class="text-2xl lg:text-3xl">🪙</span>
                                @else
                                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h1 class="{{ $isChildTheme ? 'dashboard-header-title' : 'bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent' }} text-lg lg:text-xl font-bold">
                                    @if($isChildTheme)
                                        コイン履歴
                                    @else
                                        トークン履歴
                                    @endif
                                </h1>
                                @if(!$isChildTheme)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 hidden sm:block">購入・使用履歴の確認</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('tokens.purchase') }}" 
                           class="{{ $isChildTheme ? 'dashboard-btn-primary' : 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700' }} inline-flex items-center justify-center gap-2 text-white font-semibold rounded-xl px-4 py-2.5 lg:px-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                            @if($isChildTheme)
                                <span class="text-lg">🪙</span>
                                <span class="hidden sm:inline text-sm">コインを買う</span>
                                <span class="sm:hidden text-sm">買う</span>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="hidden sm:inline text-sm">トークン購入</span>
                                <span class="sm:hidden text-sm">購入</span>
                            @endif
                        </a>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 px-4 lg:px-6 py-6">
                <div class="max-w-7xl mx-auto space-y-6">
                    {{-- 統計カード --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 lg:gap-4">
                        {{-- 現在の残高 --}}
                        <div class="token-stat-card {{ $isChildTheme ? 'bento-card token-stat-card-balance' : 'token-stat-card-adult' }}">
                            <div class="token-stat-header">
                                <div class="token-stat-icon token-stat-icon-balance">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h3 class="token-stat-title">
                                    @if($isChildTheme)
                                        今のコイン
                                    @else
                                        現在の残高
                                    @endif
                                </h3>
                            </div>
                            <div class="token-stat-value-container">
                                <p class="token-stat-value token-amount" data-original-amount="{{ $balance->balance }}">
                                    {{ number_format($balance->balance) }}
                                </p>
                                <p class="token-stat-unit">
                                    @if($isChildTheme)
                                        コイン
                                    @else
                                        トークン
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- 今月の購入金額 --}}
                        <div class="token-stat-card {{ $isChildTheme ? 'bento-card token-stat-card-purchase' : 'token-stat-card-adult' }}">
                            <div class="token-stat-header">
                                <div class="token-stat-icon token-stat-icon-purchase">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="token-stat-title">
                                    @if($isChildTheme)
                                        今月のお金
                                    @else
                                        今月の購入額
                                    @endif
                                </h3>
                            </div>
                            <div class="token-stat-value-container">
                                <p class="token-stat-value token-amount" data-original-amount="{{ $monthlyPurchaseAmount }}" data-prefix="¥">
                                    {{ number_format($monthlyPurchaseAmount) }}
                                </p>
                                <p class="token-stat-unit">
                                    {{ number_format($monthlyPurchaseTokens) }} トークン
                                </p>
                            </div>
                        </div>

                        {{-- 今月の使用量 --}}
                        <div class="token-stat-card {{ $isChildTheme ? 'bento-card token-stat-card-usage' : 'token-stat-card-adult' }} col-span-2 md:col-span-1">
                            <div class="token-stat-header">
                                <div class="token-stat-icon token-stat-icon-usage">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <h3 class="token-stat-title">
                                    @if($isChildTheme)
                                        今月使った分
                                    @else
                                        今月の使用量
                                    @endif
                                </h3>
                            </div>
                            <div class="token-stat-value-container">
                                <p class="token-stat-value token-amount" data-original-amount="{{ $monthlyUsage }}">
                                    {{ number_format($monthlyUsage) }}
                                </p>
                                <p class="token-stat-unit">
                                    @if($isChildTheme)
                                        コイン
                                    @else
                                        トークン
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- フィルター --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 {{ $isChildTheme ? 'shadow-lg border-2 border-purple-300 dark:border-purple-600' : 'shadow-md border border-gray-200 dark:border-gray-700' }}">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <select x-model="filterType" @change="filterTransactions()" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 {{ $isChildTheme ? 'text-base py-3 px-4 font-medium' : 'text-sm py-2' }} focus:ring-2 {{ $isChildTheme ? 'focus:ring-purple-500' : 'focus:ring-blue-500' }}">
                                <option value="all">{{ $isChildTheme ? 'ぜんぶ' : 'すべて表示' }}</option>
                                <option value="purchase">{{ $isChildTheme ? '買ったもの' : '購入のみ' }}</option>
                                <option value="usage">{{ $isChildTheme ? '使ったもの' : '使用のみ' }}</option>
                                <option value="monthly_reset">{{ $isChildTheme ? '毎月もらえるコイン' : '月次リセット' }}</option>
                            </select>
                            
                            <select x-model="filterPeriod" @change="filterTransactions()" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 {{ $isChildTheme ? 'text-base py-3 px-4 font-medium' : 'text-sm py-2' }} focus:ring-2 {{ $isChildTheme ? 'focus:ring-purple-500' : 'focus:ring-blue-500' }}">
                                <option value="all">{{ $isChildTheme ? 'ぜんぶの期間' : '全期間' }}</option>
                                <option value="this_month">{{ $isChildTheme ? '今月' : '今月' }}</option>
                                <option value="last_month">{{ $isChildTheme ? '先月' : '先月' }}</option>
                                <option value="last_3_months">{{ $isChildTheme ? '3ヶ月前まで' : '過去3ヶ月' }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- 履歴リスト --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border {{ $isChildTheme ? 'border-indigo-300 dark:border-indigo-600' : 'border-gray-200 dark:border-gray-700' }} overflow-hidden">
                        <div class="px-6 py-4 border-b {{ $isChildTheme ? 'border-indigo-200 dark:border-indigo-700' : 'border-gray-200 dark:border-gray-700' }}">
                            <h2 class="{{ $isChildTheme ? 'text-xl sm:text-2xl' : 'text-lg' }} font-bold text-gray-900 dark:text-white">
                                {{ $isChildTheme ? 'つかったり買ったりしたりれき' : '取引履歴' }}
                            </h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">日時</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">種類</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">内容</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">増減</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">残高</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($transactions as $transaction)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                                {{ $transaction->created_at->format('Y/m/d H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($transaction->type === 'purchase')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                        購入
                                                    </span>
                                                @elseif($transaction->type === 'consume' || $transaction->type === 'ai_usage')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                        使用
                                                    </span>
                                                @elseif($transaction->type === 'free_reset')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                        月次リセット
                                                    </span>
                                                @elseif($transaction->type === 'admin_adjust')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                                        調整
                                                    </span>
                                                @elseif($transaction->type === 'refund')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                                        返金
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                                {{ $transaction->reason ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if($transaction->amount > 0)
                                                    <span class="text-green-600 dark:text-green-400">+{{ number_format($transaction->amount) }}</span>
                                                @else
                                                    <span class="text-red-600 dark:text-red-400">{{ number_format($transaction->amount) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-300">
                                                {{ number_format($transaction->balance_after) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                                </svg>
                                                <p class="mt-2">取引履歴がありません</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- ページネーション --}}
                        @if($transactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                                {{ $transactions->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>