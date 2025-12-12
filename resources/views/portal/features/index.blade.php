@extends('layouts.portal')

@section('title', '機能紹介')
@section('meta_description', 'MyTeacher - AIタスク分解とアバター応援で、お手伝いが子どもの成長になる。月額500円で家族の時間が増える。')
@section('og_title', 'MyTeacher 機能紹介')

@push('styles')
    @vite(['resources/css/portal-common.css', 'resources/css/portal-features.css'])
@endpush

@section('content')
<!-- 背景装飾 -->
<div class="portal-bg-gradient">
    <div class="portal-bg-blob portal-bg-blob-primary" style="top: 10%; left: 10%; width: 400px; height: 400px;"></div>
    <div class="portal-bg-blob portal-bg-blob-purple" style="top: 30%; right: 10%; width: 500px; height: 500px; animation-delay: 0.5s;"></div>
    <div class="portal-bg-blob portal-bg-blob-pink" style="bottom: 20%; left: 30%; width: 450px; height: 450px; animation-delay: 1s;"></div>
</div>

<!-- Hero Section -->
<section class="portal-hero">
    <div class="portal-container text-center">
        <!-- バッジ -->
        <div class="portal-badge mb-6 inline-flex">
            <svg class="w-4 h-4 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">AIが応援する家族のタスク管理</span>
        </div>

        <h1 class="portal-hero-title">
            <span class="gradient-text">お手伝いが、<br class="sm:hidden">子どもの成長になる。</span>
        </h1>

        <div class="glass-card-strong inline-block mb-6 px-6 sm:px-8 py-5 sm:py-6 rounded-2xl max-w-3xl">
            <p class="portal-hero-description">
                ママの負担が、家族の時間になる。<br>
                <span class="text-base sm:text-lg font-semibold text-[#59B9C6]">月額500円</span>で、毎日がもっと楽しくなる。
            </p>
        </div>

        <!-- CTA -->
        <div class="portal-hero-cta flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#pricing" class="portal-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                無料で14日間試す
            </a>
            <a href="#features" class="portal-btn-secondary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                機能を詳しく見る
            </a>
        </div>
    </div>
</section>

