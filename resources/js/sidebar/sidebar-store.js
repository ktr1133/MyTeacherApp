/**
 * サイドバー状態管理ストア
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        // 初期状態を localStorage から取得
        isCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',

        /**
         * サイドバーの開閉を切り替え
         */
        toggle() {
            this.isCollapsed = !this.isCollapsed;
            localStorage.setItem('sidebar-collapsed', this.isCollapsed);
            
            // 状態変更イベントを発火（将来の拡張用）
            window.dispatchEvent(new CustomEvent('sidebar-toggled', {
                detail: { isCollapsed: this.isCollapsed }
            }));
        },

        /**
         * サイドバーを展開
         */
        expand() {
            if (this.isCollapsed) {
                this.toggle();
            }
        },

        /**
         * サイドバーを最小化
         */
        collapse() {
            if (!this.isCollapsed) {
                this.toggle();
            }
        }
    });
});