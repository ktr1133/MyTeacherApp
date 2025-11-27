/**
 * トークン数を読みやすい形式にフォーマット
 * 
 * @param {number} amount - トークン数または金額
 * @param {string} prefix - プレフィックス（¥など）
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
function formatTokenAmount(amount, prefix = '') {
    // 数値に変換
    const num = parseInt(amount);
    
    if (isNaN(num)) {
        return prefix + '0';
    }
    
    // 1,000未満はそのまま表示
    if (num < 1000) {
        return prefix + num.toString();
    }
    
    // 1,000以上 1,000,000未満 → k (キロ)
    if (num < 1000000) {
        const value = num / 1000;
        return prefix + value.toFixed(1) + 'k';
    }
    
    // 1,000,000以上 1,000,000,000未満 → M (メガ)
    if (num < 1000000000) {
        const value = num / 1000000;
        return prefix + value.toFixed(1) + 'M';
    }
    
    // 1,000,000,000以上 → B (ビリオン)
    const value = num / 1000000000;
    return prefix + value.toFixed(1) + 'B';
}

/**
 * トークン数の表示を更新
 */
function updateTokenAmounts() {
    // .token-amount クラスを持つすべての要素を取得
    const tokenElements = document.querySelectorAll('.token-amount');
    
    tokenElements.forEach(element => {
        // data-original-amount属性から元の値を取得
        let originalAmount = element.getAttribute('data-original-amount');
        
        if (!originalAmount) {
            return;
        }
        
        // プレフィックス取得（¥など）
        const prefix = element.getAttribute('data-prefix') || '';
        
        // フォーマットして表示
        const formattedAmount = formatTokenAmount(originalAmount, prefix);
        element.textContent = formattedAmount;
    });
}

/**
 * トークン履歴フィルターコントローラー
 * Alpine.jsから移行: Vanilla JavaScript実装
 */
class TokenHistoryController {
    constructor() {
        this.filterType = 'all';
        this.filterPeriod = 'all';
        this.init();
    }
    
    init() {
        // URLパラメータから初期値を取得
        const params = new URLSearchParams(window.location.search);
        this.filterType = params.get('type') || 'all';
        this.filterPeriod = params.get('period') || 'all';
        
        // セレクトボックスを取得
        const typeSelect = document.querySelector('[data-filter-type]');
        const periodSelect = document.querySelector('[data-filter-period]');
        
        if (typeSelect) {
            typeSelect.value = this.filterType;
            typeSelect.addEventListener('change', (e) => {
                this.filterType = e.target.value;
                this.filterTransactions();
            });
        }
        
        if (periodSelect) {
            periodSelect.value = this.filterPeriod;
            periodSelect.addEventListener('change', (e) => {
                this.filterPeriod = e.target.value;
                this.filterTransactions();
            });
        }
    }
    
    /**
     * フィルター適用
     */
    filterTransactions() {
        const params = new URLSearchParams();
        
        if (this.filterType !== 'all') {
            params.set('type', this.filterType);
        }
        
        if (this.filterPeriod !== 'all') {
            params.set('period', this.filterPeriod);
        }
        
        const queryString = params.toString();
        const url = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname;
        window.location.href = url;
    }
}

/**
 * ページ読み込み時に実行
 */
document.addEventListener('DOMContentLoaded', function() {
    // トークン数の表示を更新
    updateTokenAmounts();
    
    // フィルターコントローラーを初期化
    new TokenHistoryController();
});