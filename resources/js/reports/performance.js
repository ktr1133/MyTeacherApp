import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const makeCombo = (ctx, labels, bars1Label, bars1, bars2Label, bars2, lineLabel, lineData, extraLineLabel = null, extraLine = null, isGroup = false) => {
    // Dashboard配色と統一
    const primaryColor = isGroup ? 'rgba(147, 51, 234, 0.7)' : 'rgba(89, 185, 198, 0.7)';
    const primaryBorder = isGroup ? 'rgba(147, 51, 234, 1)' : 'rgba(89, 185, 198, 1)';
    const secondaryColor = 'rgba(248, 113, 113, 0.6)';
    const secondaryBorder = 'rgba(248, 113, 113, 1)';
    const lineColor = isGroup ? '#a855f7' : '#3b82f6';
    const extraLineColor = isGroup ? '#ec4899' : '#10b981';
    
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { 
                    type: 'bar', 
                    label: bars1Label, 
                    data: bars1, 
                    backgroundColor: primaryColor, 
                    borderColor: primaryBorder, 
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: isGroup ? 'rgba(147, 51, 234, 0.85)' : 'rgba(89, 185, 198, 0.85)'
                },
                { 
                    type: 'bar', 
                    label: bars2Label, 
                    data: bars2, 
                    backgroundColor: secondaryColor, 
                    borderColor: secondaryBorder, 
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(248, 113, 113, 0.75)'
                },
                { 
                    type: 'line', 
                    label: lineLabel, 
                    data: lineData, 
                    borderColor: lineColor, 
                    backgroundColor: 'transparent', 
                    yAxisID: 'y', 
                    tension: 0.4, 
                    borderWidth: 3,
                    pointBackgroundColor: lineColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                ...(extraLine ? [{ 
                    type: 'line', 
                    label: extraLineLabel, 
                    data: extraLine, 
                    borderColor: extraLineColor, 
                    backgroundColor: 'transparent', 
                    yAxisID: extraLineLabel === '報酬累計' ? 'y1' : 'y', 
                    tension: 0.4, 
                    borderWidth: 3,
                    pointBackgroundColor: extraLineColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderDash: [5, 5]
                }] : [])
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { 
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        boxWidth: 14, 
                        font: { size: 12, weight: '600' },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    } 
                },
                tooltip: { 
                    backgroundColor: 'rgba(0, 0, 0, 0.9)', 
                    padding: 12, 
                    titleFont: { size: 13, weight: 'bold' }, 
                    bodyFont: { size: 12 },
                    borderColor: isGroup ? 'rgba(147, 51, 234, 0.5)' : 'rgba(89, 185, 198, 0.5)',
                    borderWidth: 2,
                    cornerRadius: 8
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#6b7280'
                    }
                },
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        precision: 0,
                        font: { size: 11 },
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(209, 213, 219, 0.3)'
                    }
                },
                y1: { 
                    position: 'right', 
                    beginAtZero: true, 
                    grid: { drawOnChartArea: false }, 
                    ticks: { 
                        precision: 0,
                        font: { size: 11 },
                        color: '#6b7280',
                        callback: function(value) {
                            return value.toLocaleString() + '円';
                        }
                    }
                }
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const D = window.performanceData;

    // 通常タスク（ティール×ブルー系）
    makeCombo(document.getElementById('w-normal'), D.weekNormal.labels, '完了', D.weekNormal.nDone, '未完了', D.weekNormal.nTodo, '累積完了', D.weekNormal.nCum, null, null, false);
    makeCombo(document.getElementById('m-normal'), D.monthNormal.labels, '完了', D.monthNormal.nDone, '未完了', D.monthNormal.nTodo, '累積完了', D.monthNormal.nCum, null, null, false);
    makeCombo(document.getElementById('y-normal'), D.yearNormal.labels, '完了', D.yearNormal.nDone, '未完了', D.yearNormal.nTodo, '累積完了', D.yearNormal.nCum, null, null, false);

    // グループタスク（パープル×ピンク系）
    makeCombo(document.getElementById('w-group'), D.weekGroup.labels, '完了', D.weekGroup.gDone, '未完了', D.weekGroup.gTodo, '累積完了', D.weekGroup.gCum, '報酬累計', D.weekGroup.gRewardCum, true);
    makeCombo(document.getElementById('m-group'), D.monthGroup.labels, '完了', D.monthGroup.gDone, '未完了', D.monthGroup.gTodo, '累積完了', D.monthGroup.gCum, '報酬累計', D.monthGroup.gRewardCum, true);
    makeCombo(document.getElementById('y-group'), D.yearGroup.labels, '完了', D.yearGroup.gDone, '未完了', D.yearGroup.gTodo, '累積完了', D.yearGroup.gCum, '報酬累計', D.yearGroup.gRewardCum, true);
});