/**
 * アバター編集画面の制御
 */

// Alpine.jsが読み込まれる前に関数を定義
window.avatarEdit = function() {
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
 * ★ 表情スライダーコンポーネント
 */
window.expressionSlider = function(expressions) {
    return {
        expressions: expressions,
        currentIndex: 0,
        touchStartX: 0,
        touchEndX: 0,
        minSwipeDistance: 50, // スワイプの最小距離（px）
        
        /**
         * 前の表情へ移動
         */
        prevExpression() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            }
        },
        
        /**
         * 次の表情へ移動
         */
        nextExpression() {
            if (this.currentIndex < this.expressions.length - 1) {
                this.currentIndex++;
            }
        },
        
        /**
         * 指定インデックスの表情へ移動
         */
        goToExpression(index) {
            if (index >= 0 && index < this.expressions.length) {
                this.currentIndex = index;
            }
        },
        
        /**
         * タッチ開始
         */
        handleTouchStart(event) {
            this.touchStartX = event.touches[0].clientX;
        },
        
        /**
         * タッチ移動
         */
        handleTouchMove(event) {
            this.touchEndX = event.touches[0].clientX;
        },
        
        /**
         * タッチ終了
         */
        handleTouchEnd(event) {
            const swipeDistance = this.touchStartX - this.touchEndX;
            
            // 右スワイプ（前へ）
            if (swipeDistance < -this.minSwipeDistance) {
                this.prevExpression();
            }
            // 左スワイプ（次へ）
            else if (swipeDistance > this.minSwipeDistance) {
                this.nextExpression();
            }
            
            // リセット
            this.touchStartX = 0;
            this.touchEndX = 0;
        }
    };
};