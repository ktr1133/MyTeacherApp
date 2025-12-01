{{-- パスワード入力ダイアログコンポーネント（Vanilla JS） --}}
<div id="password-prompt-dialog" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true" aria-labelledby="password-prompt-dialog-title">
    {{-- オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" id="password-prompt-dialog-overlay"></div>
    
    {{-- ダイアログコンテナ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            {{-- ダイアログカード --}}
            <div id="password-prompt-dialog-card" 
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-md">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-red-500/10 to-orange-500/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-600 to-orange-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 id="password-prompt-dialog-title" class="text-lg font-bold text-gray-900 dark:text-white">
                            パスワード確認
                        </h3>
                    </div>
                </div>
                
                {{-- メッセージとパスワード入力 --}}
                <div class="px-6 py-6 space-y-4">
                    <p id="password-prompt-dialog-message" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                        削除を確定するには、パスワードを入力してください。
                    </p>
                    
                    {{-- パスワード入力フィールド --}}
                    <div>
                        <label for="password-prompt-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            パスワード
                        </label>
                        <input type="password"
                               id="password-prompt-input"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                               placeholder="パスワードを入力"
                               autocomplete="current-password">
                        <p id="password-prompt-error" class="mt-2 text-sm text-red-600 dark:text-red-400 hidden"></p>
                    </div>
                </div>
                
                {{-- ボタン --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
                    <button type="button" 
                            id="password-prompt-dialog-cancel-btn"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition shadow-sm">
                        キャンセル
                    </button>
                    <button type="button" 
                            id="password-prompt-dialog-confirm-btn"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition shadow-lg">
                        削除を確定
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * パスワード入力ダイアログ（Vanilla JS実装）
 * 
 * 使用例:
 * window.showPasswordPromptDialog(
 *     'アカウント削除を確定するには、パスワードを入力してください。',
 *     (password) => { console.log('Password:', password); },
 *     () => { console.log('キャンセル'); }
 * );
 */
(function() {
    'use strict';
    
    const dialog = document.getElementById('password-prompt-dialog');
    const overlay = document.getElementById('password-prompt-dialog-overlay');
    const card = document.getElementById('password-prompt-dialog-card');
    const messageEl = document.getElementById('password-prompt-dialog-message');
    const passwordInput = document.getElementById('password-prompt-input');
    const errorEl = document.getElementById('password-prompt-error');
    const confirmBtn = document.getElementById('password-prompt-dialog-confirm-btn');
    const cancelBtn = document.getElementById('password-prompt-dialog-cancel-btn');
    
    let currentOnConfirm = null;
    let currentOnCancel = null;
    
    /**
     * ダイアログを表示
     * @param {string} message - 表示メッセージ
     * @param {Function} onConfirm - 確認時のコールバック（引数: password）
     * @param {Function} onCancel - キャンセル時のコールバック（省略可）
     */
    window.showPasswordPromptDialog = function(message, onConfirm, onCancel) {
        if (!dialog || !messageEl || !passwordInput) {
            console.error('[PasswordPromptDialog] Dialog elements not found');
            return;
        }
        
        // コールバック保存
        currentOnConfirm = onConfirm;
        currentOnCancel = onCancel;
        
        // メッセージ設定
        if (message) {
            messageEl.textContent = message;
        }
        
        // 状態リセット
        passwordInput.value = '';
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
        
        // 表示アニメーション
        dialog.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // フェードイン
        requestAnimationFrame(() => {
            overlay.style.opacity = '0';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            
            requestAnimationFrame(() => {
                overlay.style.transition = 'opacity 300ms';
                card.style.transition = 'opacity 300ms, transform 300ms';
                overlay.style.opacity = '1';
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
                
                // フォーカス
                setTimeout(() => passwordInput.focus(), 100);
            });
        });
    };
    
    /**
     * ダイアログを閉じる
     */
    function closeDialog() {
        if (!dialog) return;
        
        overlay.style.opacity = '0';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            dialog.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            currentOnConfirm = null;
            currentOnCancel = null;
            passwordInput.value = '';
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }, 300);
    }
    
    /**
     * 確認ボタンクリック
     */
    function handleConfirm() {
        const password = passwordInput.value.trim();
        
        if (!password) {
            errorEl.textContent = 'パスワードを入力してください。';
            errorEl.classList.remove('hidden');
            passwordInput.focus();
            return;
        }
        
        if (typeof currentOnConfirm === 'function') {
            currentOnConfirm(password);
        }
        
        closeDialog();
    }
    
    /**
     * キャンセルボタンクリック
     */
    function handleCancel() {
        if (typeof currentOnCancel === 'function') {
            currentOnCancel();
        }
        closeDialog();
    }
    
    // イベントリスナー登録
    confirmBtn?.addEventListener('click', handleConfirm);
    cancelBtn?.addEventListener('click', handleCancel);
    overlay?.addEventListener('click', handleCancel);
    
    // Enterキーで確定
    passwordInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleConfirm();
        }
    });
    
    // Escapeキーでキャンセル
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
            handleCancel();
        }
    });
    
    console.log('[PasswordPromptDialog] Initialized');
})();
</script>
@endpush
