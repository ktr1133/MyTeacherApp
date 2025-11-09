/**
 * トークン履歴画面用 Alpine.js コンポーネント
 */
window.tokenHistory = function() {
    return {
        showSidebar: false,
        filterType: 'all',
        filterPeriod: 'all',
        
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
        },
        
        /**
         * フィルター適用
         */
        filterTransactions() {
            const params = new URLSearchParams(window.location.search);
            
            if (this.filterType !== 'all') {
                params.set('type', this.filterType);
            } else {
                params.delete('type');
            }
            
            if (this.filterPeriod !== 'all') {
                params.set('period', this.filterPeriod);
            } else {
                params.delete('period');
            }
            
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        }
    };
};

/**
 * Alpine.js 初期化
 */
document.addEventListener('alpine:init', () => {
    console.log('Token History Alpine initialized');
});