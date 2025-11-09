/**
 * 管理者画面共通機能
 */

/**
 * サイドバー開閉管理用のAlpine.jsコンポーネント
 */
function adminPage() {
    return {
        showSidebar: false,

        toggleSidebar() {
            this.showSidebar = !this.showSidebar;
        },

        closeSidebar() {
            this.showSidebar = false;
        },

        /**
         * 数値を短縮形式にフォーマット
         */
        formatStatValue(value) {
            const num = parseInt(String(value).replace(/,/g, ''));
            
            if (num >= 1000000000) {
                return (num / 1000000000).toFixed(1) + 'B';
            }
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 10000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toLocaleString();
        },

        /**
         * 数値が短縮表示されているかチェック
         */
        isShortened(value) {
            const num = parseInt(String(value).replace(/,/g, ''));
            return num >= 10000;
        },

        /**
         * 数値の桁数に応じてフォントサイズを調整
         */
        getStatFontSize(value) {
            const formatted = this.formatStatValue(value);
            const length = formatted.length;
            
            // 短縮表示の場合は大きめに
            if (this.isShortened(value)) {
                return 'text-2xl sm:text-3xl';
            }
            
            if (length <= 4) return 'text-3xl sm:text-4xl';
            if (length <= 6) return 'text-2xl sm:text-3xl';
            if (length <= 8) return 'text-xl sm:text-2xl';
            return 'text-lg sm:text-xl'; // 最小サイズ
        },

        /**
         * 単位のフォントサイズを調整
         */
        getUnitFontSize(value) {
            const formatted = this.formatStatValue(value);
            const length = formatted.length;
            
            if (this.isShortened(value) || length <= 4) {
                return 'text-sm';
            }
            if (length <= 6) {
                return 'text-xs';
            }
            return 'text-xs opacity-80';
        }
    };
}

// グローバルに公開
window.adminPage = adminPage;

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