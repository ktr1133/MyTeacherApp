import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

// Alpine.jsが読み込まれる前に関数を定義
window.performanceReport = function(initialTab, initialPeriod, initialOffset) {
    return {
        showSidebar: false,
        activeTab: initialTab,
        activePeriod: initialPeriod,
        offset: initialOffset,
        
        /**
         * サイドバーを閉じる
         */
        closeSidebar() {
            this.showSidebar = false;
        },
        
        /**
         * サイドバーを開く
         */
        openSidebar() {
            this.showSidebar = true;
        },
        
        /**
         * サイドバーをトグル
         */
        toggleSidebar() {
            this.showSidebar = !this.showSidebar;
        }
    };
};

// グラフ初期化（DOMContentLoadedで実行）
document.addEventListener('DOMContentLoaded', () => {
    initializePerformanceChart();
});

let chartInstance = null;

function initializePerformanceChart() {
    const canvas = document.getElementById('performance-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const { tab, currentData } = window.performanceData || {};
    
    if (!currentData) {
        console.error('Performance data not found');
        return;
    }
    
    // 既存のチャートインスタンスがあれば破棄
    if (chartInstance) {
        chartInstance.destroy();
    }

    const isGroup = tab === 'group';
    
    // 子ども向けテーマかどうかを判定
    const isChildTheme = document.documentElement.classList.contains('child-theme');
    
    // データセット作成（子ども向けテーマを考慮）
    const datasets = isGroup 
        ? getGroupDatasets(currentData, isChildTheme) 
        : getNormalDatasets(currentData, isChildTheme);

    // グラフ作成
    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: currentData.labels,
            datasets: datasets,
        },
        options: getChartOptions(isGroup, currentData, isChildTheme),
    });
}


/**
 * チャートオプション
 */
function getChartOptions(isGroup, data, isChildTheme) {
    const primaryColor = isGroup ? '#9333ea' : '#59B9C6';
    
    // 子ども向けテーマの場合、フォントサイズを大きく（1.25倍）
    const baseFontSize = isChildTheme ? 16 : 13;
    const titleFontSize = isChildTheme ? 18 : 15;
    const legendFontSize = isChildTheme ? 16 : 13;
    
    const scales = {
        x: {
            stacked: false,
            grid: {
                display: false,
                drawBorder: false,
            },
            ticks: {
                font: {
                    size: baseFontSize,
                    weight: '600',
                    family: isChildTheme ? 'Nunito, "Noto Sans JP", sans-serif' : 'Figtree, sans-serif',
                },
                color: '#6b7280',
                maxRotation: 45,
                minRotation: 0,
            },
        },
        y: {
            stacked: false,
            beginAtZero: true,
            position: 'left',
            ticks: {
                stepSize: 1,
                precision: 0,
                font: {
                    size: baseFontSize,
                    family: isChildTheme ? 'Nunito, "Noto Sans JP", sans-serif' : 'Figtree, sans-serif',
                },
                color: '#6b7280',
            },
            grid: {
                color: 'rgba(209, 213, 219, 0.3)',
                drawBorder: false,
            },
        },
    };
    
    // グループタスクで報酬累計がある場合、第2軸を追加
    if (isGroup && data.gRewardCumulative) {
        scales['y-reward'] = {
            type: 'linear',
            position: 'right',
            beginAtZero: true,
            ticks: {
                stepSize: 10,
                precision: 0,
                font: {
                    size: baseFontSize,
                    family: isChildTheme ? 'Nunito, "Noto Sans JP", sans-serif' : 'Figtree, sans-serif',
                },
                color: 'rgb(243, 156, 18)',
                callback: function(value) {
                    return value + (isChildTheme ? 'コイン' : '円');
                },
            },
            grid: {
                drawOnChartArea: false,
            },
        };
    }
    
    return {
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
                    padding: isChildTheme ? 20 : 16,
                    font: {
                        size: legendFontSize,
                        weight: 'bold',
                        family: isChildTheme ? 'Nunito, "Noto Sans JP", sans-serif' : 'Figtree, sans-serif',
                    },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: isChildTheme ? 14 : 12,
                    boxHeight: isChildTheme ? 14 : 12,
                },
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                padding: isChildTheme ? 16 : 12,
                titleFont: {
                    size: titleFontSize,
                    weight: 'bold',
                    family: isChildTheme ? 'Nunito, "Noto Sans JP", sans-serif' : 'Figtree, sans-serif',
                },
                bodyFont: {
                    size: baseFontSize,
                    family: isChildTheme ? 'Nunito, "Noto Sans JP", sans-serif' : 'Figtree, sans-serif',
                },
                borderColor: primaryColor,
                borderWidth: 2,
                cornerRadius: isChildTheme ? 10 : 8,
                displayColors: true,
                intersect: false,
                mode: 'index',
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        
                        if (label) {
                            label += ': ';
                        }
                        
                        // 報酬累計の場合は「円」または「コイン」を追加
                        if (context.dataset.yAxisID === 'y-reward') {
                            label += context.parsed.y + (isChildTheme ? 'コイン' : '円');
                        } else {
                            label += context.parsed.y;
                        }
                        
                        return label;
                    }
                }
            },
        },
        scales: scales,
        // 子ども向けテーマの場合、アニメーションを強化
        animation: {
            duration: isChildTheme ? 1000 : 800,
            easing: isChildTheme ? 'easeOutBounce' : 'easeInOutQuart',
        },
    };
}

