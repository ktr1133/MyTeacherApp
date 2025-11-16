<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/purchase.css', 'resources/css/dashboard.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/tokens/purchase.js'])
    @endpush

    <div x-data="tokenPurchase()"
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-[100dvh] token-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="token-floating-decoration absolute top-20 left-10 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl"></div>
            <div class="token-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            @click="toggleSidebar"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="token-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="token-header-title text-lg font-bold">
                                    トークン購入
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">追加トークンの購入</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('tokens.history') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="hidden sm:inline">履歴を見る</span>
                        </a>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 px-4 lg:px-6 py-6 custom-scrollbar">
                <div class="max-w-7xl mx-auto space-y-8">
                    {{-- 現在の残高カード --}}
                    <div class="balance-card rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-amber-700 dark:text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                    </svg>
                                    <h2 class="text-lg font-bold text-amber-900 dark:text-amber-100">現在の残高</h2>
                                </div>
                                <div class="text-4xl font-bold text-amber-900 dark:text-amber-100 mb-2">
                                    {{ number_format($balance->balance) }}
                                </div>
                                <div class="text-sm text-amber-700 dark:text-amber-300">
                                    無料: {{ number_format($balance->free_balance) }} / 有料: {{ number_format($balance->paid_balance) }}
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-sm text-amber-700 dark:text-amber-300 mb-1">無料枠リセット</div>
                                <div class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                                    {{ $balance->getNextResetDate()->format('Y年m月d日') }}
                                </div>
                            </div>
                        </div>

                        {{-- 残高ステータス --}}
                        @if($balance->isDepleted())
                            <div class="mt-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-red-700 dark:text-red-300">トークンが不足しています。追加購入が必要です。</p>
                                </div>
                            </div>
                        @elseif($balance->isLow())
                            <div class="mt-4 p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-300">残高が少なくなっています。早めの追加購入をおすすめします。</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- トークンパッケージ一覧 --}}
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">トークンパッケージ</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($packages as $index => $package)
                                <div class="token-package-card rounded-2xl shadow-lg overflow-hidden {{ $index === 1 ? 'popular' : '' }}">
                                    {{-- 人気バッジ --}}
                                    @if($index === 1)
                                        <div class="popular-badge text-center py-2">
                                            <span class="text-white text-sm font-bold">🔥 おすすめ</span>
                                        </div>
                                    @endif

                                    <div class="p-6">
                                        {{-- パッケージ名 --}}
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="token-icon w-12 h-12 rounded-xl flex items-center justify-center">
                                                <svg class="w-6 h-6 text-amber-700 dark:text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $package->name }}</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($package->token_amount) }} トークン</p>
                                            </div>
                                        </div>

                                        {{-- 説明 --}}
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $package->description }}</p>

                                        {{-- 価格 --}}
                                        <div class="price-tag text-center py-4 rounded-xl mb-4">
                                            <div class="text-3xl font-bold text-white">¥{{ number_format($package->price) }}</div>
                                            <div class="text-sm text-white/80">税込</div>
                                        </div>

                                        {{-- 機能一覧 --}}
                                        @if($package->features)
                                            <ul class="space-y-2 mb-6">
                                                @foreach($package->features as $feature)
                                                    <li class="flex items-start gap-2">
                                                        <svg class="w-5 h-5 feature-check shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        {{-- 購入ボタン --}}
                                        <form action="{{ route('tokens.purchase.process') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="package_id" value="{{ $package->id }}">
                                            <input type="hidden" name="payment_method" value="card">
                                            
                                            <button type="submit" class="btn-purchase w-full py-3 rounded-xl text-white font-bold shadow-lg">
                                                購入する
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 注意事項 --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-2">ご購入前にご確認ください</h3>
                                <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                                    <li>• 購入したトークンに有効期限はありません</li>
                                    <li>• 月次無料枠（{{ number_format(config('const.token.free_monthly', 1000000)) }} トークン）は毎月1日に自動リセットされます</li>
                                    <li>• トークンは無料枠から優先的に消費されます</li>
                                    <li>• 決済はStripeを利用した安全な決済システムです</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>