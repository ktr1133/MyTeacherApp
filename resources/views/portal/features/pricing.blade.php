@extends('layouts.portal')

@section('title', '料金プラン')
@section('meta_description', 'MyTeacher 料金プラン - 無料プラン、ファミリープラン（¥500/月）、エンタープライズプラン（¥3,000/月〜）。14日間無料トライアル実施中。')
@section('og_title', '料金プラン - MyTeacher')

@push('styles')
    @vite(['resources/css/portal-common.css', 'resources/css/portal-features.css'])
@endpush

@section('content')
<!-- 背景装飾 -->
<div class="portal-bg-gradient">
    <div class="portal-bg-blob portal-bg-blob-primary" style="top: 10%; right: 10%; width: 400px; height: 400px;"></div>
    <div class="portal-bg-blob portal-bg-blob-purple" style="bottom: 20%; left: 10%; width: 450px; height: 450px; animation-delay: 0.7s;"></div>
</div>

<!-- パンくずリスト -->
<section class="px-4 sm:px-6 lg:px-8 py-6">
    <div class="portal-container">
        <nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('portal.home') }}" class="hover:text-[#59B9C6] transition">ポータル</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('portal.features.index') }}" class="hover:text-[#59B9C6] transition">機能紹介</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white font-medium">料金プラン</span>
        </nav>
    </div>
</section>

<!-- Hero -->
<section class="portal-section">
    <div class="portal-container">
        <div class="text-center mb-12">
            <h1 class="text-4xl sm:text-5xl font-bold gradient-text mb-6">
                シンプルで分かりやすい料金プラン
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                家族の規模に合わせて選べる3つのプラン<br>
                すべてのプランで14日間無料トライアル実施中
            </p>
        </div>
    </div>
</section>

<!-- 料金プラン -->
<section class="portal-section">
    <div class="portal-container">
        <div class="grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- 無料プラン -->
            <div class="pricing-card">
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">無料プラン</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">まずは試してみたい方に</p>
                    
                    <div class="mb-6">
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-bold text-gray-900 dark:text-white">¥0</span>
                            <span class="text-gray-500 dark:text-gray-400">/月</span>
                        </div>
                    </div>

                    <ul class="pricing-feature-list mb-8">
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>AIタスク分解（トークン消費）</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>AIアバター生成・応援</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>グループタスク: 月3個まで</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>月次レポート: 初月のみ</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>月次無料トークンあり</li>
                    </ul>

                    <a href="{{ route('register') }}" class="portal-btn-ghost w-full text-center">
                        無料で始める
                    </a>
                </div>
            </div>

            <!-- ファミリープラン -->
            <div class="pricing-card pricing-card-popular">
                <div class="popular-badge">人気No.1</div>
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ファミリープラン</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">一般家庭におすすめ</p>
                    
                    <div class="mb-6">
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-5xl font-bold gradient-text">¥500</span>
                            <span class="text-gray-500 dark:text-gray-400">/月</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">1日あたり約¥17</p>
                    </div>

                    <ul class="pricing-feature-list mb-8">
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><strong>無料プランの全機能</strong></li>
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>メンバー数: 6人</li>
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>グループ数: 1</li>
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>グループタスク: 無制限</li>
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>自動スケジュール機能</li>
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>月次レポート: 毎月</li>
                        <li><svg class="pricing-feature-icon text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>14日間無料トライアル</li>
                    </ul>

                    <a href="{{ route('register') }}" class="portal-btn-primary w-full text-center">
                        14日間無料で試す
                    </a>
                </div>
            </div>

            <!-- エンタープライズプラン -->
            <div class="pricing-card">
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">エンタープライズ</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">大家族・施設向け</p>
                    
                    <div class="mb-6">
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-5xl font-bold text-gray-900 dark:text-white">¥3,000</span>
                            <span class="text-gray-500 dark:text-gray-400">/月〜</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">+¥150/人（21人目以降）</p>
                    </div>

                    <ul class="pricing-feature-list mb-8">
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><strong>ファミリープランの全機能</strong></li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>メンバー数: 20人（基本）</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>グループ数: 5</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>追加メンバー: ¥150/人</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>自動スケジュール機能</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>優先サポート</li>
                        <li><svg class="pricing-feature-icon text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>14日間無料トライアル</li>
                    </ul>

                    <a href="{{ route('register') }}" class="portal-btn-ghost w-full text-center">
                        14日間無料で試す
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 詳細比較表 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text mb-12">
            詳細機能比較
        </h2>

        <div class="comparison-table-wrapper max-w-6xl mx-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th class="text-left">機能</th>
                        <th class="text-center">無料</th>
                        <th class="text-center">ファミリー</th>
                        <th class="text-center">エンタープライズ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="category-header">基本機能</td>
                    </tr>
                    <tr>
                        <td>AIタスク分解</td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>AIアバター生成</td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>月次無料トークン</td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="category-header">グループ機能</td>
                    </tr>
                    <tr>
                        <td>グループタスク作成</td>
                        <td class="text-center">月3個</td>
                        <td class="text-center">無制限</td>
                        <td class="text-center">無制限</td>
                    </tr>
                    <tr>
                        <td>自動スケジュール</td>
                        <td class="text-center"><span class="x-icon">×</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>グループ数</td>
                        <td class="text-center">-</td>
                        <td class="text-center">1</td>
                        <td class="text-center">5</td>
                    </tr>
                    <tr>
                        <td>メンバー数</td>
                        <td class="text-center">-</td>
                        <td class="text-center">6</td>
                        <td class="text-center">20〜</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="category-header">レポート</td>
                    </tr>
                    <tr>
                        <td>月次レポート</td>
                        <td class="text-center">初月のみ</td>
                        <td class="text-center">毎月</td>
                        <td class="text-center">毎月</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="category-header">サポート</td>
                    </tr>
                    <tr>
                        <td>メールサポート</td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                    </tr>
                    <tr>
                        <td>優先サポート</td>
                        <td class="text-center"><span class="x-icon">×</span></td>
                        <td class="text-center"><span class="x-icon">×</span></td>
                        <td class="text-center"><span class="check-icon">✓</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="portal-section">
    <div class="portal-container max-w-4xl">
        <h2 class="portal-section-title text-center gradient-text mb-12">
            よくある質問
        </h2>

        <div class="space-y-4">
            <div class="faq-item">
                <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    <span>無料トライアル期間中に解約できますか？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    はい、可能です。14日間の無料トライアル期間中はいつでも解約でき、料金は一切発生しません。解約後も無料プランとして継続利用できます。
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    <span>プラン変更はいつでもできますか？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    はい、いつでも可能です。アップグレードは即座に反映され、ダウングレードは次回請求日から適用されます。日割り計算も対応しています。
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    <span>トークンとは何ですか？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    AI機能（タスク分解、アバター生成など）を使用する際に消費されるポイントです。毎月無料枠が付与され、不足した場合は追加購入も可能です。
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    <span>支払い方法は何がありますか？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="faq-answer">
                    クレジットカード（Visa、Mastercard、JCB、American Express）に対応しています。Stripe経由での安全な決済処理を行っています。
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="portal-section bg-gradient-to-r from-[#59B9C6] via-purple-500 to-pink-500">
    <div class="portal-container max-w-3xl text-center text-white">
        <h2 class="text-3xl sm:text-4xl font-bold mb-6">
            14日間、完全無料で<br class="sm:hidden">すべての機能を体験
        </h2>
        <p class="text-lg mb-8 opacity-90">
            クレジットカード登録後、すぐに有料プラン機能をお試しいただけます<br>
            期間中の解約で料金は一切かかりません
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-[#59B9C6] rounded-lg font-bold hover:bg-gray-100 transition shadow-lg">
                無料トライアルを始める
            </a>
            <a href="{{ route('portal.features.index') }}" class="px-8 py-4 bg-white/20 backdrop-blur-sm text-white rounded-lg font-bold hover:bg-white/30 transition border-2 border-white">
                機能詳細を見る
            </a>
        </div>
    </div>
</section>

@push('scripts')
<script>
// FAQ accordion toggle (already handled by onclick in HTML)
</script>
@endpush

@endsection
