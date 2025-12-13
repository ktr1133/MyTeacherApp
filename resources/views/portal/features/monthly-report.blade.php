{{-- filepath: /home/ktr/mtdev/resources/views/portal/features/monthly-report.blade.php --}}
@extends('layouts.portal')

@section('title', '月次レポート機能 - 成長を見える化')
@section('meta_description', '子どもの成長を月次レポートで見える化。タスク完了数やトークン獲得数をグラフ表示。PDF出力で記録を残せます。')

@push('styles')
@vite(['resources/css/portal-common.css', 'resources/css/portal-features.css'])
@endpush

@section('content')
<!-- Breadcrumb -->
<section class="px-4 sm:px-6 lg:px-8 py-4 bg-gray-50 dark:bg-gray-900/50">
    <div class="max-w-5xl mx-auto">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('portal.home') }}" class="text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] transition">ホーム</a>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('portal.features.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] transition">機能紹介</a>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white font-medium">月次レポート</span>
        </nav>
    </div>
</section>

<!-- Hero Section -->
<section class="portal-hero">
    <div class="max-w-5xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full mb-6">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">有料プラン限定機能</span>
        </div>
        
        <h1 class="text-4xl sm:text-5xl font-bold gradient-text mb-6">
            成長が「見える」から、<br class="sm:hidden">もっと頑張れる
        </h1>
        
        <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
            1ヶ月の頑張りをグラフで可視化。<br>
            子どもの自信につながり、親は成長を実感できます。
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="portal-btn-primary">
                無料で試してみる
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
            <a href="{{ route('portal.features.pricing') }}" class="portal-btn-secondary">
                プランを見る
            </a>
        </div>
    </div>
</section>

<!-- Before/After Comparison -->
<section class="portal-section bg-white dark:bg-gray-800/50">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            「見える化」で変わる、親子の会話
        </h2>
        
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Before -->
            <div class="problem-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-red-500/10 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">😔</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Before: 感覚で褒める</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">「今月は頑張ったね…？」（記憶が曖昧）</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">子ども「先月より頑張ったのに…」（不満）</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">進学面談で具体的なデータを出せない</span>
                    </li>
                </ul>
            </div>
            
            <!-- After -->
            <div class="solution-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">😊</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">After: データで褒める</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">「先月15回→今月23回！すごいね！」（データ根拠）</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">子ども「来月は30回目指す！」（自己目標）</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">面談でPDF提出「自主性が育っています」と評価</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Report Preview -->
<section class="portal-section">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            レポートで分かる5つの指標
        </h2>
        
        <div class="glass-card-strong mb-8">
            <img src="https://placehold.co/1200x600/e5e7eb/64748b?text=月次レポート全体イメージ（仮）" alt="月次レポート画面" class="rounded-lg w-full mb-6" loading="lazy">
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Metric 1 -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="font-bold text-blue-900 dark:text-blue-100">タスク完了数</h3>
                    </div>
                    <p class="text-sm text-blue-700 dark:text-blue-300">月間の完了タスク数を折れ線グラフで表示</p>
                </div>
                
                <!-- Metric 2 -->
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <h3 class="font-bold text-green-900 dark:text-green-100">完了率推移</h3>
                    </div>
                    <p class="text-sm text-green-700 dark:text-green-300">割当タスクのうち何%完了したか</p>
                </div>
                
                <!-- Metric 3 -->
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        <h3 class="font-bold text-purple-900 dark:text-purple-100">カテゴリ別分析</h3>
                    </div>
                    <p class="text-sm text-purple-700 dark:text-purple-300">勉強・家事など種類ごとの完了数</p>
                </div>
                
                <!-- Metric 4 -->
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="font-bold text-orange-900 dark:text-orange-100">平均完了時間</h3>
                    </div>
                    <p class="text-sm text-orange-700 dark:text-orange-300">タスクごとの平均所要時間を分析</p>
                </div>
                
                <!-- Metric 5 -->
                <div class="bg-pink-50 dark:bg-pink-900/20 border border-pink-200 dark:border-pink-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="font-bold text-pink-900 dark:text-pink-100">メンバー別比較</h3>
                    </div>
                    <p class="text-sm text-pink-700 dark:text-pink-300">兄弟姉妹の頑張りを並べて表示</p>
                </div>
                
                <!-- Metric 6 -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="font-bold text-indigo-900 dark:text-indigo-100">トークン獲得推移</h3>
                    </div>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300">AI機能利用やタスク報酬の履歴</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Key Features -->
