/**
 * アバターフォームの制御（Vanilla JS版）
 * Alpine.jsを使用せず、純粋なJavaScriptで実装
 */

// モデルの種類によってトークン消費量を更新
document.addEventListener('DOMContentLoaded', function() {
    const modelSelect = document.querySelector('select[name="draw_model_version"]');
    const tokenAmountDisplay = document.getElementById('token-amount');

    if (modelSelect && tokenAmountDisplay) {
        modelSelect.addEventListener('change', function() {
            const selectedModel = modelSelect.value;
            let tokenCost = 2000; // デフォルトのトークン消費量

            // モデルごとのトークン消費量を設定
            switch (selectedModel) {
                case 'anything-v4.0':
                    tokenCost = 5000;
                    break;
                case 'animagine-xl-3.1':
                    tokenCost = 2000;
                    break;
                case 'stable-diffusion-3.5-medium':
                    tokenCost = 23000;
                    break;
                default:
                    tokenCost = 2000;
            }

            const formattedTokenCost = tokenCost.toLocaleString();

            // 作成画面と編集画面のトークン量表示を更新（同じIDを使用）
            tokenAmountDisplay.textContent = formattedTokenCost;
        });
    }
    
    console.log('[Avatar Form] Token cost updater initialized');
});