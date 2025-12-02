/**
 * 月次レポート詳細ページのJavaScript
 * 
 * 責務:
 * - 年月選択による画面遷移制御
 * - Chart.jsによるグラフ描画（合計、報酬、詳細）
 * - 詳細グラフの折りたたみ制御とアニメーション
 * - レスポンシブ対応（デスクトップ/モバイル）
 */

document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // 1. 年月選択による画面遷移
    // ========================================
    
    // デスクトップ: 年月プルダウン
    const yearSelect = document.getElementById('year-select');
    const monthSelect = document.getElementById('month-select');
    
    if (yearSelect && monthSelect) {
        const handleNavigation = () => {
            const year = yearSelect.value;
            const month = monthSelect.value.padStart(2, '0');
            const baseUrl = yearSelect.dataset.routeBase || '/reports/monthly';
            window.location.href = `${baseUrl}/${year}/${month}`;
        };
        
        yearSelect.addEventListener('change', handleNavigation);
        monthSelect.addEventListener('change', handleNavigation);
    }
    
    // モバイル: input[type=month]
    const monthPicker = document.getElementById('month-picker');
    if (monthPicker) {
        monthPicker.addEventListener('change', function() {
            const [year, month] = this.value.split('-');
            const baseUrl = this.dataset.routeBase || '/reports/monthly';
            window.location.href = `${baseUrl}/${year}/${month}`;
        });
    }
    
    // ========================================
    // 2. Chart.js グラフ描画
    // ========================================
    
    // データ取得（Bladeから渡される）
    const trendDataElement = document.getElementById('trend-data');
    if (!trendDataElement) {
        console.warn('Trend data element not found');
        return;
    }
    
    const trendData = JSON.parse(trendDataElement.textContent);
    
    if (!trendData || !trendData.total?.datasets?.length) {
        console.warn('No trend data available');
        return;
    }
    
    console.log('Trend data loaded:', {
        totalDatasetCount: trendData.total?.datasets?.length || 0,
        normalDatasetCount: trendData.normal?.datasets?.length || 0,
        groupDatasetCount: trendData.group?.datasets?.length || 0,
        rewardDatasetCount: trendData.reward?.datasets?.length || 0,
        members: trendData.members
    });
    
    // ダークモード判定
    const isDarkMode = document.documentElement.classList.contains('dark');
    
    // 共通のChartオプション（折れ線グラフ用）
    const lineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                align: 'start',
                labels: {
                    color: isDarkMode ? '#e5e7eb' : '#374151',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    boxWidth: 20,
                    boxHeight: 12,
                    padding: 12,
                    usePointStyle: true,
                    pointStyle: 'circle',
                },
                maxHeight: 80,
            },
            title: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                titleColor: isDarkMode ? '#f3f4f6' : '#111827',
                bodyColor: isDarkMode ? '#e5e7eb' : '#374151',
                borderColor: isDarkMode ? '#4b5563' : '#d1d5db',
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
                grid: {
                    display: false
                },
                ticks: {
                    color: isDarkMode ? '#9ca3af' : '#6b7280',
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 2,
                    color: isDarkMode ? '#9ca3af' : '#6b7280',
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: isDarkMode ? '#374151' : '#e5e7eb'
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    };
    
    // 積み上げ棒グラフ用オプション
    const barOptions = JSON.parse(JSON.stringify(lineOptions));
    barOptions.scales.x.stacked = true;
    barOptions.scales.y.stacked = true;
    barOptions.scales.y.ticks.stepSize = 1;
    barOptions.plugins.legend.labels.pointStyle = 'rectRounded';
    
    // 合計タスクグラフ（折れ線グラフ）
    const totalCtx = document.getElementById('total-trend-chart');
    if (totalCtx && trendData.total?.datasets?.length > 0) {
        new Chart(totalCtx, {
            type: 'line',
            data: {
                labels: trendData.total.labels,
                datasets: trendData.total.datasets
            },
            options: lineOptions
        });
    }
    
    // 報酬獲得の推移グラフ（折れ線グラフ）
    const rewardCtx = document.getElementById('reward-trend-chart');
    if (rewardCtx && trendData.reward?.datasets?.length > 0) {
        // 報酬用にツールチップをカスタマイズ（数値のみ表示）
        const rewardOptions = JSON.parse(JSON.stringify(lineOptions));
        rewardOptions.plugins.tooltip.callbacks = {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += context.parsed.y.toLocaleString();
                }
                return label;
            }
        };
        
        new Chart(rewardCtx, {
            type: 'line',
            data: {
                labels: trendData.reward.labels,
                datasets: trendData.reward.datasets
            },
            options: rewardOptions
        });
    }
    
    // ========================================
    // 3. 詳細グラフの遅延初期化 & トグル制御
    // ========================================
    
    let normalChart = null;
    let groupChart = null;
    let detailChartsInitialized = false;
    
    /**
     * 詳細グラフを初期化（遅延実行）
     * Canvas要素が非表示の状態でChart.jsを初期化すると正しく描画されないため、
     * トグルで表示された後に初期化を実行する
     */
    function initializeDetailCharts() {
        if (detailChartsInitialized) return;
        
        // 通常タスクグラフ（積み上げ棒グラフ）
        const normalCtx = document.getElementById('normal-trend-chart');
        if (normalCtx && trendData.normal?.datasets?.length > 0 && !normalChart) {
            normalChart = new Chart(normalCtx, {
                type: 'bar',
                data: {
                    labels: trendData.normal.labels,
                    datasets: trendData.normal.datasets
                },
                options: barOptions
            });
        }
        
        // グループタスクグラフ（積み上げ棒グラフ）
        const groupCtx = document.getElementById('group-trend-chart');
        if (groupCtx && trendData.group?.datasets?.length > 0 && !groupChart) {
            groupChart = new Chart(groupCtx, {
                type: 'bar',
                data: {
                    labels: trendData.group.labels,
                    datasets: trendData.group.datasets
                },
                options: barOptions
            });
        }
        
        detailChartsInitialized = true;
    }
    
    /**
     * 詳細グラフのトグル制御（スムーズなアニメーション付き）
     */
    const toggleButton = document.getElementById('toggle-detail-charts');
    const detailCharts = document.getElementById('detail-charts');
    const toggleIcon = document.getElementById('toggle-icon');
    
    if (toggleButton && detailCharts && toggleIcon) {
        toggleButton.addEventListener('click', function() {
            const isOpen = detailCharts.style.maxHeight && detailCharts.style.maxHeight !== '0px';
            
            if (!isOpen) {
                // 開く処理
                toggleIcon.classList.add('rotate-180');
                
                // スクロール高さを取得するために一時的にmax-heightを解除
                detailCharts.style.maxHeight = 'none';
                const height = detailCharts.scrollHeight;
                detailCharts.style.maxHeight = '0';
                
                // 次のフレームでアニメーション開始
                requestAnimationFrame(() => {
                    detailCharts.style.maxHeight = height + 'px';
                    detailCharts.style.opacity = '1';
                });
                
                // グラフ初期化（アニメーション開始後に実行）
                if (!detailChartsInitialized) {
                    setTimeout(() => {
                        initializeDetailCharts();
                        
                        // グラフ描画完了後に高さを再調整
                        setTimeout(() => {
                            detailCharts.style.maxHeight = detailCharts.scrollHeight + 'px';
                        }, 100);
                    }, 50);
                }
            } else {
                // 閉じる処理
                toggleIcon.classList.remove('rotate-180');
                detailCharts.style.maxHeight = '0';
                detailCharts.style.opacity = '0';
            }
        });
    }
});
