<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- ヘッダーと年月選択 --}}
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            月次レポート
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $formatted['report_month'] }}の実績レポート
                        </p>
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
                <div class="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-white dark:bg-gray-700 rounded-full flex items-center justify-center shadow-md">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                AI教師からのコメント
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $formatted['ai_comment'] }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- サマリーカード --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 shadow-sm">
                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-2">通常タスク</p>
                    <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $formatted['summary']['normal_tasks']['count'] }}</p>
                    @if($formatted['summary']['normal_tasks']['change_percentage'] != 0)
                        <p class="mt-2 text-sm {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['normal_tasks']['change_percentage'] }}% (前月比)
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                    @endif
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6 shadow-sm">
                    <p class="text-sm text-purple-600 dark:text-purple-400 font-medium mb-2">グループタスク</p>
                    <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ $formatted['summary']['group_tasks']['count'] }}</p>
                    @if($formatted['summary']['group_tasks']['change_percentage'] != 0)
                        <p class="mt-2 text-sm {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['group_tasks']['change_percentage'] }}% (前月比)
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">前月比: 変化なし</p>
                    @endif
                </div>
                
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 shadow-sm">
                    <p class="text-sm text-amber-600 dark:text-amber-400 font-medium mb-2">獲得報酬</p>
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
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    過去6ヶ月の推移
                </h3>
                @if(!empty($trendData['datasets']))
                    <div class="h-80">
                        <canvas id="trend-chart"></canvas>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        グラフ表示に必要なデータがありません
                    </p>
                @endif
            </div>

            {{-- 明細テーブル（次のステップで実装） --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        タスク明細
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        明細テーブルは次のステップで実装予定
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript: 年月選択の動作 --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // デスクトップ: 年月プルダウン
            const yearSelect = document.getElementById('year-select');
            const monthSelect = document.getElementById('month-select');
            
            if (yearSelect && monthSelect) {
                const handleNavigation = () => {
                    const year = yearSelect.value;
                    const month = monthSelect.value.padStart(2, '0');
                    window.location.href = `{{ route('reports.monthly.show') }}/${year}/${month}`;
                };
                
                yearSelect.addEventListener('change', handleNavigation);
                monthSelect.addEventListener('change', handleNavigation);
            }
            
            // モバイル: input[type=month]
            const monthPicker = document.getElementById('month-picker');
            if (monthPicker) {
                monthPicker.addEventListener('change', function() {
                    const [year, month] = this.value.split('-');
                    window.location.href = `{{ route('reports.monthly.show') }}/${year}/${month}`;
                });
            }
            
            // Chart.js: トレンドグラフ
            @if(!empty($trendData['datasets']))
            const ctx = document.getElementById('trend-chart');
            if (ctx) {
                const trendData = @json($trendData);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: trendData.labels,
                        datasets: trendData.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151',
                                    font: {
                                        size: 12
                                    },
                                    boxWidth: 12,
                                    padding: 10
                                }
                            },
                            title: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + '件';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                                },
                                grid: {
                                    color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                                }
                            }
                        }
                    }
                });
            }
            @endif
        });
    </script>
    @endpush
</x-app-layout>
