@extends('layouts.portal')

@section('title', 'グループタスク管理')
@section('meta_description', 'MyTeacher グループタスク - 兄弟姉妹で楽しく競争。家族全員でタスク共有、自動スケジュール機能で継続的なお手伝い習慣を。')
@section('og_title', 'グループタスク管理 - MyTeacher')

@push('styles')
    @vite(['resources/css/portal-common.css', 'resources/css/portal-features.css'])
@endpush

@section('content')
<!-- 背景装飾 -->
<div class="portal-bg-gradient">
    <div class="portal-bg-blob portal-bg-blob-primary" style="top: 20%; left: 5%; width: 500px; height: 500px;"></div>
    <div class="portal-bg-blob portal-bg-blob-pink" style="bottom: 10%; right: 15%; width: 450px; height: 450px; animation-delay: 0.6s;"></div>
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
            <span class="text-gray-900 dark:text-white font-medium">グループタスク</span>
        </nav>
    </div>
</section>

<!-- Hero -->
<section class="portal-section">
    <div class="portal-container">
        <div class="text-center mb-12">
            <div class="feature-icon-wrapper inline-flex mb-6">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-12 h-12">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold gradient-text mb-6">
                グループタスク管理
            </h1>
            <p class="text-2xl sm:text-3xl font-bold gradient-text-warm max-w-3xl mx-auto">
                兄弟姉妹で楽しく競争、<br class="sm:hidden">家族みんなで成長
            </p>
        </div>

        <!-- メインビジュアル -->
        <div class="screenshot-wrapper max-w-4xl mx-auto mb-12">
            <img src="https://placehold.co/1200x800/f3f4f6/ec4899?text=%E3%82%B0%E3%83%AB%E3%83%BC%E3%83%97%E3%82%BF%E3%82%B9%E3%82%AF%E7%94%BB%E9%9D%A2" 
                 alt="グループタスク画面" 
                 class="screenshot-img">
        </div>
    </div>
</section>

