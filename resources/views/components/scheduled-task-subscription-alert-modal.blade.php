{{-- グループタスク自動作成設定用サブスクリプションアラートモーダル（Vanilla JS） --}}
<div id="scheduled-task-subscription-alert-modal"
     class="fixed inset-0 z-[9999] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="scheduled-task-alert-title">
    
    {{-- 背景オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity modal-overlay">
    </div>

    {{-- モーダルコンテンツ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-md modal-content"
                 style="opacity: 0; transform: translateY(1rem) scale(0.95);">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-indigo-500/10 via-blue-500/10 to-purple-500/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 id="scheduled-task-alert-title" class="text-lg font-bold text-gray-900 dark:text-white">
                            サブスクリプション限定機能
                        </h3>
                    </div>
                </div>

                {{-- メッセージ --}}
                <div class="px-6 py-6">
                    <p class="text-base text-gray-700 dark:text-gray-300 mb-3">
                        グループタスク自動作成機能はサブスクリプションプランでご利用いただけます。
                    </p>
                    <div class="bg-gradient-to-r from-indigo-500/10 via-blue-500/10 to-purple-500/10 rounded-lg p-4 border border-indigo-500/30 dark:border-indigo-600/30">
                        <p class="font-semibold text-indigo-600 dark:text-indigo-400 mb-2">✨ サブスク限定機能</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                            <li>毎日・毎週・毎月の定期タスク自動生成</li>
                            <li>祝日対応・担当者割当機能</li>
                            <li>一度設定すれば継続的に自動作成</li>
                        </ul>
                    </div>

                    {{-- プラン情報 --}}
                    <div class="mt-4 bg-gradient-to-r from-indigo-500/10 via-blue-500/10 to-purple-500/10 rounded-lg p-4 border border-indigo-500/30 dark:border-indigo-600/30">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                            🎉 お得なサブスクリプションプラン
                        </p>
                        <p class="text-2xl font-bold bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 bg-clip-text text-transparent">
                            月額 ¥500〜
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            全機能が使い放題 + 月次レポート自動生成
                        </p>
                    </div>
                </div>

                {{-- ボタン --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
                    <button type="button"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition shadow-sm"
                            onclick="ScheduledTaskSubscriptionModal.hide()">
                        キャンセル
                    </button>
                    <a href="{{ route('subscriptions.index') }}"
                       class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 hover:from-indigo-700 hover:via-blue-700 hover:to-purple-700 transition shadow-lg hover:shadow-xl">
                        プランを見る
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * グループタスク自動作成設定用サブスクリプションアラートモーダル管理
 */
const ScheduledTaskSubscriptionModal = {
    modal: null,
    overlay: null,
    content: null,

    /**
     * 初期化
     */
    init() {
        this.modal = document.getElementById('scheduled-task-subscription-alert-modal');
        if (!this.modal) return;

        this.overlay = this.modal.querySelector('.modal-overlay');
        this.content = this.modal.querySelector('.modal-content');

        // オーバーレイクリックで閉じる
        this.overlay?.addEventListener('click', () => this.hide());

        // Escapeキーで閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.hide();
            }
        });
    },

    /**
     * モーダルを表示
     */
    show() {
        if (!this.modal) return;

        // モーダル表示
        this.modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // アニメーション
        requestAnimationFrame(() => {
            this.overlay?.classList.add('opacity-100');
            if (this.content) {
                this.content.style.opacity = '1';
                this.content.style.transform = 'translateY(0) scale(1)';
            }
        });
    },

    /**
     * モーダルを非表示
     */
    hide() {
        if (!this.modal) return;

        // アニメーション（逆再生）
        this.overlay?.classList.remove('opacity-100');
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

// 初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ScheduledTaskSubscriptionModal.init());
} else {
    ScheduledTaskSubscriptionModal.init();
}
</script>