/**
 * 通常タスク用のデータセット
 * @param {Object} data - グラフデータ
 * @param {boolean} isChildTheme - 子ども向けテーマかどうか
 */
function getNormalDatasets(data, isChildTheme) {
    // 子ども向けテーマの場合は YET/DONE/ごうけい、大人用は 未完了/完了/累積完了
    const doneLabel = isChildTheme ? 'DONE' : '完了';
    const todoLabel = isChildTheme ? 'YET' : '未完了';
    const totalLabel = isChildTheme ? 'ごうけい' : '累積完了';
    
    const datasets = [
        {
            type: 'bar',
            label: doneLabel,
            data: data.nDone,
            backgroundColor: isChildTheme ? 'rgba(16, 185, 129, 0.7)' : 'rgba(16, 185, 129, 0.8)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 2,
            borderRadius: isChildTheme ? 10 : 8,
            hoverBackgroundColor: 'rgba(16, 185, 129, 0.9)',
            order: 2,
        },
        {
            type: 'bar',
            label: todoLabel,
            data: data.nTodo,
            backgroundColor: isChildTheme ? 'rgba(251, 146, 60, 0.7)' : 'rgba(239, 68, 68, 0.8)',
            borderColor: isChildTheme ? 'rgb(251, 146, 60)' : 'rgb(239, 68, 68)',
            borderWidth: 2,
            borderRadius: isChildTheme ? 10 : 8,
            hoverBackgroundColor: isChildTheme ? 'rgba(251, 146, 60, 0.9)' : 'rgba(239, 68, 68, 0.9)',
            order: 3,
        },
    ];
    
    // 累積完了を折れ線グラフで追加
    if (data.nCumulative) {
        datasets.push({
            type: 'line',
            label: totalLabel,
            data: data.nCumulative,
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            borderColor: 'rgb(168, 85, 247)',
            borderWidth: 3,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: isChildTheme ? 6 : 5,
            pointHoverRadius: isChildTheme ? 8 : 7,
            tension: 0.4,
            fill: false,
            order: 1,
        });
    }
    
    return datasets;
}

/**
 * グループタスク用のデータセット
 * @param {Object} data - グラフデータ
 * @param {boolean} isChildTheme - 子ども向けテーマかどうか
 */
function getGroupDatasets(data, isChildTheme) {
    // 子ども向けテーマの場合は YET/DONE/ごうけい/コイン、大人用は 未完了/完了/累積完了/報酬累計
    const doneLabel = isChildTheme ? 'DONE' : '完了';
    const todoLabel = isChildTheme ? 'YET' : '未完了';
    const totalLabel = isChildTheme ? 'ごうけい' : '累積完了';
    const rewardLabel = isChildTheme ? 'コイン' : '報酬累計';
    
    const datasets = [
        {
            type: 'bar',
            label: doneLabel,
            data: data.gDone,
            backgroundColor: isChildTheme ? 'rgba(16, 185, 129, 0.7)' : 'rgba(147, 51, 234, 0.8)',
            borderColor: isChildTheme ? 'rgb(16, 185, 129)' : 'rgb(147, 51, 234)',
            borderWidth: 2,
            borderRadius: isChildTheme ? 10 : 8,
            hoverBackgroundColor: isChildTheme ? 'rgba(16, 185, 129, 0.9)' : 'rgba(147, 51, 234, 0.9)',
            order: 2,
        },
        {
            type: 'bar',
            label: todoLabel,
            data: data.gTodo,
            backgroundColor: isChildTheme ? 'rgba(251, 146, 60, 0.7)' : 'rgba(236, 72, 153, 0.8)',
            borderColor: isChildTheme ? 'rgb(251, 146, 60)' : 'rgb(236, 72, 153)',
            borderWidth: 2,
            borderRadius: isChildTheme ? 10 : 8,
            hoverBackgroundColor: isChildTheme ? 'rgba(251, 146, 60, 0.9)' : 'rgba(236, 72, 153, 0.9)',
            order: 3,
        },
    ];
    
    // 累積完了を折れ線グラフで追加
    if (data.gCumulative) {
        datasets.push({
            type: 'line',
            label: totalLabel,
            data: data.gCumulative,
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            borderColor: 'rgb(168, 85, 247)',
            borderWidth: 3,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: isChildTheme ? 6 : 5,
            pointHoverRadius: isChildTheme ? 8 : 7,
            tension: 0.4,
            fill: false,
            order: 1,
        });
    }
    
    // 報酬累計を折れ線グラフで追加
    if (data.gRewardCumulative) {
        datasets.push({
            type: 'line',
            label: rewardLabel,
            data: data.gRewardCumulative,
            backgroundColor: 'rgba(243, 156, 18, 0.1)',
            borderColor: 'rgb(243, 156, 18)',
            borderWidth: 3,
            pointBackgroundColor: 'rgb(243, 156, 18)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: isChildTheme ? 6 : 5,
            pointHoverRadius: isChildTheme ? 8 : 7,
            tension: 0.4,
            fill: false,
            yAxisID: 'y-reward',
            order: 1,
        });
    }
    
    return datasets;
}