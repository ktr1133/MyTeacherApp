{{-- filepath: /home/ktr/mtdev/resources/views/portal/features/auto-schedule.blade.php --}}
@extends('layouts.portal')

@section('title', '自動スケジュール機能 - 定期タスクを自動生成')
@section('meta_description', '毎週・毎月の定期タスクを自動生成。声かけ不要で習慣化をサポート。祝日対応やランダム割当機能も搭載。')

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
            <span class="text-gray-900 dark:text-white font-medium">自動スケジュール</span>
        </nav>
    </div>
</section>

<!-- Hero Section -->
<section class="portal-hero">
    <div class="max-w-5xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-full mb-6">
            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">有料プラン限定機能</span>
        </div>
        
        <h1 class="text-4xl sm:text-5xl font-bold gradient-text mb-6">
            「毎週言わなきゃ」から<br class="sm:hidden">解放される
        </h1>
        
        <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
            定期タスクを一度設定すれば、自動で生成・通知。<br>
            ママの声かけ不要で、子どもの習慣化をサポートします。
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
            自動化で変わる、家族の毎日
        </h2>
        
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Before -->
            <div class="problem-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-red-500/10 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">😩</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Before: 手動管理</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">毎週日曜日に「お風呂掃除ね」と声かけ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">祝日を忘れて「なんでやってないの！」</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">兄弟姉妹で「今週は誰の番？」と確認</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">月初に「今月のタスク作成」で30分</span>
                    </li>
                </ul>
            </div>
            
            <!-- After -->
            <div class="solution-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">😊</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">After: 自動スケジュール</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">毎週日曜日に自動でタスク作成＆通知</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">祝日は自動スキップ、手動調整不要</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">ランダム割当で自動的に公平に分配</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">一度設定すれば、親は何もしなくてOK</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Key Features -->
<section class="portal-section">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            自動スケジュールの3つの機能
        </h2>
        
        <div class="grid md:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="glass-card hover-float">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">繰り返しパターン設定</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    日次・週次・月次の繰り返しパターンを柔軟に設定。「毎週火曜日」「第1・第3日曜日」など細かい指定も可能。
                </p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• 毎日（平日のみ/全日）</li>
                    <li>• 毎週（曜日指定）</li>
                    <li>• 毎月（日付/第○曜日）</li>
                </ul>
            </div>
            
            <!-- Feature 2 -->
            <div class="glass-card hover-float">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">祝日自動対応</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    日本の祝日を自動認識してスキップ。「祝日も実施」「祝日は翌日に振替」など柔軟な設定が可能。
                </p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• 祝日を自動スキップ</li>
                    <li>• 祝日も実施する設定</li>
                    <li>• 翌営業日に自動振替</li>
                </ul>
            </div>
            
            <!-- Feature 3 -->
            <div class="glass-card hover-float">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-pink-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">ランダム割当</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    複数の子どもに公平に割り当て。「今週は誰の番？」の確認不要。履歴を考慮した自動割当。
                </p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• 完全ランダム割当</li>
                    <li>• 履歴考慮で公平性確保</li>
                    <li>• 固定メンバー除外可能</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- How to Use -->
