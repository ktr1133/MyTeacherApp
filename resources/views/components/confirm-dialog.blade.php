{{-- 汎用確認ダイアログコンポーネント（Vanilla JS） --}}
<div id="confirm-dialog" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
    {{-- オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" id="confirm-dialog-overlay"></div>
    
    {{-- ダイアログコンテナ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            {{-- ダイアログカード --}}
            <div id="confirm-dialog-card" 
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-md">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 id="confirm-dialog-title" class="text-lg font-bold text-gray-900 dark:text-white">
                            確認
                        </h3>
                    </div>
                </div>
                
                {{-- メッセージ --}}
                <div class="px-6 py-6">
                    <p id="confirm-dialog-message" class="text-base text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                        <!-- メッセージがここに表示されます -->
                    </p>
                </div>
                
                {{-- ボタン --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
                    <button type="button" 
                            id="confirm-dialog-cancel-btn"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition shadow-sm">
                        キャンセル
                    </button>
                    <button type="button" 
                            id="confirm-dialog-confirm-btn"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA5B2] hover:to-purple-700 transition shadow-lg">
                        実行
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * 汎用確認ダイアログ（Vanilla JS実装）
 * 
 * 使用例:
 * window.showConfirmDialog(
 *     '本当に削除しますか？',
 *     () => { console.log('確認'); },
 *     () => { console.log('キャンセル'); }
 * );
 */
(function() {
    'use strict';
    
    const dialog = document.getElementById('confirm-dialog');
    const overlay = document.getElementById('confirm-dialog-overlay');
    const card = document.getElementById('confirm-dialog-card');
    const titleEl = document.getElementById('confirm-dialog-title');
    const messageEl = document.getElementById('confirm-dialog-message');
    const confirmBtn = document.getElementById('confirm-dialog-confirm-btn');
    const cancelBtn = document.getElementById('confirm-dialog-cancel-btn');
    
    let currentOnConfirm = null;
    let currentOnCancel = null;
    
    /**
     * ダイアログを表示
     * @param {string} message - 表示メッセージ
     * @param {Function} onConfirm - 確認時のコールバック
     * @param {Function} onCancel - キャンセル時のコールバック（省略可）
     */
    window.showConfirmDialog = function(message, onConfirm, onCancel) {
        if (!dialog || !messageEl) {
            console.error('[ConfirmDialog] Dialog elements not found');
            return;
        }
        
        // コールバック保存
        currentOnConfirm = onConfirm;
        currentOnCancel = onCancel;
        
        // タイトルとメッセージ設定
        if (titleEl) titleEl.textContent = '確認';
        messageEl.textContent = message;
        
        // 両方のボタンを表示
        if (cancelBtn) cancelBtn.classList.remove('hidden');
        if (confirmBtn) confirmBtn.textContent = '実行';
        
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
            });
        });
        
        // 確認ボタンにフォーカス
        setTimeout(() => confirmBtn?.focus(), 100);
    };
    
    /**
     * アラートダイアログを表示（OKボタンのみ）
     * @param {string} message - 表示メッセージ
     * @param {Function} onClose - 閉じた時のコールバック（省略可）
     */
    window.showAlertDialog = function(message, onClose) {
        if (!dialog || !messageEl) {
            console.error('[ConfirmDialog] Dialog elements not found');
            return;
        }
        
        // コールバック保存
        currentOnConfirm = onClose;
        currentOnCancel = null;
        
        // タイトルとメッセージ設定
        if (titleEl) titleEl.textContent = '通知';
        messageEl.textContent = message;
        
        // キャンセルボタンを非表示、確認ボタンを「OK」に変更
        if (cancelBtn) cancelBtn.classList.add('hidden');
        if (confirmBtn) confirmBtn.textContent = 'OK';
        
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
            });
        });
        
        // 確認ボタンにフォーカス
        setTimeout(() => confirmBtn?.focus(), 100);
    };
    
    /**
     * ダイアログを閉じる
     */
    function closeDialog() {
        if (!dialog) return;
        
        // フェードアウト
        overlay.style.opacity = '0';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            dialog.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            currentOnConfirm = null;
            currentOnCancel = null;
        }, 300);
    }
    
    /**
     * 確認ボタンクリック
     */
    function handleConfirm() {
        closeDialog();
        if (typeof currentOnConfirm === 'function') {
            currentOnConfirm();
        }
    }
    
    /**
     * キャンセルボタンクリック
     */
    function handleCancel() {
        closeDialog();
        if (typeof currentOnCancel === 'function') {
            currentOnCancel();
        }
    }
    
    // イベントリスナー登録
    confirmBtn?.addEventListener('click', handleConfirm);
    cancelBtn?.addEventListener('click', handleCancel);
    overlay?.addEventListener('click', handleCancel);
    
    // Escapeキーでキャンセル
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
            handleCancel();
        }
    });
    
    console.log('[ConfirmDialog] Initialized');
})();
</script>
@endpush