<!-- 問題提起セクション -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            こんなお悩み、ありませんか？
        </h2>

        <div class="portal-grid-2 max-w-4xl mx-auto">
            <div class="problem-card">
                <div class="flex items-start gap-3">
                    <svg class="problem-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="problem-text">「お手伝いやった?」毎日言うのに疲れた...</p>
                </div>
            </div>

            <div class="problem-card">
                <div class="flex items-start gap-3">
                    <svg class="problem-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="problem-text">お小遣いの額、どう決めればいいか分からない</p>
                </div>
            </div>

            <div class="problem-card">
                <div class="flex items-start gap-3">
                    <svg class="problem-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="problem-text">子どもが「やった」と言うけど、本当か不明</p>
                </div>
            </div>

            <div class="problem-card">
                <div class="flex items-start gap-3">
                    <svg class="problem-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="problem-text">兄弟姉妹で不公平感が出てしまう</p>
                </div>
            </div>
        </div>

        <div class="glass-card p-5 sm:p-6 rounded-xl max-w-2xl mx-auto mt-8">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-gray-400 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-gray-700 dark:text-gray-300 italic">
                        「週末になると、今週何回お手伝いしたか<br class="hidden sm:inline">
                        記憶を頼りに思い出す作業が苦痛でした」
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">東京都・小3男子ママ</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Before/After解決ストーリー -->
<section class="portal-section" id="solution">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            田中家の変化
        </h2>

        <div class="portal-grid-2 max-w-5xl mx-auto items-center">
            <!-- Before -->
            <div class="comparison-card comparison-card-before">
                <div class="text-center mb-4">
                    <span class="inline-block px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm font-semibold">Before</span>
                </div>
                <div class="aspect-[4/3] portal-placeholder rounded-xl mb-4">
                    <img src="https://placehold.co/600x450/e5e7eb/6b7280?text=Before%3A+%E3%82%A4%E3%83%A9%E3%82%A4%E3%83%A9%E3%81%99%E3%82%8B%E3%83%9E%E3%83%9E" 
                         alt="Before: イライラするママ、忘れる太郎くん" 
                         class="w-full h-full object-cover rounded-xl">
                </div>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <span class="text-red-500">→</span>
                        <span>毎日声かけ → 忘れる → イライラ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-500">→</span>
                        <span>お小遣いの根拠が曖昧 → 不満</span>
                    </li>
                </ul>
            </div>

            <!-- Arrow（モバイルでは縦、PCでは横） -->
            <div class="comparison-arrow text-center sm:text-left">
                <span class="sm:hidden">↓</span>
                <span class="hidden sm:inline">→</span>
            </div>

            <!-- After -->
            <div class="comparison-card comparison-card-after">
                <div class="text-center mb-4">
                    <span class="inline-block px-4 py-2 bg-[#59B9C6] text-white rounded-full text-sm font-semibold">After（導入3ヶ月後）</span>
                </div>
                <div class="aspect-[4/3] portal-placeholder rounded-xl mb-4">
                    <img src="https://placehold.co/600x450/d1f4f7/59b9c6?text=After%3A+%E7%AC%91%E9%A1%94%E3%81%AE%E5%AE%B6%E6%97%8F" 
                         alt="After: 笑顔の家族、アプリを確認する太郎くん" 
                         class="w-full h-full object-cover rounded-xl">
                </div>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <span class="text-green-500">✓</span>
                        <span>アプリが自動通知 → 自分でチェック</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500">✓</span>
                        <span>タスク完了数 = お小遣い → 納得</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="glass-card p-5 sm:p-6 rounded-xl max-w-2xl mx-auto mt-8">
            <p class="text-gray-700 dark:text-gray-300 font-medium mb-2">
                「月980円じゃなくて、500円でこの変化！もっと早く知りたかったです」
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ママの声</p>
        </div>
    </div>
</section>

<!-- 機能紹介（感情的ベネフィット重視） -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30" id="features">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            MyTeacherの主要機能
        </h2>

        <div class="space-y-12 sm:space-y-16">
            <!-- 機能1: AIタスク分解 -->
            <div class="feature-showcase-card">
                <div class="grid lg:grid-cols-2 gap-8 items-center">
                    <div class="order-2 lg:order-1">
                        <div class="feature-icon-wrapper">
                            <svg fill="currentColor" viewBox="0 0 20 20" class="w-10 h-10">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">AIタスク分解</h3>
                        <p class="text-2xl sm:text-3xl font-bold gradient-text-warm mb-4">
                            ママが考える30分を、<br>家族の時間に
                        </p>
                        <p class="feature-description">
                            大きなタスク（例: 夏休みの自由研究）をAIが自動で小さなステップに分解。親が考えて説明する30分が、AIなら3秒で完了。
                        </p>
                        <a href="{{ route('portal.features.ai-decomposition') }}" class="portal-link-underline">
                            詳しく見る →
                        </a>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="screenshot-wrapper">
                            <img src="https://placehold.co/800x600/f3f4f6/59b9c6?text=AI%E3%82%BF%E3%82%B9%E3%82%AF%E5%88%86%E8%A7%A3%E7%94%BB%E9%9D%A2" 
                                 alt="AIタスク分解画面" 
                                 class="screenshot-img">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 機能2: アバター応援 -->
            <div class="feature-showcase-card">
                <div class="grid lg:grid-cols-2 gap-8 items-center">
                    <div class="order-1">
                        <div class="screenshot-wrapper">
                            <img src="https://placehold.co/800x600/f3f4f6/8b5cf6?text=%E3%82%A2%E3%83%90%E3%82%BF%E3%83%BC%E5%BF%9C%E6%8F%B4%E7%94%BB%E9%9D%A2" 
                                 alt="アバター応援画面" 
                                 class="screenshot-img">
                        </div>
                    </div>
                    <div class="order-2">
                        <div class="feature-icon-wrapper">
                            <svg fill="currentColor" viewBox="0 0 20 20" class="w-10 h-10">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">AIアバター応援</h3>
                        <p class="text-2xl sm:text-3xl font-bold gradient-text-warm mb-4">
                            作業じゃなくて、<br>ゲーム感覚で楽しい
                        </p>
                        <p class="feature-description">
                            Stable Diffusion AIで生成されたオリジナル教師キャラクターが、タスク完了時に応援メッセージ。「先生が褒めてくれるから、もっと頑張りたくなる！」
                        </p>
                        <a href="{{ route('portal.features.avatar') }}" class="portal-link-underline">
                            詳しく見る →
                        </a>
                    </div>
                </div>
            </div>

            <!-- 機能3: グループタスク -->
            <div class="feature-showcase-card">
                <div class="grid lg:grid-cols-2 gap-8 items-center">
                    <div class="order-2 lg:order-1">
                        <div class="feature-icon-wrapper">
                            <svg fill="currentColor" viewBox="0 0 20 20" class="w-10 h-10">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">グループタスク</h3>
                        <p class="text-2xl sm:text-3xl font-bold gradient-text-warm mb-4">
                            兄弟姉妹で<br>楽しく競争
                        </p>
                        <p class="feature-description">
                            同じタスク（例: お皿洗い）を兄弟姉妹に同時割当。誰が早く終わるか、楽しく競争。無料プランは月3回まで、有料プランは無制限。
                        </p>
                        <a href="{{ route('portal.features.group-tasks') }}" class="portal-link-underline">
                            詳しく見る →
                        </a>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="screenshot-wrapper">
                            <img src="https://placehold.co/800x600/f3f4f6/ec4899?text=%E3%82%B0%E3%83%AB%E3%83%BC%E3%83%97%E3%82%BF%E3%82%B9%E3%82%AF%E7%94%BB%E9%9D%A2" 
                                 alt="グループタスク画面" 
                                 class="screenshot-img">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="#pricing" class="portal-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                プランを見る
            </a>
        </div>
    </div>
