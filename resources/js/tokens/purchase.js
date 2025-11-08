/**
 * トークン購入画面用 Alpine.js コンポーネント
 */
window.tokenPurchase = function() {
    return {
        showSidebar: false,
        
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

/**
 * Alpine.js 初期化
 */
document.addEventListener('alpine:init', () => {
    // ここに追加の初期化処理があれば記述
});