{{-- 
    汎用アラートダイアログコンポーネント
    
    使用方法:
    window.showAlertDialog('メッセージ', 'タイトル', () => {
        // OKボタンクリック後のコールバック（オプション）
    });
--}}
<div id="alert-dialog" 
     class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-gray-900/75 backdrop-blur-sm"
     role="dialog"
     aria-modal="true"
     aria-labelledby="alert-dialog-title"
     aria-describedby="alert-dialog-message">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md transform transition-all opacity-0 scale-95"
         id="alert-dialog-content">
        
        {{-- ヘッダー --}}
        <div class="px-6 py-4 border-b border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 id="alert-dialog-title" class="text-lg font-bold text-gray-900 dark:text-white">
                    お知らせ
                </h3>
            </div>
        </div>
        
        {{-- メッセージ --}}
        <div class="px-6 py-6">
            <p id="alert-dialog-message" class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                <!-- メッセージがここに挿入されます -->
            </p>
        </div>
        
        {{-- フッター --}}
        <div class="px-6 py-4 border-t border-gray-200/50 dark:border-gray-700/50 flex justify-end">
            <button type="button"
                    id="alert-dialog-ok-btn"
                    class="inline-flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-[#59B9C6] to-blue-600 hover:from-[#4AA0AB] hover:to-blue-700 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition">
                OK
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    const dialog = document.getElementById('alert-dialog');
    const content = document.getElementById('alert-dialog-content');
    const titleElement = document.getElementById('alert-dialog-title');
    const messageElement = document.getElementById('alert-dialog-message');
    const okButton = document.getElementById('alert-dialog-ok-btn');
    
    let currentCallback = null;
    
    /**
     * アラートダイアログを表示
     * @param {string} message - メッセージ
     * @param {string} title - タイトル（デフォルト: 'お知らせ'）
     * @param {Function} onOk - OKボタンクリック時のコールバック（オプション）
     */
    window.showAlertDialog = function(message, title = 'お知らせ', onOk = null) {
        if (!dialog || !content || !titleElement || !messageElement || !okButton) {
            console.error('[AlertDialog] Required elements not found');
            alert(message);
            return;
        }
        
        // タイトルとメッセージを設定
        titleElement.textContent = title;
        messageElement.textContent = message;
        
        // コールバックを保存
        currentCallback = onOk;
        
        // ダイアログを表示
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        
        // アニメーション
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                content.classList.remove('opacity-0', 'scale-95');
                content.classList.add('opacity-100', 'scale-100');
            });
        });
        
        // 最初のフォーカス可能な要素にフォーカス
        okButton.focus();
    };
    
    /**
     * アラートダイアログを閉じる
     */
    function closeAlertDialog() {
        if (!dialog || !content) return;
        
        // アニメーション
        content.classList.remove('opacity-100', 'scale-100');
        content.classList.add('opacity-0', 'scale-95');
        
        setTimeout(() => {
            dialog.classList.remove('flex');
            dialog.classList.add('hidden');
            
            // コールバック実行
            if (currentCallback && typeof currentCallback === 'function') {
                currentCallback();
            }
            currentCallback = null;
        }, 200);
    }
    
    // OKボタンクリック
    if (okButton) {
        okButton.addEventListener('click', closeAlertDialog);
    }
    
    // Escapeキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && dialog && dialog.classList.contains('flex')) {
            closeAlertDialog();
        }
    });
    
    // 背景クリックで閉じる
    if (dialog) {
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                closeAlertDialog();
            }
        });
    }
})();
</script>
