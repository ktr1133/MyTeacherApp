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
 * 表情スライダーコンポーネント（サムネイル型 + 動的背景）
 */
window.expressionSlider = function(expressions) {
    return {
        expressions: expressions,
        currentIndex: 0,
        touchStartX: 0,
        touchEndX: 0,
        minSwipeDistance: 50, // スワイプの最小距離（px）
        
        /**
         * 現在の画像URLを取得（背景用）
         */
        get currentImageUrl() {
            const currentExpr = this.expressions[this.currentIndex];
            if (currentExpr && currentExpr.image) {
                return currentExpr.image.s3_url || currentExpr.image.public_url;
            }
            return null;
        },
        
        /**
         * 指定インデックスの表情へ移動（サムネイルクリック用）
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
         * タッチ終了（スライドアニメーション）
         */
        handleTouchEnd(event) {
            const swipeDistance = this.touchStartX - this.touchEndX;
            
            // 右スワイプ（前へ）
            if (swipeDistance < -this.minSwipeDistance) {
                if (this.currentIndex > 0) {
                    this.currentIndex--;
                }
            }
            // 左スワイプ（次へ）
            else if (swipeDistance > this.minSwipeDistance) {
                if (this.currentIndex < this.expressions.length - 1) {
                    this.currentIndex++;
                }
            }
            
            // リセット
            this.touchStartX = 0;
            this.touchEndX = 0;
        }
    };
};