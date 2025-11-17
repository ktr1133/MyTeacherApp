/**
 * アバターフォームの制御
 */
window.avatarForm = function() {
    return {
        // フォーム送信中フラグ
        isSubmitting: false,
        
        // 生成状態の監視
        generationStatus: '',
        
        /**
         * 初期化
         */
        init() {
            console.log('Avatar form initialized');
        },
        
        /**
         * フォーム送信処理
         */
        async submitForm(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return;
            }
            
            this.isSubmitting = true;
            
            // フォームのバリデーション
            const form = event.target;
            if (!form.checkValidity()) {
                this.isSubmitting = false;
                return;
            }
            
            // 送信中の表示を更新
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '生成中...';
            }
        },
        
        /**
         * 生成ステータスを確認
         */
        checkGenerationStatus() {
            // ポーリングで生成状態を確認する場合はここに実装
            console.log('Checking generation status...');
        }
    };
};

// モデルの種類によってトークン消費量を更新
document.addEventListener('DOMContentLoaded', function() {
    const modelSelect = document.getElementById('draw-model-version');
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

            tokenAmountDisplay.textContent = `${tokenCost.toLocaleString()}`;
        });
    }
});