</section>

<!-- プラン比較セクション -->
<section class="portal-section" id="pricing">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            あなたの家族に合ったプランを選べます
        </h2>

        <div class="portal-grid-3 max-w-6xl mx-auto">
            <!-- 無料プラン -->
            <div class="pricing-card">
                <h3 class="pricing-plan-name">無料プラン</h3>
                <div class="mb-6">
                    <div class="pricing-amount">¥0</div>
                    <div class="pricing-period">/月</div>
                </div>
                <ul class="pricing-feature-list">
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">個人タスク無制限</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">AIタスク分解</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">アバター応援</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">グループタスク: 月3回</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">月次レポート: 初月のみ</span>
                    </li>
                </ul>
                <a href="{{ route('register') }}" class="portal-btn-secondary w-full">
                    無料で始める
                </a>
            </div>

            <!-- ファミリープラン（人気） -->
            <div class="pricing-card pricing-card-popular">
                <h3 class="pricing-plan-name">ファミリープラン</h3>
                <div class="mb-6">
                    <div class="pricing-amount">¥500</div>
                    <div class="pricing-period">/月（1日あたり約17円）</div>
                </div>
                <ul class="pricing-feature-list">
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text"><strong>グループタスク無制限</strong></span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">最大6名まで</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">月次レポート（PDF）</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">定期タスク自動作成</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text"><strong>14日間無料トライアル</strong></span>
                    </li>
                </ul>
                <a href="{{ route('portal.features.pricing') }}" class="portal-btn-primary w-full">
                    今すぐ試す
                </a>
            </div>

            <!-- エンタープライズプラン -->
            <div class="pricing-card">
                <h3 class="pricing-plan-name">エンタープライズ</h3>
                <div class="mb-6">
                    <div class="pricing-amount">¥3,000</div>
                    <div class="pricing-period">/月 + 追加メンバー¥150</div>
                </div>
                <ul class="pricing-feature-list">
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">最大20名（基本）</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">最大5グループ</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">統計レポート（将来実装）</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">優先サポート（将来実装）</span>
                    </li>
                    <li class="pricing-feature-item">
                        <svg class="pricing-feature-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="pricing-feature-text">14日間無料トライアル</span>
                    </li>
                </ul>
                <a href="{{ route('portal.features.pricing') }}" class="portal-btn-secondary w-full">
                    詳しく見る
                </a>
            </div>
        </div>

        <!-- 価格比較（アンカリング） -->
        <div class="glass-card p-6 rounded-xl max-w-2xl mx-auto mt-12 text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-3">価格比較</p>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">家庭教師</p>
                    <p class="text-lg font-bold text-gray-400 line-through">¥20,000/月</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">学習塾</p>
                    <p class="text-lg font-bold text-gray-400 line-through">¥15,000/月</p>
                </div>
                <div>
                    <p class="text-[#59B9C6] font-semibold">MyTeacher</p>
                    <p class="text-2xl font-bold gradient-text">¥500/月</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">自主性が育つ</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- お客様の声 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            利用者の声
        </h2>

        <div class="portal-grid-3 max-w-6xl mx-auto">
            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「月10回 → 毎日に！ お手伝い回数が3倍になりました。月次レポートを見せたら、子どもが自分で目標を立てるようになりました。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">田</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">田中様</div>
                        <div class="testimonial-meta">東京都・小3男子ママ（利用3ヶ月）</div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「生徒30名の宿題管理がラクに。AIタスク分解で『今週の宿題』を自動で小分けにして配信。保護者からも『見える化されて安心』と好評です。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">山</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">山田先生</div>
                        <div class="testimonial-meta">学習塾経営（エンタープライズプラン）</div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「想定外の効果: 兄妹仲が良くなった！グループタスク機能で、兄妹が協力してお皿洗いをするように。MyTeacherは家族のコミュニケーションツールです。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">佐</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">佐藤様</div>
                        <div class="testimonial-meta">大阪府・小2女子・小5男子ママ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ簡易版 -->
