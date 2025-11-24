/**
 * ウェルカムページ - 実績グラフ表示
 * 
 * Chart.jsを使用してグループタスクの実績グラフ（週間・月間・年間）を表示します。
 * 完了/未完了の棒グラフ、累積完了と報酬累計の折れ線グラフを表示します。
 */

let performanceChart = null;

// サンプルデータ（グループタスク用）
const chartData = {
    weekly: {
        labels: ['月', '火', '水', '木', '金', '土', '日'],
        completed: [3, 5, 4, 6, 5, 2, 4],
        incomplete: [2, 1, 2, 1, 2, 3, 1],
        cumulative: [3, 8, 12, 18, 23, 25, 29],
        reward: [150, 400, 600, 900, 1150, 1250, 1450],
        stats: { completed: 29, tokens: 1450, rate: 87 }
    },
    monthly: {
        labels: ['第1週', '第2週', '第3週', '第4週'],
        completed: [18, 22, 20, 19],
        incomplete: [7, 5, 6, 8],
        cumulative: [18, 40, 60, 79],
        reward: [900, 2000, 3000, 3950],
        stats: { completed: 79, tokens: 3950, rate: 83 }
    },
    yearly: {
        labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        completed: [65, 72, 68, 75, 70, 78, 82, 80, 85, 90, 88, 92],
        incomplete: [15, 12, 14, 10, 12, 8, 6, 8, 5, 4, 6, 3],
        cumulative: [65, 137, 205, 280, 350, 428, 510, 590, 675, 765, 853, 945],
        reward: [3250, 6850, 10250, 14000, 17500, 21400, 25500, 29500, 33750, 38250, 42650, 47250],
        stats: { completed: 945, tokens: 47250, rate: 89 }
    }
};

/**
 * グラフ表示切り替え
 * 
 * @param {string} period - 表示期間 ('weekly', 'monthly', 'yearly')
 */
window.showChart = function(period) {
    // タブのアクティブ状態を更新
    document.querySelectorAll('.chart-tab').forEach(tab => {
        tab.classList.remove('active', 'text-[#59B9C6]', 'border-b-2', 'border-[#59B9C6]');
        tab.classList.add('text-gray-500', 'dark:text-gray-400');
    });
    const activeTab = document.getElementById(`tab-${period}`);
    activeTab.classList.add('active', 'text-[#59B9C6]', 'border-b-2', 'border-[#59B9C6]');
    activeTab.classList.remove('text-gray-500', 'dark:text-gray-400');

    // 統計を更新
    const stats = chartData[period].stats;
    document.getElementById('stat-completed').textContent = stats.completed;
    document.getElementById('stat-tokens').textContent = stats.tokens.toLocaleString();
    document.getElementById('stat-rate').textContent = `${stats.rate}%`;

    // グラフを更新
    const data = chartData[period];
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#d1d5db' : '#4b5563';
    const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

    if (performanceChart) {
        performanceChart.destroy();
    }

    const ctx = document.getElementById('performanceChart').getContext('2d');
    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    type: 'bar',
                    label: '完了',
                    data: data.completed,
                    backgroundColor: 'rgba(147, 51, 234, 0.8)',
                    borderColor: 'rgb(147, 51, 234)',
                    borderWidth: 2,
                    borderRadius: 8,
                    yAxisID: 'y',
                    order: 2
                },
                {
                    type: 'bar',
                    label: '未完了',
                    data: data.incomplete,
                    backgroundColor: 'rgba(236, 72, 153, 0.8)',
                    borderColor: 'rgb(236, 72, 153)',
                    borderWidth: 2,
                    borderRadius: 8,
                    yAxisID: 'y',
                    order: 3
                },
                {
                    type: 'line',
                    label: '累積完了',
                    data: data.cumulative,
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(168, 85, 247)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y',
                    order: 1
                },
                {
                    type: 'line',
                    label: '報酬累計',
                    data: data.reward,
                    borderColor: 'rgb(243, 156, 18)',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(243, 156, 18)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y-reward',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: textColor,
                        font: { size: 14, weight: 'bold' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 12,
                        boxHeight: 12
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 3) {
                                label += context.parsed.y.toLocaleString() + ' pt';
                            } else {
                                label += context.parsed.y + ' 件';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'タスク数',
                        color: textColor,
                        font: { size: 12, weight: 'bold' }
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                        color: textColor,
                        callback: function(value) {
                            return value + ' 件';
                        }
                    },
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    }
                },
                'y-reward': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '報酬',
                        color: 'rgb(243, 156, 18)',
                        font: { size: 12, weight: 'bold' }
                    },
                    ticks: {
                        color: 'rgb(243, 156, 18)',
                        callback: function(value) {
                            return value.toLocaleString() + ' pt';
                        }
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                },
                x: {
                    ticks: {
                        color: textColor,
                        font: { size: 13, weight: '600' }
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        }
    });
};

// 初期表示
document.addEventListener('DOMContentLoaded', function() {
    window.showChart('weekly');
});
