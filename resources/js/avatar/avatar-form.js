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

