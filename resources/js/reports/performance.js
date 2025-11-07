import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

// Alpine.jsコンポーネント
window.performanceReport = function(initialTab, initialPeriod, initialOffset) {
    return {
        showSidebar: false,
        activeTab: initialTab,
        activePeriod: initialPeriod,
        offset: initialOffset,
    };
};

// グラフ初期化
document.addEventListener('DOMContentLoaded', () => {
    initializePerformanceChart();
});

let chartInstance = null;

function initializePerformanceChart() {
    const canvas = document.getElementById('performance-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const { tab, currentData } = window.performanceData;
    
    // 既存のチャートインスタンスがあれば破棄
    if (chartInstance) {
        chartInstance.destroy();
    }

    const isGroup = tab === 'group';
    
    // データセット作成
    const datasets = isGroup ? getGroupDatasets(currentData) : getNormalDatasets(currentData);

    // グラフ作成
    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: currentData.labels,
            datasets: datasets,
        },
        options: getChartOptions(isGroup, currentData),
    });
}

/**
 * 通常タスク用のデータセット
 */
function getNormalDatasets(data) {
    return [
        {
            type: 'bar',
            label: '完了',
            data: data.nDone,
            backgroundColor: 'rgba(34, 197, 94, 0.7)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: 'rgba(34, 197, 94, 0.9)',
        },
        {
            type: 'bar',
            label: '未完了',
            data: data.nTodo,
            backgroundColor: 'rgba(239, 68, 68, 0.7)',
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: 'rgba(239, 68, 68, 0.9)',
        },
    ];
}

/**
 * グループタスク用のデータセット
 */
function getGroupDatasets(data) {
    return [
        {
            type: 'bar',
            label: '完了',
            data: data.gDone,
            backgroundColor: 'rgba(147, 51, 234, 0.7)',
            borderColor: 'rgb(147, 51, 234)',
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: 'rgba(147, 51, 234, 0.9)',
        },
        {
            type: 'bar',
            label: '未完了',
            data: data.gTodo,
            backgroundColor: 'rgba(236, 72, 153, 0.7)',
            borderColor: 'rgb(236, 72, 153)',
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: 'rgba(236, 72, 153, 0.9)',
        },
    ];
}

/**
 * チャートオプション
 */
function getChartOptions(isGroup, data) {
    const primaryColor = isGroup ? '#9333ea' : '#59B9C6';
    
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
                    padding: 20,
                    font: {
                        size: 14,
                        weight: 'bold',
                    },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 12,
                    boxHeight: 12,
                },
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                padding: 16,
                titleFont: {
                    size: 14,
                    weight: 'bold',
                },
                bodyFont: {
                    size: 13,
                },
                borderColor: primaryColor,
                borderWidth: 2,
                cornerRadius: 10,
                displayColors: true,
                intersect: false,
                mode: 'index',
            },
        },
        scales: {
            x: {
                stacked: false,
                grid: {
                    display: false,
                    drawBorder: false,
                },
                ticks: {
                    font: {
                        size: 12,
                        weight: '600',
                    },
                    color: '#6b7280',
                    maxRotation: 45,
                    minRotation: 0,
                },
            },
            y: {
                stacked: false,
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    precision: 0,
                    font: {
                        size: 12,
                    },
                    color: '#6b7280',
                },
                grid: {
                    color: 'rgba(209, 213, 219, 0.3)',
                    drawBorder: false,
                },
            },
        },
    };
}