<section class="portal-section">
    <div class="portal-container max-w-4xl">
        <h2 class="portal-section-title text-center gradient-text">
            よくある質問
        </h2>

        <div class="space-y-4">
            <div class="faq-item">
                <div class="faq-question">
                    <span>無料プランと有料プランの違いは？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <div class="faq-answer">
                    グループタスク（複数人への同時割当）が、無料プランは月3回まで、有料プランは無制限です。また、月次レポート機能は有料プランで継続利用できます（無料プランは初月のみ）。
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>AIタスク分解は有料機能ですか？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <div class="faq-answer">
                    いいえ、全プランで利用可能です。ただし、トークンを消費します（月次無料枠あり）。
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>途中でプラン変更できますか？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <div class="faq-answer">
                    はい、いつでも変更可能です。日割り計算で返金・請求いたします。
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>14日間無料トライアルって本当に無料？</span>
                    <svg class="faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <div class="faq-answer">
                    はい、完全無料です。トライアル期間中にキャンセルすれば、一切費用は発生しません。
                </div>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('portal.faq') }}" class="portal-link-underline">
                FAQをもっと見る →
            </a>
        </div>
    </div>
</section>

<!-- 最終CTA -->
<section class="portal-section">
    <div class="portal-container max-w-4xl">
        <div class="cta-section">
            <div class="cta-content">
                <h2 class="cta-title">
                    さあ、今日から始めよう。<br>
                    家族の時間を、もっと楽しく。
                </h2>
                <p class="cta-description">
                    ✅ 14日間完全無料<br>
                    ✅ クレジットカード登録不要（無料プラン）<br>
                    ✅ いつでもキャンセル可能
                </p>
                <a href="{{ route('register') }}" class="cta-btn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    今すぐ無料で始める
                </a>
                <p class="text-white/70 text-sm mt-4">すでに3,000家族が利用中（2025年12月時点）</p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
// FAQ アコーディオン
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            // 他のアイテムを閉じる
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });
            
            // クリックしたアイテムをトグル
            item.classList.toggle('active');
        });
    });
});
</script>
@endpush
