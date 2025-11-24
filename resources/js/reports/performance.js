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
    
    // アバター表示（実績画面のみ、他ページからの遷移時のみ）
    if (window.location.pathname === '/reports/performance') {
        const referrer = document.referrer;
        
        // リファラーが空（直接アクセス）または実績画面以外からの遷移
        const isExternalTransition = !referrer || !referrer.includes('/reports/performance');
        
        if (isExternalTransition) {
            console.log('[Performance Avatar] External transition detected, showing avatar');
            console.log('[Performance Avatar] Referrer:', referrer || '(direct access)');
            showPerformanceAvatarOnLoad();
        } else {
            console.log('[Performance Avatar] Internal navigation detected, skipping avatar');
            console.log('[Performance Avatar] Referrer:', referrer);
        }
    }
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
    if (isGroup && data.gRewardCum) {
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
    if (data.nCum) {
        datasets.push({
            type: 'line',
            label: totalLabel,
            data: data.nCum,
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
    if (data.gCum) {
        datasets.push({
            type: 'line',
            label: totalLabel,
            data: data.gCum,
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
    if (data.gRewardCum) {
        datasets.push({
            type: 'line',
            label: rewardLabel,
            data: data.gRewardCum,
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

// ========================================
// 実績画面専用アバター表示機能
// ========================================

/**
 * 実績画面アバター表示（画面遷移時）
 * 
 * 注意: コメント内容に「今週」「今月」という文言があるため、
 * 常に固定期間（今週/今月）のデータを使用する。
 * 表示中の期間（過去の週/月）のデータは使用しない。
 */
function showPerformanceAvatarOnLoad() {
    const isChildTheme = document.documentElement.classList.contains('child-theme');
    const { normalData, groupData } = window.performanceData || {};
    
    if (!normalData || !groupData) {
        console.warn('[Performance Avatar] Data not available');
        return;
    }
    
    let comment, value;
    
    if (isChildTheme) {
        // 子ども向け: 今月の報酬累計（groupData は常に month/offset=0 想定）
        // PHPのキー名は gRewardCum（Cumulative の略）
        // コメントに「今月」という文言が入っているため、当月固定のデータを使用
        const rewardCumulative = groupData.gRewardCum || [];
        value = rewardCumulative[rewardCumulative.length - 1] || 0;
        comment = `今月は${value.toLocaleString()}コインゲット！<br>がんばったね！`;
        console.log('[Performance Avatar] Child theme - Today month reward');
        console.log('[Performance Avatar] Child theme - groupData:', groupData);
        console.log('[Performance Avatar] Child theme - Reward cumulative array:', rewardCumulative);
        console.log('[Performance Avatar] Child theme - Final value:', value);
    } else {
        // 大人向け: 今週の完了件数（normalData は常に week/offset=0 想定）
        // コメントに「今週」という文言が入っているため、今週固定のデータを使用
        const completedCount = (normalData.nDone || []).reduce((sum, n) => sum + n, 0);
        value = completedCount;
        comment = `今週は${value}件完了しました。<br>お疲れ様です。`;
        console.log('[Performance Avatar] Adult theme - This week completed count');
        console.log('[Performance Avatar] Adult theme - Completed count:', completedCount);
    }
    
    // アバター表示実行
    showPerformanceAvatar({
        comment: comment,
        imageUrl: null, // データ属性から取得
        animation: 'avatar-cheer',
        isChildTheme: isChildTheme
    });
}

/**
 * 実績画面専用アバター表示
 */
function showPerformanceAvatar(data) {
    // 既存のオーバーレイがあれば削除
    const existing = document.getElementById('performance-avatar-overlay');
    if (existing) existing.remove();
    
    const { comment, imageUrl, animation, isChildTheme } = data;
    
    // アバター画像URL取得（既存ウィジェットのdata属性から）
    const widget = document.getElementById('avatar-widget');
    const avatarImage = imageUrl || widget?.dataset.happyImage || widget?.dataset.defaultImage;
    
    if (!avatarImage) {
        console.warn('[Performance Avatar] No avatar image available');
        return;
    }
    
    // オーバーレイ生成
    const overlay = document.createElement('div');
    overlay.id = 'performance-avatar-overlay';
    overlay.className = 'performance-avatar-overlay';
    overlay.innerHTML = `
        ${isChildTheme ? '<div class="performance-celebration-bg"></div>' : ''}
        
        <div class="performance-avatar-container ${isChildTheme ? 'child-theme' : ''}">
            <div class="performance-avatar-bubble">
                <p class="comment-text">${comment}</p>
            </div>
            
            <img 
                src="${avatarImage}" 
                alt="Teacher Avatar"
                class="performance-avatar-image ${animation}"
            />
            
            <button class="performance-avatar-close" type="button" title="閉じる">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // 閉じるボタンイベント
    overlay.querySelector('.performance-avatar-close').addEventListener('click', () => {
        hidePerformanceAvatar();
    });
    
    // オーバーレイクリックで閉じる（バブリング対策）
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) hidePerformanceAvatar();
    });
    
    // フェードイン
    requestAnimationFrame(() => {
        overlay.classList.add('show');
    });
    
    // 子ども向けエフェクト
    if (isChildTheme) {
        triggerCelebrationEffects();
    }
    
    // 20秒後に自動非表示
    setTimeout(() => hidePerformanceAvatar(), 20000);
}

/**
 * アバター非表示
 */
function hidePerformanceAvatar() {
    const overlay = document.getElementById('performance-avatar-overlay');
    if (!overlay) return;
    
    overlay.classList.remove('show');
    
    setTimeout(() => {
        overlay.remove();
    }, 500);
}

/**
 * 子ども向け祝福エフェクト
 */
function triggerCelebrationEffects() {
    // 花火エフェクト（3回）
    if (typeof confetti !== 'undefined') {
        const fireConfetti = () => {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#FFD700', '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A']
            });
        };
        
        fireConfetti();
        setTimeout(fireConfetti, 300);
        setTimeout(fireConfetti, 600);
    }
    
    // パーティクル生成
    createFloatingParticles();
}

/**
 * 浮遊パーティクル生成
 */
function createFloatingParticles() {
    const overlay = document.getElementById('performance-avatar-overlay');
    if (!overlay) return;
    
    const particleCount = 20;
    const particles = ['⭐', '💖', '✨', '🌟', '💫'];
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'floating-particle';
        particle.textContent = particles[Math.floor(Math.random() * particles.length)];
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.animationDelay = `${Math.random() * 2}s`;
        particle.style.animationDuration = `${5 + Math.random() * 3}s`;
        
        overlay.appendChild(particle);
    }
}