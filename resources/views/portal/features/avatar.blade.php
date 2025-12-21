@extends('layouts.portal')

@section('title', 'AIアバター応援機能')
@section('meta_description', 'MyTeacher AIアバター - Stable Diffusion生成のオリジナル教師キャラクターが、タスク完了時に応援。楽しく続けられる秘訣。')
@section('og_title', 'AIアバター応援機能 - MyTeacher')

@push('styles')
    @vite(['resources/css/portal-common.css', 'resources/css/portal-features.css'])
@endpush

@section('content')
<!-- 背景装飾 -->
<div class="portal-bg-gradient">
    <div class="portal-bg-blob portal-bg-blob-purple" style="top: 10%; left: 15%; width: 450px; height: 450px;"></div>
    <div class="portal-bg-blob portal-bg-blob-pink" style="bottom: 15%; right: 10%; width: 400px; height: 400px; animation-delay: 0.5s;"></div>
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
            <span class="text-gray-900 dark:text-white font-medium">AIアバター</span>
        </nav>
    </div>
</section>

<!-- Hero -->
<section class="portal-section">
    <div class="portal-container">
        <div class="text-center mb-12">
            <div class="feature-icon-wrapper inline-flex mb-6">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-12 h-12">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold gradient-text mb-6">
                AIアバター応援機能
            </h1>
            <p class="text-2xl sm:text-3xl font-bold gradient-text-warm max-w-3xl mx-auto">
                作業じゃなくて、<br class="sm:hidden">ゲーム感覚で楽しい
            </p>
        </div>

        <!-- メインビジュアル -->
        <div class="screenshot-wrapper max-w-4xl mx-auto mb-12">
            <img src="{{ asset('images/portal/avatar-sample.gif') }}" 
                 alt="アバター応援画面" 
                 class="screenshot-img">
        </div>
    </div>
</section>

<!-- アバターの特徴 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            アバターができること
        </h2>

        <div class="portal-grid-3 max-w-5xl mx-auto">
            <div class="feature-showcase-card text-center">
                <div class="text-5xl mb-4">🎉</div>
                <h3 class="feature-title">タスク完了を祝福</h3>
                <p class="feature-description">
                    「すごいね！」「よくできました！」など、タスク完了時に励ましの言葉をかけてくれます。
                </p>
            </div>

            <div class="feature-showcase-card text-center">
                <div class="text-5xl mb-4">😊</div>
                <h3 class="feature-title">表情が変わる</h3>
                <p class="feature-description">
                    笑顔、驚き、喜びなど、状況に応じて8種類の表情とポーズで応援。
                </p>
            </div>

            <div class="feature-showcase-card text-center">
                <div class="text-5xl mb-4">✨</div>
                <h3 class="feature-title">オリジナルキャラ</h3>
                <p class="feature-description">
                    Stable Diffusion AIで生成されたあなただけの教師キャラクター。
                </p>
            </div>
        </div>
    </div>
</section>

<!-- アバター生成の流れ -->
<section class="portal-section">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            アバター作成は3ステップ
        </h2>

        <div class="portal-grid-3 max-w-5xl mx-auto">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">外見を選ぶ</h3>
                <p class="step-description">
                    髪型、服装、雰囲気など、好みの外見を選択
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">性格を設定</h3>
                <p class="step-description">
                    優しい、厳しい、ユーモラスなど、キャラクターの性格を選択
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">AI生成完了</h3>
                <p class="step-description">
                    約1分で8種類の画像（表情・ポーズ）が生成されます
                </p>
            </div>
        </div>
    </div>
</section>

<!-- アバターの応援例 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            こんな場面で応援
        </h2>

        <div class="space-y-6 max-w-4xl mx-auto">
            <div class="portal-card p-6">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">📝</div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">タスク完了時</h3>
                        <div class="bg-[#59B9C6]/10 p-4 rounded-lg border-l-4 border-[#59B9C6]">
                            <p class="text-gray-700 dark:text-gray-300 italic">
                                「お皿洗い、完璧だね！次はお風呂掃除も頑張ろう！」
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="portal-card p-6">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">🎯</div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">目標達成時</h3>
                        <div class="bg-purple-500/10 p-4 rounded-lg border-l-4 border-purple-500">
                            <p class="text-gray-700 dark:text-gray-300 italic">
                                「今週10タスク達成！素晴らしい！来週は12個チャレンジしてみようか？」
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="portal-card p-6">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">💪</div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">難しいタスク挑戦時</h3>
                        <div class="bg-pink-500/10 p-4 rounded-lg border-l-4 border-pink-500">
                            <p class="text-gray-700 dark:text-gray-300 italic">
                                「自由研究、大変だけど一緒に頑張ろう！まずは最初のステップから！」
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 子どもの声 -->
<section class="portal-section">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            子どもたちの声
        </h2>

        <div class="portal-grid-2 max-w-4xl mx-auto">
            <div class="glass-card p-6 rounded-2xl">
                <div class="flex items-start gap-3 mb-4">
                    <div class="text-3xl">😊</div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">太郎くん（小3）</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">東京都</p>
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300">
                    「先生が『すごいね！』って言ってくれるから、もっと頑張りたくなる！ゲームみたいで楽しい！」
                </p>
            </div>

            <div class="glass-card p-6 rounded-2xl">
                <div class="flex items-start gap-3 mb-4">
                    <div class="text-3xl">🌟</div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">花子ちゃん（小1）</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">神奈川県</p>
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300">
                    「かわいい先生が応援してくれるから、お手伝い楽しい！毎日やりたくなっちゃう！」
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 技術仕様 -->
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
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                        </svg>
                        AI技術
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>Stable Diffusion（画像生成AI）</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>高品質なイラスト生成</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>8種類の表情・ポーズ</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        カスタマイズ
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>外見・性格の選択可能</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>いつでも作り直せる</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#59B9C6]">•</span>
                            <span>全プラン利用可能</span>
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
                    AIアバターを<br class="sm:hidden">今すぐ作成
                </h2>
                <p class="cta-description">
                    無料プランでもアバター生成可能<br>
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
