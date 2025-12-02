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
                                アバターからのコメント
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
            @if(!empty($trendData['normal']['datasets']) || !empty($trendData['group']['datasets']))
            <div class="mb-6 space-y-6">
                {{-- 通常タスクグラフ --}}
                @if(!empty($trendData['normal']['datasets']))
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            📊 通常タスクの推移（過去6ヶ月）
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            メンバー別完了数
                        </span>
                    </div>
                    <div class="h-80">
                        <canvas id="normal-trend-chart"></canvas>
                    </div>
                </div>
                @endif
                
                {{-- グループタスクグラフ --}}
                @if(!empty($trendData['group']['datasets']))
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            👥 グループタスクの推移（過去6ヶ月）
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            メンバー別完了数
                        </span>
                    </div>
                    <div class="h-80">
                        <canvas id="group-trend-chart"></canvas>
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

            {{-- 明細テーブル --}}
            <x-reports.task-detail-table 
                :member-details="$formatted['member_details']"
                :group-task-summary="$formatted['group_task_summary']" 
            />
        </div>
    </div>

    {{-- JavaScript: 年月選択とグラフ --}}
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
            
            // Chart.js: トレンドグラフ（2つに分離）
            @if(!empty($trendData['normal']['datasets']) || !empty($trendData['group']['datasets']))
            const trendData = @json($trendData);
            
            console.log('Trend data loaded:', {
                normalDatasetCount: trendData.normal?.datasets?.length || 0,
                groupDatasetCount: trendData.group?.datasets?.length || 0,
                members: trendData.members
            });
            
            // 共通のChartオプション
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            boxWidth: 20,
                            boxHeight: 12,
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            generateLabels: function(chart) {
                                const datasets = chart.data.datasets;
                                return datasets.map((dataset, i) => ({
                                    text: dataset.label,
                                    fillStyle: dataset.backgroundColor,
                                    strokeStyle: dataset.borderColor,
                                    lineWidth: dataset.borderWidth,
                                    hidden: !chart.isDatasetVisible(i),
                                    index: i,
                                    pointStyle: 'rectRounded'
                                }));
                            }
                        },
                        maxHeight: 80,
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const chart = legend.chart;
                            const meta = chart.getDatasetMeta(index);
                            meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
                            chart.update();
                        }
                    },
                    title: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: document.documentElement.classList.contains('dark') ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
                        bodyColor: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151',
                        borderColor: document.documentElement.classList.contains('dark') ? '#4b5563' : '#d1d5db',
                        borderWidth: 1,
                        padding: 12,
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
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            };
            
            // 通常タスクグラフ
            const normalCtx = document.getElementById('normal-trend-chart');
            if (normalCtx && trendData.normal?.datasets?.length > 0) {
                new Chart(normalCtx, {
                    type: 'bar',
                    data: {
                        labels: trendData.normal.labels,
                        datasets: trendData.normal.datasets
                    },
                    options: commonOptions
                });
            }
            
            // グループタスクグラフ
            const groupCtx = document.getElementById('group-trend-chart');
            if (groupCtx && trendData.group?.datasets?.length > 0) {
                new Chart(groupCtx, {
                    type: 'bar',
                    data: {
                        labels: trendData.group.labels,
                        datasets: trendData.group.datasets
                    },
                    options: commonOptions
                });
            }
            @else
            console.warn('No trend data available', {
                trendDataExists: {{ !empty($trendData) ? 'true' : 'false' }}
            });
            @endif
        });
    </script>
    @endpush
</x-app-layout>