<section class="portal-section bg-white dark:bg-gray-800/50">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            月次レポートの3つの特徴
        </h2>
        
        <div class="grid md:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="glass-card hover-float">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">視覚的なグラフ表示</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    Chart.jsによる美しいグラフで、数字が苦手な子どもでも一目で成長を実感できます。
                </p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• 折れ線グラフ（完了数推移）</li>
                    <li>• 棒グラフ（カテゴリ別）</li>
                    <li>• 円グラフ（達成率）</li>
                </ul>
            </div>
            
            <!-- Feature 2 -->
            <div class="glass-card hover-float">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">PDF出力機能</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    レポートをPDFでダウンロード。進学面談や家族会議での資料として活用できます。
                </p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• ワンクリックでPDF生成</li>
                    <li>• グラフ・表を含む完全版</li>
                    <li>• 印刷・メール送信も簡単</li>
                </ul>
            </div>
            
            <!-- Feature 3 -->
            <div class="glass-card hover-float">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-pink-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">過去6ヶ月分の履歴</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    半年分のレポートを振り返り可能。長期的な成長トレンドを確認できます。
                </p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• 月別レポート一覧</li>
                    <li>• 長期トレンド分析</li>
                    <li>• 成長の振り返りに最適</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases -->
<section class="portal-section">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            こんな使い方ができます
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Use Case 1 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🎓</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">進学面談での活用</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    「家庭での自主性」をデータで証明。先生に成長の様子を具体的に説明できます。
                </p>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-sm">
                    <p class="text-blue-900 dark:text-blue-100">
                        「4月から12月で完了率が65%→92%に向上しました」<br>
                        → 先生「自主性が育っていますね」
                    </p>
                </div>
            </div>
            
            <!-- Use Case 2 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">👨‍👩‍👧‍👦</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">家族会議での振り返り</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    月末に家族でレポートを見ながら「今月頑張ったこと」「来月の目標」を話し合い。
                </p>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 text-sm">
                    <p class="text-green-900 dark:text-green-100">
                        「お風呂掃除、毎週やったね！来月は夕飯の手伝いも増やしてみようか」<br>
                        → 子ども「うん、やる！」
                    </p>
                </div>
            </div>
            
            <!-- Use Case 3 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🎁</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">お小遣い額の根拠</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    「今月は20タスク完了したから、お小遣いは2,000円ね」と公平に決められます。
                </p>
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3 text-sm">
                    <p class="text-purple-900 dark:text-purple-100">
                        「完了数×100円ルール」で透明性確保<br>
                        → 子どもも納得、「来月は25タスク目指す！」
                    </p>
                </div>
            </div>
            
            <!-- Use Case 4 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">📖</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">成長記録として保存</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    PDFを保存して、子どもが大きくなったときに一緒に振り返る「成長アルバム」に。
                </p>
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-3 text-sm">
                    <p class="text-orange-900 dark:text-orange-100">
                        「小学1年生のとき、月5タスクしかできなかったのに、6年生で月50タスク！」<br>
                        → 子ども「こんなに成長したんだ！」
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customer Testimonials -->
<section class="portal-section bg-white dark:bg-gray-800/50">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            利用者の声
        </h2>
        
        <div class="space-y-6">
            <!-- Testimonial 1 -->
            <div class="testimonial-card">
                <div class="flex items-start gap-4">
                    <img src="https://placehold.co/80x80/e5e7eb/64748b?text=S" alt="佐藤さん" class="w-16 h-16 rounded-full" loading="lazy">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="font-bold text-gray-900 dark:text-white">東京都・小6女子ママ</p>
                            <span class="px-2 py-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs rounded-full">ファミリープラン</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">
                            「進学面談でPDFレポートを先生に見せたら、
                            <strong class="text-gray-900 dark:text-white">『自主性が育っていますね』と高評価</strong>をいただきました！
                            中学受験の内申点にもプラスになったと思います。データで成長を証明できるって、こんなに心強いとは思いませんでした。」
                        </p>
                        <div class="flex gap-1">
                            @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 2 -->
            <div class="testimonial-card">
                <div class="flex items-start gap-4">
                    <img src="https://placehold.co/80x80/e5e7eb/64748b?text=K" alt="木村さん" class="w-16 h-16 rounded-full" loading="lazy">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="font-bold text-gray-900 dark:text-white">大阪府・小2男子ママ</p>
                            <span class="px-2 py-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs rounded-full">ファミリープラン</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">
                            「グラフを見せたら、息子が
                            <strong class="text-gray-900 dark:text-white">『来月は30タスクにする！』と自分で目標設定</strong>するように。
                            親が言わなくても、自分で頑張りたくなる仕組みがすごいです。数字で『見える』って、こんなにモチベーションに繋がるんですね。」
                        </p>
                        <div class="flex gap-1">
                            @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Plan Comparison -->
