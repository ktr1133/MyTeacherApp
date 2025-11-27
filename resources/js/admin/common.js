/**
 * 管理者画面共通機能
 */

/**
 * 統計値のツールチップ初期化
 */
document.addEventListener('DOMContentLoaded', function() {
    // ツールチップ要素を動的に作成
    const tooltip = document.createElement('div');
    tooltip.id = 'stat-tooltip';
    tooltip.className = 'stat-tooltip';
    document.body.appendChild(tooltip);

    // 統計値のホバーイベント
    document.addEventListener('mouseover', function(e) {
        const statValue = e.target.closest('.stat-value-container');
        if (statValue && statValue.dataset.fullValue) {
            tooltip.textContent = statValue.dataset.fullValue;
            tooltip.classList.add('active');
            updateTooltipPosition(e);
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (tooltip.classList.contains('active')) {
            updateTooltipPosition(e);
        }
    });

    document.addEventListener('mouseout', function(e) {
        const statValue = e.target.closest('.stat-value-container');
        if (statValue) {
            tooltip.classList.remove('active');
        }
    });

    function updateTooltipPosition(e) {
        const x = e.clientX;
        const y = e.clientY;
        const tooltipWidth = tooltip.offsetWidth;
        const tooltipHeight = tooltip.offsetHeight;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        // 画面外に出ないように調整
        let left = x + 15;
        let top = y - 10;

        if (left + tooltipWidth > windowWidth - 20) {
            left = x - tooltipWidth - 15;
        }

        if (top + tooltipHeight > windowHeight - 20) {
            top = windowHeight - tooltipHeight - 20;
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }
});