<!-- グループタスクのメリット -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            グループタスクでできること
        </h2>

        <div class="portal-grid-3 max-w-5xl mx-auto">
            <div class="feature-showcase-card text-center">
                <div class="text-5xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="feature-title">複数人に同時割当</h3>
                <p class="feature-description">
                    「お皿洗い」を兄弟3人に一斉に割り当て。誰が先にやるか、楽しく競争。
                </p>
            </div>

            <div class="feature-showcase-card text-center">
                <div class="text-5xl mb-4">📅</div>
                <h3 class="feature-title">自動スケジュール</h3>
                <p class="feature-description">
                    「毎週月曜18:00にお風呂掃除」など、定期タスクを自動生成。
                </p>
            </div>

            <div class="feature-showcase-card text-center">
                <div class="text-5xl mb-4">✅</div>
                <h3 class="feature-title">承認フロー</h3>
                <p class="feature-description">
                    完了報告を親が確認・承認。写真添付で質も確認できます。
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 使い方 -->
<section class="portal-section">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            グループタスクの使い方
        </h2>

        <div class="portal-grid-3 max-w-5xl mx-auto">
            <div class="step-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="step-number">1</div>
                    <h3 class="step-title mb-0">タスク作成</h3>
                </div>
                <p class="step-description">
                    タスクを作成し、複数のメンバーを選択して一斉割当
                </p>
            </div>

            <div class="step-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="step-number">2</div>
                    <h3 class="step-title mb-0">子どもが実行</h3>
                </div>
                <p class="step-description">
                    各自のタスク一覧に表示。早い者勝ちで取り組む
                </p>
            </div>

            <div class="step-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="step-number">3</div>
                    <h3 class="step-title mb-0">完了・承認</h3>
                </div>
                <p class="step-description">
                    完了報告を親が確認。写真添付で質もチェック
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 活用例 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <h2 class="portal-section-title text-center gradient-text">
            活用例
        </h2>

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- 例1: 兄弟競争 -->
            <div class="portal-card p-6">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">🏃</div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">兄弟で競争</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-semibold text-[#59B9C6] mb-2">タスク例</p>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>お皿洗い（兄・弟）</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>ゴミ出し（兄・弟）</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>洗濯物たたみ（兄・弟）</span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-purple-600 dark:text-purple-400 mb-2">効果</p>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>早い者勝ちで自発的に</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>達成数を競い合う</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>責任感が育つ</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 例2: 当番制 -->
            <div class="portal-card p-6">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">🔄</div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">当番制で公平に</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-semibold text-[#59B9C6] mb-2">スケジュール例</p>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>月・水・金: お風呂掃除（長女）</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>火・木・土: お風呂掃除（長男）</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>日: お風呂掃除（次女）</span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-purple-600 dark:text-purple-400 mb-2">効果</p>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>自動生成で親の負担ゼロ</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>公平に役割分担</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>習慣化しやすい</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 例3: 家族イベント -->
            <div class="portal-card p-6">
                <div class="flex items-start gap-4">
                    <div class="text-4xl">🎄</div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">家族イベント準備</h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-semibold text-[#59B9C6] mb-2">タスク例（クリスマス）</p>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>飾り付け（全員）</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>ツリー組み立て（パパ・長男）</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>クッキー作り（ママ・長女）</span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-purple-600 dark:text-purple-400 mb-2">効果</p>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>全員参加で一体感</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>準備も楽しいイベントに</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span>✓</span>
                                        <span>協力する経験が得られる</span>
                                    </li>
                                </ul>
                            </div>
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
            利用者の声
        </h2>

        <div class="portal-grid-2 max-w-4xl mx-auto">
            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「兄弟3人に『お皿洗い』を一斉割当したら、誰が先にやるか競争になりました。今まで押し付け合っていたのに、むしろ取り合いに（笑）。グループタスクのおかげで家事が楽しいゲームになっています。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">佐</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">佐藤様</div>
                        <div class="testimonial-meta">埼玉県・3児のママ</div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-quote">
                    「スケジュール機能で『毎週月曜18:00にお風呂掃除』を自動設定。もう私が毎週言わなくても、子どもたちが自分でやってくれます。2ヶ月続いて完全に習慣化しました。」
                </p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">山</div>
                    <div class="testimonial-info">
                        <div class="testimonial-name">山田様</div>
                        <div class="testimonial-meta">千葉県・双子ママ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- プラン別機能比較 -->
<section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container max-w-4xl">
        <h2 class="portal-section-title text-center gradient-text">
            プラン別機能
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-4 text-gray-900 dark:text-white font-bold">機能</th>
                        <th class="py-4 px-4 text-center text-gray-900 dark:text-white font-bold">無料</th>
                        <th class="py-4 px-4 text-center text-gray-900 dark:text-white font-bold">ファミリー</th>
                        <th class="py-4 px-4 text-center text-gray-900 dark:text-white font-bold">エンタープライズ</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">グループタスク作成</td>
                        <td class="py-3 px-4 text-center text-green-600 dark:text-green-400 font-bold">月3個まで</td>
                        <td class="py-3 px-4 text-center text-green-600 dark:text-green-400 font-bold">無制限</td>
                        <td class="py-3 px-4 text-center text-green-600 dark:text-green-400 font-bold">無制限</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">自動スケジュール</td>
                        <td class="py-3 px-4 text-center text-red-600 dark:text-red-400">×</td>
                        <td class="py-3 px-4 text-center text-green-600 dark:text-green-400 font-bold">◯</td>
                        <td class="py-3 px-4 text-center text-green-600 dark:text-green-400 font-bold">◯</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">グループ数</td>
                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">-</td>
                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">1</td>
                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">5</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">メンバー数</td>
                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">-</td>
                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">6</td>
                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">20〜</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('portal.features.pricing') }}" class="portal-btn-primary">
                料金プランを詳しく見る →
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="portal-section">
    <div class="portal-container max-w-3xl">
        <div class="cta-section">
            <div class="cta-content">
                <h2 class="cta-title text-2xl sm:text-3xl">
                    グループタスクを<br class="sm:hidden">今すぐ体験
                </h2>
                <p class="cta-description">
                    無料プランでも月3個まで利用可能<br>
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