<section class="portal-section">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-8">
            月次レポート機能の利用条件
        </h2>
        
        <div class="comparison-table-container">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>機能</th>
                        <th>無料プラン</th>
                        <th>ファミリープラン</th>
                        <th>エンタープライズ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>月額料金</td>
                        <td>¥0</td>
                        <td><strong class="text-[#59B9C6]">¥500</strong></td>
                        <td>¥3,000</td>
                    </tr>
                    <tr>
                        <td>月次レポート</td>
                        <td>初月のみ</td>
                        <td><span class="text-green-500">✓ 無制限</span></td>
                        <td><span class="text-green-500">✓ 無制限</span></td>
                    </tr>
                    <tr>
                        <td>PDF出力</td>
                        <td><span class="text-red-500">✗</span></td>
                        <td><span class="text-green-500">✓</span></td>
                        <td><span class="text-green-500">✓</span></td>
                    </tr>
                    <tr>
                        <td>過去履歴表示</td>
                        <td>当月のみ</td>
                        <td>過去6ヶ月</td>
                        <td>過去6ヶ月</td>
                    </tr>
                    <tr>
                        <td>メンバー別比較</td>
                        <td><span class="text-red-500">✗</span></td>
                        <td><span class="text-green-500">✓</span></td>
                        <td><span class="text-green-500">✓</span></td>
                    </tr>
                    <tr>
                        <td>グラフ種類</td>
                        <td>基本のみ</td>
                        <td>全種類</td>
                        <td>全種類</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-8">
            <a href="{{ route('portal.features.pricing') }}" class="portal-btn-primary inline-flex">
                プラン詳細を見る
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="max-w-4xl mx-auto text-center text-white">
        <h2 class="text-3xl sm:text-4xl font-bold mb-4">
            成長を「見える化」して、<br class="sm:hidden">もっと楽しく育てよう
        </h2>
        <p class="text-xl opacity-90 mb-8">
            月額500円、1日約17円で子どもの成長を実感できる。<br>
            14日間無料でお試しいただけます。
        </p>
        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-white dark:bg-gray-800 text-[#59B9C6] rounded-lg hover:shadow-2xl transition font-bold text-lg">
            無料で14日間試してみる
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
        <p class="text-sm opacity-75 mt-4">クレジットカード登録不要 • いつでもキャンセル可能</p>
    </div>
</section>
@endsection
