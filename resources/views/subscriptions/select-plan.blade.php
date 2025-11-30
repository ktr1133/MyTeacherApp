<x-app-layout>
    @push('styles')
        @vite(['resources/css/subscriptions/select-plan.css'])
    @endpush

    <div class="flex min-h-[100dvh] subscription-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾（大人向けのみ） --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="subscription-floating-decoration absolute top-20 left-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="subscription-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 subscription-header-blur shadow-sm">
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
                            <div class="subscription-header-icon w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="dashboard-header-title text-lg font-bold">
                                    サブスクリプション管理
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400 hidden sm:block">
                                    グループ機能のプラン選択
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- 右側のボタン --}}
                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ route('group.edit') }}" 
                           class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="hidden sm:inline">グループ管理へ</span>
                        </a>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-7xl mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    
                    {{-- 成功・エラーメッセージ --}}
                    @if(session('success'))
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg mb-6">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg mb-6">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- 現在のサブスクリプション情報（加入者のみ表示） --}}
                    @if($currentSubscription)
                        <div class="subscription-card-modern p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
                            <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                            現在のサブスクリプション
                                        </h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            プランを変更する場合は、下記から選択してください
                                        </p>
                                    </div>
                                </div>
                                
                                {{-- 管理ボタン --}}
                                <div class="flex gap-2 flex-wrap">
                                    <a href="{{ route('subscriptions.billing-portal') }}" 
                                       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition shadow-md text-sm">
                                        支払い情報を管理
                                    </a>
                                    <button type="button" 
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition shadow-md text-sm"
                                            data-cancel-subscription>
                                        サブスクリプションをキャンセル
                                    </button>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">プラン</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $plans[$currentSubscription['plan']]['name'] ?? 'Unknown' }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ステータス</p>
                                    <p class="text-lg font-semibold">
                                        @if($currentSubscription['active'])
                                            <span class="text-green-600 dark:text-green-400">✓ 有効</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400">× 無効</span>
                                        @endif
                                    </p>
                                </div>
                                @if($currentSubscription['trial_ends_at'])
                                    <div class="space-y-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">トライアル終了日</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($currentSubscription['trial_ends_at'])->format('Y年m月d日') }}
                                        </p>
                                    </div>
                                @endif
                                @if($currentSubscription['ends_at'])
                                    <div class="space-y-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">有効期限</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($currentSubscription['ends_at'])->format('Y年m月d日') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- プランカード一覧 --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        {{-- 無料プラン --}}
                        <div class="plan-card {{ !$currentSubscription ? 'current-plan' : '' }}">
                            <div class="plan-header">
                                <h3 class="plan-title">無料プラン</h3>
                                <div class="plan-price">
                                    <span class="price-amount">¥0</span>
                                    <span class="price-period">/月</span>
                                </div>
                            </div>
                            
                            <div class="plan-features">
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>最大6名まで</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>グループタスク月3回まで</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>実績レポート初月のみ</span>
                                </div>
                            </div>

                            @if(!$currentSubscription)
                                <div class="plan-action">
                                    <button disabled class="plan-button-disabled">
                                        現在のプラン
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- ファミリープラン --}}
                        <div class="plan-card {{ $currentSubscription && $currentSubscription['plan'] === 'family' ? 'current-plan' : 'featured-plan' }}">
                            @if(!$currentSubscription || $currentSubscription['plan'] !== 'family')
                                <div class="plan-badge">おすすめ</div>
                            @endif
                            
                            <div class="plan-header">
                                <h3 class="plan-title">ファミリープラン</h3>
                                <div class="plan-price">
                                    <span class="price-amount">¥{{ number_format($plans['family']['amount']) }}</span>
                                    <span class="price-period">/月</span>
                                </div>
                            </div>
                            
                            <div class="plan-features">
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>最大{{ $plans['family']['max_members'] }}名まで</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>グループタスク無制限</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>実績レポート機能</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    @if($currentSubscription && $currentSubscription['plan'] === 'family' && $currentSubscription['trial_ends_at'])
                                        @php
                                            $trialEnd = \Carbon\Carbon::parse($currentSubscription['trial_ends_at']);
                                            $daysLeft = now()->diffInDays($trialEnd, false);
                                        @endphp
                                        @if($daysLeft > 0)
                                            <span class="text-orange-600 dark:text-orange-400 font-semibold">
                                                トライアル中（残り{{ ceil($daysLeft) }}日）
                                            </span>
                                        @else
                                            <span>14日間無料トライアル</span>
                                        @endif
                                    @else
                                        <span>14日間無料トライアル</span>
                                    @endif
                                </div>
                            </div>

                            <div class="plan-action">
                                @if($currentSubscription && $currentSubscription['plan'] === 'family')
                                    <button disabled class="plan-button-disabled">
                                        加入中のプラン
                                    </button>
                                @elseif($currentSubscription)
                                    {{-- プラン変更 --}}
                                    <button type="button" 
                                            class="plan-button-primary" 
                                            data-plan-change="family"
                                            data-plan-name="ファミリープラン">
                                        このプランに変更
                                    </button>
                                @else
                                    {{-- 新規契約 --}}
                                    <button type="button" 
                                            class="plan-button-primary" 
                                            data-plan-subscribe="family"
                                            data-plan-name="ファミリープラン"
                                            data-plan-price="500">
                                        このプランを選択
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- エンタープライズプラン --}}
                        <div class="plan-card {{ $currentSubscription && $currentSubscription['plan'] === 'enterprise' ? 'current-plan' : '' }}">
                            <div class="plan-header">
                                <h3 class="plan-title">エンタープライズプラン</h3>
                                <div class="plan-price">
                                    <span class="price-amount">¥{{ number_format($plans['enterprise']['amount']) }}</span>
                                    <span class="price-period">/月</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    基本{{ $plans['enterprise']['max_members'] }}名まで
                                </p>
                            </div>
                            
                            <div class="plan-features">
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>最大{{ $plans['enterprise']['max_members'] }}名 + 追加可能</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>追加メンバー¥150/月/名</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>グループタスク無制限</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>実績レポート機能</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>最大5グループまで</span>
                                </div>
                                <div class="feature-item">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    @if($currentSubscription && $currentSubscription['plan'] === 'enterprise' && $currentSubscription['trial_ends_at'])
                                        @php
                                            $trialEnd = \Carbon\Carbon::parse($currentSubscription['trial_ends_at']);
                                            $daysLeft = now()->diffInDays($trialEnd, false);
                                        @endphp
                                        @if($daysLeft > 0)
                                            <span class="text-orange-600 dark:text-orange-400 font-semibold">
                                                トライアル中（残り{{ ceil($daysLeft) }}日）
                                            </span>
                                        @else
                                            <span>14日間無料トライアル</span>
                                        @endif
                                    @else
                                        <span>14日間無料トライアル</span>
                                    @endif
                                </div>
                            </div>

                            <div class="plan-action">
                                @if($currentSubscription && $currentSubscription['plan'] === 'enterprise')
                                    <button disabled class="plan-button-disabled">
                                        加入中のプラン
                                    </button>
                                @elseif($currentSubscription)
                                    {{-- プラン変更 --}}
                                    <button type="button" 
                                            class="plan-button-secondary" 
                                            data-plan-change="enterprise"
                                            data-plan-name="エンタープライズプラン">
                                        このプランに変更
                                    </button>
                                @else
                                    {{-- 新規契約 --}}
                                    <button type="button" 
                                            class="plan-button-secondary" 
                                            data-enterprise-subscribe
                                            data-plan-name="エンタープライズプラン">
                                        このプランを選択
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- エンタープライズプラン用モーダル --}}
                    <div id="enterprise-modal" class="modal hidden">
                        <div class="modal-overlay"></div>
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">エンタープライズプラン - メンバー数設定</h3>
                                <button type="button" class="modal-close" data-modal-close>
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <form action="{{ route('subscriptions.checkout') }}" method="POST" class="modal-body">
                                @csrf
                                <input type="hidden" name="plan" value="enterprise">
                                
                                <div class="mb-6">
                                    <label for="additional_members" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        追加メンバー数（基本20名 + 追加メンバー）
                                    </label>
                                    <input 
                                        type="number" 
                                        id="additional_members" 
                                        name="additional_members" 
                                        min="0" 
                                        max="100" 
                                        value="0"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-gray-100"
                                    >
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                        追加メンバー1名あたり¥150/月が加算されます
                                    </p>
                                </div>

                                <div class="price-summary mb-6">
                                    <div class="flex justify-between text-sm mb-2">
                                        <span>基本料金（20名まで）</span>
                                        <span>¥3,000</span>
                                    </div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span>追加メンバー料金</span>
                                        <span id="additional-price">¥0</span>
                                    </div>
                                    <div class="border-t border-gray-300 dark:border-gray-600 pt-2 flex justify-between font-bold">
                                        <span>合計</span>
                                        <span id="total-price">¥3,000</span>
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    <button type="button" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition" data-modal-close>
                                        キャンセル
                                    </button>
                                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                        このプランで申し込む
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- 注意事項 --}}
                    <div class="mt-8 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            サブスクリプションについて
                        </h4>
                        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>全プランで14日間の無料トライアル期間があります</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>決済はStripeで安全に処理されます</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>いつでもキャンセル可能です（即時反映）</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>サブスクリプション登録後は通知で最終結果をお知らせします</span>
                            </li>
                        </ul>
                    </div>

                    {{-- 請求履歴（サブスクリプション加入者のみ） --}}
                    @if($currentSubscription && count($invoices) > 0)
                        <div class="mt-8 subscription-card-modern p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                請求履歴
                            </h2>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3">日付</th>
                                            <th class="px-4 py-3">金額</th>
                                            <th class="px-4 py-3">ステータス</th>
                                            <th class="px-4 py-3">請求書</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr class="border-b dark:border-gray-700">
                                                <td class="px-4 py-3">
                                                    {{ \Carbon\Carbon::parse($invoice['date'])->format('Y年m月d日') }}
                                                </td>
                                                <td class="px-4 py-3 font-semibold">
                                                    ¥{{ number_format($invoice['total']) }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($invoice['status'] === 'paid')
                                                        <span class="text-green-600 dark:text-green-400">支払い済み</span>
                                                    @elseif($invoice['status'] === 'open')
                                                        <span class="text-yellow-600 dark:text-yellow-400">未払い</span>
                                                    @else
                                                        <span class="text-gray-600 dark:text-gray-400">{{ $invoice['status'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($invoice['invoice_pdf'])
                                                        <a href="{{ $invoice['invoice_pdf'] }}" target="_blank" 
                                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                            PDF
                                                        </a>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    {{-- 汎用確認ダイアログ --}}
    <x-confirm-dialog />

    {{-- プラン変更モーダル --}}
    @if($currentSubscription)
        <x-subscription-change-modal :currentPlan="$currentSubscription['plan']" :plans="$plans" />
    @endif

    @push('scripts')
        @vite(['resources/js/subscriptions/select-plan.js'])
    @endpush
</x-app-layout>
