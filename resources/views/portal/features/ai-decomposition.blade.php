@extends('layouts.portal')

@section('title', 'AIタスク分解機能')
@section('meta_description', 'MyTeacher AIタスク分解 - 大きなタスクを自動で小さなステップに分解。親が考える30分を、家族の時間に。')
@section('og_title', 'AIタスク分解機能 - MyTeacher')

@push('styles')
    @vite(['resources/css/portal-common.css', 'resources/css/portal-features.css'])
@endpush

@section('content')
<!-- 背景装飾 -->
<div class="portal-bg-gradient">
    <div class="portal-bg-blob portal-bg-blob-primary" style="top: 15%; right: 10%; width: 400px; height: 400px;"></div>
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
            <span class="text-gray-900 dark:text-white font-medium">AIタスク分解</span>
        </nav>
    </div>
</section>

<!-- Hero -->
<section class="portal-section">
    <div class="portal-container">
        <div class="text-center mb-12">
            <div class="feature-icon-wrapper inline-flex mb-6">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-12 h-12">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                </svg>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold gradient-text mb-6">
                AIタスク分解機能
            </h1>
            <p class="text-2xl sm:text-3xl font-bold gradient-text-warm max-w-3xl mx-auto">
                ママが考える30分を、<br class="sm:hidden">家族の時間に
            </p>
        </div>

        <!-- メインビジュアル -->
        <div class="screenshot-wrapper max-w-4xl mx-auto mb-12">
            <img src="{{ asset('images/portal/decomposition-by-ai.gif') }}" 
                 alt="AIタスク分解フロー" 
                 class="screenshot-img">
        </div>
    </div>
</section>

<!-- 問題と解決策 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <div class="portal-grid-2 max-w-5xl mx-auto items-center gap-8">
            <!-- Before -->
            <div class="space-y-4">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-6">
                    <span class="text-red-500">✗</span> これまでの方法
                </h2>
                <div class="problem-card">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">😰</span>
                        <div>
                            <p class="problem-text">「夏休みの自由研究やりなさい」</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">→ 何から始めればいいか分からず、進まない</p>
                        </div>
                    </div>
                </div>
                <div class="problem-card">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">😓</span>
                        <div>
                            <p class="problem-text">親が細かく段取りを説明</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">→ 考える時間30分 + 説明時間20分</p>
                        </div>
                    </div>
                </div>
                <div class="problem-card">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">😔</span>
                        <div>
                            <p class="problem-text">子どもは受け身、自分で考えない</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">→ 自主性が育たない</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- After -->
            <div class="space-y-4">
                <h2 class="text-2xl sm:text-3xl font-bold gradient-text mb-6">
                    <span class="text-green-500">✓</span> MyTeacherなら
                </h2>
                <div class="solution-card">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">🎯</span>
                        <div>
                            <p class="text-gray-700 dark:text-gray-300 font-medium">AIが3秒で8ステップに自動分解</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">→ 「テーマを決める」「材料を集める」など具体的に</p>
                        </div>
                    </div>
                </div>
                <div class="solution-card">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚡</span>
                        <div>
                            <p class="text-gray-700 dark:text-gray-300 font-medium">親の作業時間: 30分 → 1分</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">→ 29分を家族の時間に充てられる</p>
                        </div>
                    </div>
                </div>
                <div class="solution-card">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">🌟</span>
                        <div>
                            <p class="text-gray-700 dark:text-gray-300 font-medium">子どもが自分で計画・実行</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">→ 自主性と計画力が育つ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 使い方（3ステップ） -->
<section class="portal-section">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            使い方はカンタン3ステップ
        </h2>

        <div class="portal-grid-3 max-w-5xl mx-auto">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">タスクを入力</h3>
                <p class="step-description">
                    「夏休みの自由研究」「部屋の片付け」など、大きなタスクを入力するだけ
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">AIが分解</h3>
                <p class="step-description">
                    わずか3秒で、具体的な小さなステップに自動分解。編集も可能
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">実行・完了</h3>
                <p class="step-description">
                    子どもが1つずつクリア。アバターが応援してくれるから楽しく続く
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 実例紹介 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            分解例
        </h2>

        <div class="space-y-8 max-w-4xl mx-auto">
            <!-- 例1 -->
            <div class="portal-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-sm font-semibold">小4向け</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">「夏休みの自由研究をやる」</h3>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">↓ AIが分解</p>
                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">1.</span>
                                <span>テーマを3つ考える</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">2.</span>
                                <span>親と相談してテーマを1つ決める</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">3.</span>
                                <span>必要な材料をリストアップ</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">4.</span>
                                <span>材料を買いに行く</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-start gap-2">
                            <span class="text-[#59B9C6] font-bold">5.</span>
                            <span>実験・観察を行う（3日間）</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#59B9C6] font-bold">6.</span>
                            <span>結果をノートにまとめる</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#59B9C6] font-bold">7.</span>
                            <span>模造紙に清書する</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#59B9C6] font-bold">8.</span>
                            <span>発表練習をする</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 例2 -->
            <div class="portal-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm font-semibold">小1向け</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">「お部屋をきれいにする」</h3>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">↓ AIが分解</p>
                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">1.</span>
                                <span>床に落ちているものを拾う</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">2.</span>
                                <span>おもちゃを箱に入れる</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-[#59B9C6] font-bold">3.</span>
                                <span>本を本棚に戻す</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-start gap-2">
                            <span class="text-[#59B9C6] font-bold">4.</span>
                            <span>机の上を整理する</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#59B9C6] font-bold">5.</span>
                            <span>ゴミ箱のゴミを捨てる</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- お客様の声 -->
<section class="portal-section">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            実際の声
        </h2>

        <div class="portal-grid-2 max-w-4xl mx-auto">
            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「『読書感想文を書く』を8ステップに分けてくれて、子どもが自分で計画的に進められるようになりました。今まで『書きなさい』と言うだけで終わっていたのが、具体的な行動に変わりました。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">鈴</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">鈴木様</div>
                        <div class="testimonial-meta">神奈川県・小4男子ママ</div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「AIが年齢に合わせて分解してくれるのが驚き。小1の娘には『お部屋の片付け』を5ステップ、小5の息子には『自由研究』を10ステップに。それぞれに最適な粒度で提案してくれます。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">高</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">高橋様</div>
                        <div class="testimonial-meta">東京都・小1女子・小5男子ママ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 技術仕様（オプション） -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container max-w-4xl">
        <h2 class="portal-section-title text-center gradient-text">
            技術情報
        </h2>

        <div class="glass-card p-6 sm:p-8 rounded-2xl">
            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                        </svg>
                        AI技術
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>OpenAI GPT-4o-mini</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>高精度な自然言語処理</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>年齢・レベルに応じた最適化</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        利用料金
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span><strong>全プラン利用可能</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>トークン消費型（月次無料枠あり）</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>追加購入も可能</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="portal-section">
    <div class="portal-container max-w-3xl">
        <div class="cta-section">
            <div class="cta-content">
                <h2 class="cta-title text-2xl sm:text-3xl">
                    AIタスク分解を<br class="sm:hidden">今すぐ体験
                </h2>
                <p class="cta-description">
                    無料プランでも全機能利用可能<br>
                    クレジットカード登録不要
                </p>
                <a href="{{ route('register') }}" class="cta-btn">
                    無料で始める
                </a>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('portal.features.index') }}" class="portal-link-underline">
                ← 機能一覧に戻る
            </a>
        </div>
    </div>
</section>
@endsection
