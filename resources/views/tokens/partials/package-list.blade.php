{{-- 現在の残高表示 --}}
<div class="balance-card mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
            @if($isChildTheme)
                <div class="coin-icon-large">
                    <span class="text-4xl">🪙</span>
                </div>
            @else
                <div class="icon-wrapper">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($isChildTheme)
                        いまのコイン
                    @else
                        現在の残高
                    @endif
                </p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($balance->balance) }}
                    @if($isChildTheme)
                        <span class="text-lg text-amber-600">コイン</span>
                    @else
                        <span class="text-lg text-gray-600 dark:text-gray-400">トークン</span>
                    @endif
                </p>
            </div>
        </div>
        
        <a href="{{ route('tokens.history') }}" class="btn-history">
            @if($isChildTheme)
                りれき
            @else
                履歴を見る
            @endif
        </a>
    </div>

    {{-- 無料枠/有料枠の内訳 --}}
    <div class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-700/30">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    無料枠
                </p>
                <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">
                    {{ number_format($balance->free_balance) }}
                </p>
            </div>
            <div>
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    有料
                </p>
                <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">
                    {{ number_format($balance->paid_balance) }}
                </p>
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
                <p class="text-sm font-semibold text-red-700 dark:text-red-300">
                    @if($isChildTheme)
                        コインが なくなっちゃった...追加で買おう！
                    @else
                        トークンが不足しています。追加購入が必要です。
                    @endif
                </p>
            </div>
        </div>
    @elseif($balance->isLow())
        <div class="mt-4 p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 rounded-lg">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-300">
                    @if($isChildTheme)
                        コインが少なくなってきたよ。そろそろ買おうかな？
                    @else
                        残高が少なくなっています。早めの追加購入をおすすめします。
                    @endif
                </p>
            </div>
        </div>
    @endif
</div>

{{-- パッケージ一覧 --}}
<div>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4 sm:mb-6">
        @if($isChildTheme)
            どれにする？
        @else
            トークンパッケージ
        @endif
    </h2>
    
    <div class="package-grid">
        @forelse($packages as $package)
            <div class="package-card {{ $isChildTheme ? 'child-theme' : '' }}">
                {{-- パッケージヘッダー --}}
                <div class="package-header">
                    @if($isChildTheme)
                        <div class="coin-badge-large">
                            <span class="text-3xl">🪙</span>
                        </div>
                    @endif
                    <h3 class="package-name">{{ $package->name }}</h3>
                </div>

                {{-- トークン数 --}}
                <div class="package-tokens">
                    <span class="token-amount">{{ number_format($package->token_amount) }}</span>
                    <span class="token-label">
                        @if($isChildTheme)
                            コイン
                        @else
                            トークン
                        @endif
                    </span>
                </div>

                {{-- 価格 --}}
                <div class="package-price">
                    <span class="price-amount">¥{{ number_format($package->price) }}</span>
                    <span class="price-label">(税込)</span>
                </div>

                {{-- 説明 --}}
                @if($package->description)
                    <p class="package-description">{{ $package->description }}</p>
                @endif

                {{-- 購入ボタン --}}
                <form action="{{ route('tokens.purchase.process') }}" method="POST" class="mt-auto">
                    @csrf
                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                    <button type="submit" class="btn-purchase {{ $isChildTheme ? 'child-theme' : '' }}">
                        @if($isChildTheme)
                            @if($user->requiresPurchaseApproval())
                                <span class="emoji">🙏</span>
                                <span>お願いする</span>
                            @else
                                <span class="emoji">🪙</span>
                                <span>買う</span>
                            @endif
                        @else
                            @if($user->requiresPurchaseApproval())
                                リクエストを送る
                            @else
                                購入する
                            @endif
                        @endif
                    </button>
                </form>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-600 dark:text-gray-400">
                    @if($isChildTheme)
                        買えるコインがないよ...
                    @else
                        現在購入可能なパッケージはありません。
                    @endif
                </p>
            </div>
        @endforelse
    </div>
</div>

{{-- 注意事項（大人向けのみ） --}}
@if(!$isChildTheme)
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-2">ご購入前にご確認ください</h3>
                <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                    <li>• 購入したトークンに有効期限はありません</li>
                    <li>• トークンは無料枠から優先的に消費されます</li>
                    <li>• 決済はStripeを利用した安全な決済システムです</li>
                </ul>
            </div>
        </div>
    </div>
@endif