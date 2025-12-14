{{-- グループタスク作成上限エラーモーダル（Vanilla JS） --}}
<div id="group-task-limit-modal"
     class="fixed inset-0 z-[9999] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="group-task-limit-title">
    
    {{-- 背景オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity modal-overlay">
    </div>

    {{-- モーダルコンテンツ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-md modal-content"
                 style="opacity: 0; transform: translateY(1rem) scale(0.95);">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-purple-600/10 to-pink-600/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 id="group-task-limit-title" class="text-lg font-bold text-gray-900 dark:text-white">
                            グループタスク作成上限
                        </h3>
                    </div>
                </div>

                {{-- メッセージ --}}
                <div class="px-6 py-6">
                    <p id="group-task-limit-message" class="text-base text-gray-700 dark:text-gray-300 mb-4">
                        <!-- エラーメッセージがここに動的に挿入されます -->
                    </p>
                    
                    {{-- プラン情報 --}}
                    <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 rounded-lg p-4 border border-[#59B9C6]/30 dark:border-purple-600/30">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                            🎉 サブスクリプションで制限解除
                        </p>
                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm mb-3">
                            <li>グループタスクを無制限に作成</li>
                            <li>月次レポート自動生成</li>
                            <li>全機能が使い放題</li>
                        </ul>
                        <p class="text-2xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
                            月額 ¥500〜
                        </p>
                    </div>
                </div>

                {{-- ボタン --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
                    <button type="button"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition shadow-sm"
                            onclick="GroupTaskLimitModal.hide()">
                        閉じる
                    </button>
                    <a href="{{ route('subscriptions.index') }}"
                       class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA5B2] hover:to-purple-700 transition shadow-lg">
                        サブスク管理画面へ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * グループタスク作成上限エラーモーダル管理
 * Vanilla JS実装（Alpine.js不使用）
 */
const GroupTaskLimitModal = {
    modal: null,
    overlay: null,
    content: null,
    messageElement: null,
    
    /**
     * 初期化
     */
    init() {
        this.modal = document.getElementById('group-task-limit-modal');
        if (!this.modal) return;
        
        this.overlay = this.modal.querySelector('.modal-overlay');
        this.content = this.modal.querySelector('.modal-content');
        this.messageElement = document.getElementById('group-task-limit-message');
        
        // モーダル全体のクリックイベント（オーバーレイクリックで閉じる）
        this.modal.addEventListener('click', (e) => {
            // クリックされた要素がモーダルコンテンツの外側かチェック
            if (!this.content.contains(e.target) && e.target !== this.content) {
                this.hide();
            }
        });
        
        // ESCキーで閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.hide();
            }
        });
    },
    
    /**
     * モーダル表示
     * @param {string} message - エラーメッセージ
     */
    show(message = '') {
        if (!this.modal) {
            return;
        }
        
        // メッセージを設定
        if (this.messageElement && message) {
            this.messageElement.textContent = message;
        }
        
        // モーダル表示
        this.modal.classList.remove('hidden');
        
        // アニメーション用のフレーム遅延
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                if (this.content) {
                    this.content.style.opacity = '1';
                    this.content.style.transform = 'translateY(0) scale(1)';
                }
            });
        });
        
        // body固定（スクロール防止）
        document.body.style.overflow = 'hidden';
    },
    
    /**
     * モーダル非表示
     */
    hide() {
        if (!this.modal) return;
        
        // アニメーション
        if (this.content) {
            this.content.style.opacity = '0';
            this.content.style.transform = 'translateY(1rem) scale(0.95)';
        }
        
        // アニメーション完了後に非表示
        setTimeout(() => {
            this.modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }
};

// DOMContentLoaded後に初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => GroupTaskLimitModal.init());
} else {
    GroupTaskLimitModal.init();
}

// グローバルスコープに公開（group-task.jsから参照可能にする）
window.GroupTaskLimitModal = GroupTaskLimitModal;
</script>

<style>
/* トランジション用のCSS */
.modal-content {
    transition: opacity 300ms ease-out, transform 300ms ease-out;
}

.modal-overlay {
    transition: opacity 300ms ease-out;
}
</style>