<section class="portal-section bg-white dark:bg-gray-800/50">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">
            使い方は3ステップ
        </h2>
        
        <div class="space-y-6">
            <!-- Step 1 -->
            <div class="flex gap-6 items-start">
                <div class="w-12 h-12 bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                    1
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">スケジュール設定を作成</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-3">
                        「グループタスク管理」から「スケジュール設定」を選択。タスク名、繰り返しパターン、祝日対応を設定します。
                    </p>
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
                        <img src="{{ asset('images/portal/auto-schedule.gif') }}" alt="スケジュール設定画面" class="rounded-lg w-full" loading="lazy">
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="flex gap-6 items-start">
                <div class="w-12 h-12 bg-gradient-to-br from-[#8b5cf6] to-[#ec4899] rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                    2
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">割当メンバーを選択</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-3">
                        グループメンバーから割当対象を選択。ランダム割当を有効にすると、毎回自動的に公平に割り当てられます。
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <p class="font-semibold text-blue-900 dark:text-blue-100 mb-2">✅ 固定割当</p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">特定の子どもに毎回割当</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <p class="font-semibold text-green-900 dark:text-green-100 mb-2">🎲 ランダム割当</p>
                            <p class="text-sm text-green-700 dark:text-green-300">自動で公平に割り当て</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="flex gap-6 items-start">
                <div class="w-12 h-12 bg-gradient-to-br from-[#10b981] to-[#059669] rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                    3
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">あとは自動でタスク作成</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-3">
                        設定した日時になると自動でタスクが作成され、メンバーに通知。親は何もする必要がありません。
                    </p>
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <div>
                                <p class="font-semibold">自動通知で忘れない！</p>
                                <p class="text-sm opacity-90">毎週日曜日 9:00に「お風呂掃除」が割り当てられました</p>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <span class="text-3xl">🛁</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">週次のお手伝い</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    毎週日曜日9:00に「お風呂掃除」「ゴミ出し」などを自動作成。兄弟姉妹にランダム割当で公平に。
                </p>
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3 text-sm">
                    <p class="text-indigo-900 dark:text-indigo-100"><strong>設定例:</strong> 毎週日曜 9:00 → 太郎・花子にランダム割当 → 祝日スキップ</p>
                </div>
            </div>
            
            <!-- Use Case 2 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">📚</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">平日の習慣</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    平日毎日16:00に「宿題」「明日の準備」を自動作成。祝日は自動スキップで手間いらず。
                </p>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 text-sm">
                    <p class="text-green-900 dark:text-green-100"><strong>設定例:</strong> 平日毎日 16:00 → 太郎に固定割当 → 祝日は自動スキップ</p>
                </div>
            </div>
            
            <!-- Use Case 3 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🍽️</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">当番制の家事</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    毎日18:30に「食器洗い」を自動作成。ランダム割当で誰の番か自動決定、不公平感なし。
                </p>
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3 text-sm">
                    <p class="text-purple-900 dark:text-purple-100"><strong>設定例:</strong> 毎日 18:30 → 太郎・花子・ママにランダム → 履歴考慮で公平</p>
                </div>
            </div>
            
            <!-- Use Case 4 -->
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🌱</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">月次のイベント</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-3">
                    毎月1日に「お小遣い帳記入」、第1日曜に「お部屋の大掃除」など定期イベントも管理。
                </p>
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-3 text-sm">
                    <p class="text-orange-900 dark:text-orange-100"><strong>設定例:</strong> 毎月1日 9:00 → 太郎に固定 / 第1日曜 10:00 → 全員</p>
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
                    <img src="https://placehold.co/80x80/e5e7eb/64748b?text=M" alt="山田さん" class="w-16 h-16 rounded-full" loading="lazy">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="font-bold text-gray-900 dark:text-white">東京都・小3男子ママ</p>
                            <span class="px-2 py-1 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 text-xs rounded-full">ファミリープラン</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">
                            「毎週のお手伝い、声かけが本当にストレスでした。自動スケジュール機能を使い始めてから、
                            <strong class="text-gray-900 dark:text-white">私の負担がゼロに</strong>。
                            子どもも通知で自分で確認するようになり、自主性が育ちました！」
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
                    <img src="https://placehold.co/80x80/e5e7eb/64748b?text=T" alt="田中さん" class="w-16 h-16 rounded-full" loading="lazy">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="font-bold text-gray-900 dark:text-white">神奈川県・小1女子・小4男子ママ</p>
                            <span class="px-2 py-1 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 text-xs rounded-full">ファミリープラン</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">
                            「ランダム割当が神機能！『今週は誰の番？』の確認が不要になり、
                            <strong class="text-gray-900 dark:text-white">兄弟げんかが減りました</strong>。
                            アプリが決めてくれるから、子どもも納得して動いてくれます。」
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
            自動スケジュール機能の利用条件
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
                        <td>自動スケジュール</td>
                        <td><span class="text-red-500">✗</span></td>
                        <td><span class="text-green-500">✓</span></td>
                        <td><span class="text-green-500">✓</span></td>
                    </tr>
                    <tr>
                        <td>スケジュール数</td>
                        <td>-</td>
                        <td>無制限</td>
                        <td>無制限</td>
                    </tr>
                    <tr>
                        <td>ランダム割当</td>
                        <td><span class="text-red-500">✗</span></td>
                        <td><span class="text-green-500">✓</span></td>
                        <td><span class="text-green-500">✓</span></td>
                    </tr>
                    <tr>
                        <td>祝日対応</td>
                        <td><span class="text-red-500">✗</span></td>
                        <td><span class="text-green-500">✓</span></td>
                        <td><span class="text-green-500">✓</span></td>
                    </tr>
                    <tr>
                        <td>複数グループ対応</td>
                        <td><span class="text-red-500">✗</span></td>
                        <td>1グループ</td>
                        <td>最大5グループ</td>
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
            声かけストレスから<br class="sm:hidden">解放されませんか？
        </h2>
        <p class="text-xl opacity-90 mb-8">
            月額500円、1日約17円で家族の時間が増える。<br>
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
