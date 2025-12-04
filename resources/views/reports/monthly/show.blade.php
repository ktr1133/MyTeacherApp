<x-app-layout>
    @push('styles')
        @vite(['resources/css/reports/performance.css'])
    @endpush

    <div class="pb-12 pt-5 bg-gradient-to-br from-[#F3F3F2] via-white to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen relative">
        {{-- 背景の装飾円 --}}
        <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl floating-icon pointer-events-none"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl floating-icon pointer-events-none" style="animation-delay: 1.5s;"></div>
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 relative z-10">
            {{-- ヘッダーと年月選択 --}}
            <div class="mb-6 hero-title">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        {{-- アイコン --}}
                        <div class="w-10 h-10 bg-gradient-to-br from-[#59B9C6] to-blue-500 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        
                        <div>
                            <h2 class="text-lg font-bold bg-gradient-to-r from-[#59B9C6] to-blue-500 bg-clip-text text-transparent">
                                月次レポート
                            </h2>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $formatted['report_month'] }}の実績レポート
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        {{-- デスクトップ: 年月プルダウン --}}
                        <div class="hidden md:flex items-center gap-2">
                            <select id="year-select" 
                                    class="block rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($availableMonths->unique('year')->sortByDesc('year') as $item)
                                    <option value="{{ $item['year'] }}" {{ $item['year'] == $year ? 'selected' : '' }}>
                                        {{ $item['year'] }}年
                                    </option>
                                @endforeach
                            </select>
                            
                            <select id="month-select" 
                                    class="block rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($availableMonths->where('year', $year) as $item)
                                    <option value="{{ $item['month'] }}" 
                                            data-accessible="{{ $item['is_accessible'] ? '1' : '0' }}"
                                            {{ $item['month'] == $month ? 'selected' : '' }}
                                            {{ !$item['is_accessible'] ? 'disabled' : '' }}>
                                        {{ $item['month'] }}月
                                        @if(!$item['is_accessible'])
                                            🔒
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- モバイル: input[type=month] --}}
                        <div class="md:hidden">
                            <input type="month" 
                                   id="month-picker"
                                   value="{{ sprintf('%s-%s', $year, $month) }}"
                                   min="{{ $availableMonths->last()['year_month'] ?? '' }}"
                                   max="{{ $availableMonths->first()['year_month'] ?? '' }}"
                                   class="block rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <a href="{{ route('reports.performance') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="hidden sm:inline">戻る</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- AI教師コメント --}}
            @if(!empty($formatted['ai_comment']))
                <div class="mb-6 bento-card rounded-2xl shadow-lg overflow-hidden hero-subtitle">
                    <div class="px-6 py-4 border-b border-purple-500/20 dark:border-purple-500/30 bg-gradient-to-r from-purple-500/5 to-pink-50/50 dark:from-purple-500/10 dark:to-pink-900/10">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                アバターからのコメント
                            </h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $formatted['ai_comment'] }}</p>
                    </div>
                </div>
            @endif

            {{-- サマリーカード --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 hero-cta">
                <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-cyan-50/50 dark:from-blue-500/10 dark:to-cyan-900/10">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center shadow">
                                <span class="text-xs">📝</span>
                            </div>
                            <p class="text-sm font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">通常タスク</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $formatted['summary']['normal_tasks']['count'] }}</p>
                        @if($formatted['summary']['normal_tasks']['change_percentage'] != 0)
                            <p class="mt-2 text-sm {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['normal_tasks']['change_percentage'] }}% (前月比)
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                        @endif
                    </div>
                </div>
                
                <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-purple-500/20 dark:border-purple-500/30 bg-gradient-to-r from-purple-500/5 to-pink-50/50 dark:from-purple-500/10 dark:to-pink-900/10">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow">
                                <span class="text-xs">👥</span>
                            </div>
                            <p class="text-sm font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">グループタスク</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $formatted['summary']['group_tasks']['count'] }}</p>
                        @if($formatted['summary']['group_tasks']['change_percentage'] != 0)
                            <p class="mt-2 text-sm {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['group_tasks']['change_percentage'] }}% (前月比)
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                        @endif
                    </div>
                </div>
                
                <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-amber-500/20 dark:border-amber-500/30 bg-gradient-to-r from-amber-500/5 to-yellow-50/50 dark:from-amber-500/10 dark:to-yellow-900/10">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-amber-600 to-yellow-600 flex items-center justify-center shadow">
                                <span class="text-xs">💰</span>
                            </div>
                            <p class="text-sm font-bold bg-gradient-to-r from-amber-600 to-yellow-600 bg-clip-text text-transparent">獲得報酬</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($formatted['summary']['rewards']['total']) }}</p>
                        @if($formatted['summary']['rewards']['change_percentage'] != 0)
                            <p class="mt-2 text-sm {{ $formatted['summary']['rewards']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $formatted['summary']['rewards']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['rewards']['change_percentage'] }}% (前月比)
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- グラフエリア --}}
            @if(!empty($trendData['total']['datasets']))
            <div class="mb-6 space-y-6">
                {{-- 合計タスクグラフ（メイン） --}}
                <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-teal-500/20 dark:border-teal-500/30 bg-gradient-to-r from-teal-500/5 to-emerald-50/50 dark:from-teal-500/10 dark:to-emerald-900/10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-600 to-emerald-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold bg-gradient-to-r from-teal-600 to-emerald-600 bg-clip-text text-transparent">
                                    タスク完了数の推移（過去6ヶ月）
                                </h3>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                                通常タスク + グループタスク
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="h-80">
                            <canvas id="total-trend-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                {{-- 詳細グラフ（折りたたみ可能） --}}
                <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                    <button id="toggle-detail-charts" 
                            class="w-full px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    タスク種別ごとの詳細推移
                                </h4>
                            </div>
                            <svg id="toggle-icon" 
                                 class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>
                    
                    <div id="detail-charts" 
                         class="px-6 pb-6 space-y-6 overflow-hidden transition-all duration-200 ease-out"
                         style="max-height: 0; opacity: 0;">
                        {{-- 通常タスクグラフ --}}
                        @if(!empty($trendData['normal']['datasets']))
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-4">
                                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    通常タスク
                                </h5>
                            </div>
                            <div class="h-64">
                                <canvas id="normal-trend-chart"></canvas>
                            </div>
                        </div>
                        @endif
                        
                        {{-- グループタスクグラフ --}}
                        @if(!empty($trendData['group']['datasets']))
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    グループタスク
                                </h5>
                            </div>
                            <div class="h-64">
                                <canvas id="group-trend-chart"></canvas>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- 報酬獲得の推移グラフ --}}
                @if(!empty($trendData['reward']['datasets']))
                <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-emerald-500/20 dark:border-emerald-500/30 bg-gradient-to-r from-emerald-500/5 to-green-50/50 dark:from-emerald-500/10 dark:to-green-900/10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-600 to-green-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
                                    報酬獲得の推移（過去6ヶ月）
                                </h3>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                                グループタスク報酬
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="h-80">
                            <canvas id="reward-trend-chart"></canvas>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="mb-6 bento-card rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-500/20 dark:border-gray-500/30 bg-gradient-to-r from-gray-500/5 to-slate-50/50 dark:from-gray-500/10 dark:to-slate-900/10">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-600 to-slate-600 flex items-center justify-center shadow">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold bg-gradient-to-r from-gray-600 to-slate-600 bg-clip-text text-transparent">
                            過去6ヶ月の推移
                        </h3>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        グラフ表示に必要なデータがありません
                    </p>
                </div>
            </div>
            @endif
            
            {{-- グラフデータ（JavaScriptから参照） --}}
            @if(!empty($trendData['total']['datasets']))
            <script type="application/json" id="trend-data">
                @json($trendData)
            </script>
            @endif

            {{-- 明細テーブル --}}
            <x-reports.task-detail-table 
                :member-details="$formatted['member_details']"
                :group-task-summary="$formatted['group_task_summary']" 
            />
        </div>
    </div>

    {{-- メンバー別概況レポート生成中モーダル --}}
    <div id="member-summary-generating-modal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl w-full max-w-md p-8">
                    <div class="text-center">
                        <div class="mx-auto mb-4 w-16 h-16 border-4 border-[#59B9C6] border-t-transparent rounded-full animate-spin"></div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">概況レポート生成中</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">AIがメンバーの活動を分析しています...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- メンバー別概況レポート結果表示モーダル --}}
    <div id="member-summary-result-modal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" id="member-summary-result-overlay"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl w-full max-w-4xl">
                    {{-- ヘッダー --}}
                    <div class="px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="member-summary-result-title">
                                    メンバー別概況レポート
                                </h3>
                            </div>
                            <button type="button" id="member-summary-result-close-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    {{-- コンテンツ --}}
                    <div class="px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                        {{-- AIコメント --}}
                        <div class="mb-6 bento-card rounded-2xl shadow-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-purple-500/20 dark:border-purple-500/30 bg-gradient-to-r from-purple-500/5 to-pink-50/50 dark:from-purple-500/10 dark:to-pink-900/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">アバターからのコメント</h4>
                                </div>
                            </div>
                            <div class="p-6">
                                <p id="member-summary-comment" class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words leading-relaxed"></p>
                            </div>
                        </div>
                        
                        {{-- グラフエリア --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- タスク傾向（円グラフ） --}}
                            <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                                <div class="px-6 py-4 border-b border-teal-500/20 dark:border-teal-500/30 bg-gradient-to-r from-teal-500/5 to-cyan-50/50 dark:from-teal-500/10 dark:to-cyan-900/10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-teal-600 to-cyan-600 flex items-center justify-center shadow">
                                            <span class="text-xs">📊</span>
                                        </div>
                                        <h4 class="text-sm font-bold bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">タスク傾向</h4>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="h-64">
                                        <canvas id="member-task-classification-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- 報酬推移（折れ線グラフ） --}}
                            <div class="bento-card rounded-2xl shadow-lg overflow-hidden">
                                <div class="px-6 py-4 border-b border-emerald-500/20 dark:border-emerald-500/30 bg-gradient-to-r from-emerald-500/5 to-green-50/50 dark:from-emerald-500/10 dark:to-green-900/10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-emerald-600 to-green-600 flex items-center justify-center shadow">
                                            <span class="text-xs">💰</span>
                                        </div>
                                        <h4 class="text-sm font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">報酬推移（過去6ヶ月）</h4>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="h-64">
                                        <canvas id="member-reward-trend-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- トークン消費情報 --}}
                        <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            消費トークン: <span id="member-summary-tokens-used" class="font-semibold">0</span>
                        </div>
                        
                        {{-- 隠しフィールド（PDF生成用） --}}
                        <input type="hidden" id="member-summary-result-user-id">
                        <input type="hidden" id="member-summary-result-year-month">
                        <textarea id="member-summary-result-comment" class="hidden"></textarea>
                    </div>
                    
                    {{-- フッター --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" id="download-member-summary-pdf-btn" class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 transition shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            PDFダウンロード
                        </button>
                        <button type="button" id="member-summary-result-close-btn-2" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA5B2] hover:to-purple-700 transition shadow-lg">
                            閉じる
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript: 年月選択とグラフ --}}
    @vite(['resources/js/reports/monthly-report.js'])
    
    {{-- ルートURLをdata属性で渡す --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ルートURL設定
            const yearSelect = document.getElementById('year-select');
            const monthPicker = document.getElementById('month-picker');
            const routeBase = '{{ route('reports.monthly.show') }}'.replace(/\/\d{4}\/\d{2}$/, '');
            
            if (yearSelect) yearSelect.dataset.routeBase = routeBase;
            if (monthPicker) monthPicker.dataset.routeBase = routeBase;
            
            // メンバー別概況レポート生成用のデータを設定
            const generateBtn = document.getElementById('generate-member-summary-btn');
            if (generateBtn) {
                @if(isset($group) && $group)
                    generateBtn.dataset.groupId = '{{ $group->id }}';
                    generateBtn.dataset.yearMonth = '{{ sprintf("%s-%s", $year, $month) }}';
                    generateBtn.dataset.apiUrl = '{{ route('reports.monthly.member-summary') }}';
                @else
                    console.error('グループ情報が取得できません');
                    generateBtn.disabled = true;
                @endif
            }
        });
    </script>
</x-app-layout>
