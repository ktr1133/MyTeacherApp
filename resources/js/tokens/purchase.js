/**
 * トークン数を読みやすい形式にフォーマット
 * 
 * @param {number} amount - トークン数
 * @returns {string} フォーマットされた文字列
 * 
 * 例:
 * 999 → "999"
 * 1,000 → "1k"
 * 1,500 → "1.50k"
 * 50,000 → "50k"
 * 500,000 → "500k"
 * 1,250,000 → "1.25M"
 * 2,500,000 → "2.50M"
 * 1,000,000,000 → "1B"
 */
function formatTokenAmount(amount) {
    // 数値に変換
    const num = parseInt(amount);
    
    if (isNaN(num)) {
        return '0';
    }
    
    // 1,000未満はそのまま表示
    if (num < 1000) {
        return num.toString();
    }
    
    // 1,000以上 1,000,000未満 → k (キロ)
    if (num < 1000000) {
        const value = num / 1000;
        return value.toFixed(1) + 'k';
    }
    
    // 1,000,000以上 1,000,000,000未満 → M (メガ)
    if (num < 1000000000) {
        const value = num / 1000000;
        return value.toFixed(1) + 'M';
    }
    
    // 1,000,000,000以上 → B (ビリオン)
    const value = num / 1000000000;
    return value.toFixed(1) + 'B';
}

/**
 * トークン数の表示を更新
 */
function updateTokenAmounts() {
    // .token-amount クラスを持つすべての要素を取得
    const tokenElements = document.querySelectorAll('.token-amount');
    
    tokenElements.forEach(element => {
        // data-original-amount属性から元の値を取得
        // 初回実行時は現在のテキストから取得
        let originalAmount = element.getAttribute('data-original-amount');
        
        if (!originalAmount) {
            // カンマと数字以外を削除して元の数値を取得
            originalAmount = element.textContent.replace(/[^\d]/g, '');
            element.setAttribute('data-original-amount', originalAmount);
        }
        
        // フォーマットして表示
        const formattedAmount = formatTokenAmount(originalAmount);
        element.textContent = formattedAmount;
    });
    
    // 残高表示も同様に処理（token-amountクラスがない場合）
    updateBalanceDisplays();
}

/**
 * 残高表示の更新（残高カード内の大きな数字）
 */
function updateBalanceDisplays() {
    // 残高カード内の大きな数字を探す
    const balanceElements = document.querySelectorAll('.balance-card .text-2xl, .balance-card .text-3xl');
    
    balanceElements.forEach(element => {
        // すでに処理済みかチェック
        if (element.classList.contains('token-amount') || 
            element.getAttribute('data-balance-formatted')) {
            return;
        }
        
        // 数字を含むテキストを検出
        const textContent = element.textContent.trim();
        const match = textContent.match(/[\d,]+/);
        
        if (match) {
            const originalAmount = match[0].replace(/,/g, '');
            const numericValue = parseInt(originalAmount);
            
            // 1,000以上の場合のみフォーマット
            if (numericValue >= 1000) {
                element.setAttribute('data-balance-formatted', 'true');
                element.setAttribute('data-original-amount', originalAmount);
                
                const formattedAmount = formatTokenAmount(originalAmount);
                const newText = textContent.replace(match[0], formattedAmount);
                element.textContent = newText;
            }
        }
    });
}

/**
 * ページ読み込み時に実行
 */
document.addEventListener('DOMContentLoaded', function() {
    // トークン数の表示を更新
    updateTokenAmounts();
    
    // デバッグ用：コンソールに処理結果を出力
    if (window.APP_DEBUG) {
        console.log('Token amounts formatted');
    }
});

/**
 * Alpine.js との連携（必要に応じて）
 */
document.addEventListener('alpine:init', () => {
    // Alpine.jsのコンポーネントが初期化された後にも実行
    setTimeout(() => {
        updateTokenAmounts();
    }, 100);
});

/**
 * トークン購入画面用 Alpine.js コンポーネント
 */
window.tokenPurchase = function() {
    return {
        activeTab: 'packages',

        /**
         * タブ切り替え
         */
        switchTab(tab) {
            this.activeTab = tab;
            // タブ切り替え後にトークン数を再フォーマット
            this.$nextTick(() => {
                updateTokenAmounts();
            });
        }
    };
};

/**
 * グローバルに公開（テスト用）
 */
if (typeof window !== 'undefined') {
    window.formatTokenAmount = formatTokenAmount;
    window.updateTokenAmounts = updateTokenAmounts;
}