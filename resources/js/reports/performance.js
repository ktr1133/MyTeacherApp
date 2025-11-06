import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const makeCombo = (ctx, labels, bars1Label, bars1, bars2Label, bars2, lineLabel, lineData, extraLineLabel = null, extraLine = null) => {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { type: 'bar', label: bars1Label, data: bars1, backgroundColor: 'rgba(59,130,246,.6)', borderColor: 'rgba(59,130,246,1)', borderWidth: 1 },
                { type: 'bar', label: bars2Label, data: bars2, backgroundColor: 'rgba(248,113,113,.6)', borderColor: 'rgba(248,113,113,1)', borderWidth: 1 },
                { type: 'line', label: lineLabel, data: lineData, borderColor: '#10b981', backgroundColor: 'transparent', yAxisID: 'y', tension: .3, borderWidth: 2 },
                ...(extraLine ? [{ type: 'line', label: extraLineLabel, data: extraLine, borderColor: '#8b5cf6', backgroundColor: 'transparent', yAxisID: extraLineLabel === '報酬累計' ? 'y1' : 'y', tension: .3, borderWidth: 2 }] : [])
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
            },
            plugins: { 
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 10, titleFont: { size: 12 }, bodyFont: { size: 11 } }
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const D = window.performanceData;

    // 通常タスク（本人のみ）
    makeCombo(document.getElementById('w-normal'), D.weekNormal.labels, '完了', D.weekNormal.nDone, '未完了', D.weekNormal.nTodo, '累積完了', D.weekNormal.nCum);
    makeCombo(document.getElementById('m-normal'), D.monthNormal.labels, '完了', D.monthNormal.nDone, '未完了', D.monthNormal.nTodo, '累積完了', D.monthNormal.nCum);
    makeCombo(document.getElementById('y-normal'), D.yearNormal.labels, '完了', D.yearNormal.nDone, '未完了', D.yearNormal.nTodo, '累積完了', D.yearNormal.nCum);

    // グループタスク（選択されたメンバーまたはグループ全体）
    makeCombo(document.getElementById('w-group'), D.weekGroup.labels, '完了', D.weekGroup.gDone, '未完了', D.weekGroup.gTodo, '累積完了', D.weekGroup.gCum, '報酬累計', D.weekGroup.gRewardCum);
    makeCombo(document.getElementById('m-group'), D.monthGroup.labels, '完了', D.monthGroup.gDone, '未完了', D.monthGroup.gTodo, '累積完了', D.monthGroup.gCum, '報酬累計', D.monthGroup.gRewardCum);
    makeCombo(document.getElementById('y-group'), D.yearGroup.labels, '完了', D.yearGroup.gDone, '未完了', D.yearGroup.gTodo, '累積完了', D.yearGroup.gCum, '報酬累計', D.yearGroup.gRewardCum);
});