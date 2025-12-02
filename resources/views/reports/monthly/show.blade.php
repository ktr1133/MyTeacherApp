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
                        <div class="w-12 h-12 bg-gradient-to-br from-[#59B9C6] to-blue-500 rounded-lg flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        
                        <div>
                            <h2 class="text-3xl font-bold gradient-text">
                                月次レポート
                            </h2>
                            <p class="mt-1 text-base text-gray-600 dark:text-gray-400">
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
                <div class="mb-6 glass-card bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-2xl p-6 shadow-lg hero-subtitle feature-card">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-white dark:bg-gray-700 rounded-full flex items-center justify-center shadow-md floating-icon">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold gradient-text mb-2">
                                アバターからのコメント
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $formatted['ai_comment'] }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- サマリーカード --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 hero-cta">
                <div class="glass-card bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 shadow-lg feature-card">
                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-2">📝 通常タスク</p>
                    <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $formatted['summary']['normal_tasks']['count'] }}</p>
                    @if($formatted['summary']['normal_tasks']['change_percentage'] != 0)
                        <p class="mt-2 text-sm {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['normal_tasks']['change_percentage'] }}% (前月比)
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                    @endif
                </div>
                
                <div class="glass-card bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-6 shadow-lg feature-card">
                    <p class="text-sm text-purple-600 dark:text-purple-400 font-medium mb-2">👥 グループタスク</p>
                    <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ $formatted['summary']['group_tasks']['count'] }}</p>
                    @if($formatted['summary']['group_tasks']['change_percentage'] != 0)
                        <p class="mt-2 text-sm {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['group_tasks']['change_percentage'] }}% (前月比)
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                    @endif
                </div>
                
                <div class="glass-card bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-6 shadow-lg feature-card">
                    <p class="text-sm text-amber-600 dark:text-amber-400 font-medium mb-2">💰 獲得報酬</p>
                    <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ number_format($formatted['summary']['rewards']['total']) }}</p>
                    @if($formatted['summary']['rewards']['change_percentage'] != 0)
                        <p class="mt-2 text-sm {{ $formatted['summary']['rewards']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $formatted['summary']['rewards']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['rewards']['change_percentage'] }}% (前月比)
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                    @endif
                </div>
            </div>

            {{-- グラフエリア --}}
            @if(!empty($trendData['total']['datasets']))
            <div class="mb-6 space-y-6">
                {{-- 合計タスクグラフ（メイン） --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            📈 タスク完了数の推移（過去6ヶ月）
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                            通常タスク + グループタスク
                        </span>
                    </div>
                    <div class="h-80">
                        <canvas id="total-trend-chart"></canvas>
                    </div>
                </div>
                
                {{-- 詳細グラフ（折りたたみ可能） --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <button id="toggle-detail-charts" 
                            class="w-full px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                📊 タスク種別ごとの詳細推移
                            </h4>
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
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            💰 報酬獲得の推移（過去6ヶ月）
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400 sm:whitespace-nowrap">
                            グループタスク報酬
                        </span>
                    </div>
                    <div class="h-80">
                        <canvas id="reward-trend-chart"></canvas>
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    過去6ヶ月の推移
                </h3>
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    グラフ表示に必要なデータがありません
                </p>
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
                    <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="member-summary-result-title">
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
                        <div class="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-white dark:bg-gray-700 rounded-full flex items-center justify-center shadow-md">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-2">アバターからのコメント</h4>
                                    <p id="member-summary-comment" class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words leading-relaxed"></p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- グラフエリア --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- タスク傾向（円グラフ） --}}
                            <div class="glass-card p-6">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">タスク傾向</h4>
                                <div class="h-64">
                                    <canvas id="member-task-classification-chart"></canvas>
                                </div>
                            </div>
                            
                            {{-- 報酬推移（折れ線グラフ） --}}
                            <div class="glass-card p-6">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">報酬推移（過去6ヶ月）</h4>
                                <div class="h-64">
                                    <canvas id="member-reward-trend-chart"></canvas>
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
                generateBtn.dataset.groupId = '{{ $group->id }}';
                generateBtn.dataset.yearMonth = '{{ sprintf("%s-%s", $year, $month) }}';
                generateBtn.dataset.apiUrl = '{{ route('reports.monthly.member-summary') }}';
            }
        });
    </script>
</x-app-layout>
