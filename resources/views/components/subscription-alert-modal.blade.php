{{-- サブスクリプション促進アラートモーダル（Vanilla JS） --}}
<div id="subscription-alert-modal"
     class="fixed inset-0 z-[9999] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="subscription-alert-title"
     data-show="{{ $show ?? false }}"
     data-feature="{{ $feature ?? '' }}">
    
    {{-- 背景オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity modal-overlay">
    </div>

    {{-- モーダルコンテンツ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-md modal-content"
                 style="opacity: 0; transform: translateY(1rem) scale(0.95);">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 id="subscription-alert-title" class="text-lg font-bold text-gray-900 dark:text-white">
                            サブスクリプション限定機能
                        </h3>
                    </div>
                </div>

                {{-- メッセージ --}}
                <div class="px-6 py-6">
                    {{-- 機能別メッセージ --}}
                    <div data-feature-type="period" class="hidden">
                        <p class="text-base text-gray-700 dark:text-gray-300 mb-3">
                            月間・年間の実績表示はサブスクリプションプランでご利用いただけます。
                        </p>
                        <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 rounded-lg p-4 border border-[#59B9C6]/30 dark:border-purple-600/30">
                            <p class="font-semibold text-[#59B9C6] dark:text-[#59B9C6] mb-2">✨ サブスク限定機能</p>
                            <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                <li>月間・年間の実績グラフ</li>
                                <li>長期的な進捗トレンド分析</li>
                                <li>詳細な統計データ</li>
                            </ul>
                        </div>
                    </div>

                    <div data-feature-type="member" class="hidden">
                        <p class="text-base text-gray-700 dark:text-gray-300 mb-3">
                            個人別実績表示はサブスクリプションプランでご利用いただけます。
                        </p>
                        <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 rounded-lg p-4 border border-[#59B9C6]/30 dark:border-purple-600/30">
                            <p class="font-semibold text-[#59B9C6] dark:text-[#59B9C6] mb-2">✨ サブスク限定機能</p>
                            <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                <li>メンバー個別の実績表示</li>
                                <li>詳細なタスク達成状況</li>
                                <li>個人別グラフとレポート</li>
                            </ul>
                        </div>
                    </div>

                    <div data-feature-type="navigation" class="hidden">
                        <p class="text-base text-gray-700 dark:text-gray-300 mb-3">
                            過去期間の実績閲覧はサブスクリプションプランでご利用いただけます。
                        </p>
                        <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 rounded-lg p-4 border border-[#59B9C6]/30 dark:border-purple-600/30">
                            <p class="font-semibold text-[#59B9C6] dark:text-[#59B9C6] mb-2">✨ サブスク限定機能</p>
                            <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 text-sm">
                                <li>過去の実績データ閲覧</li>
                                <li>履歴トレンド分析</li>
                                <li>月次レポート生成</li>
                            </ul>
                        </div>
                    </div>

                    {{-- プラン情報 --}}
                    <div class="mt-4 bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 rounded-lg p-4 border border-[#59B9C6]/30 dark:border-purple-600/30">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                            🎉 お得なサブスクリプションプラン
                        </p>
                        <p class="text-2xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
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
                            onclick="SubscriptionAlertModal.hide()">
                        キャンセル
                    </button>
                    <a href="{{ route('subscriptions.index') }}"
                       class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA5B2] hover:to-purple-700 transition shadow-lg">
                        プランを見る
                    </a>
                </div>
        </div>
    </div>
</div>

<script>
/**
 * サブスクリプションアラートモーダル管理
 * Vanilla JS実装（Alpine.js不使用）
 */
const SubscriptionAlertModal = {
    modal: null,
    overlay: null,
    content: null,
    
    /**
     * 初期化
     */
    init() {
        this.modal = document.getElementById('subscription-alert-modal');
        if (!this.modal) return;
        
        this.overlay = this.modal.querySelector('.modal-overlay');
        this.content = this.modal.querySelector('.modal-content');
        
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
        
        // 初期表示判定
        if (this.modal.dataset.show === 'true') {
            const feature = this.modal.dataset.feature;
            this.show(feature);
        }
    },
    
    /**
     * モーダル表示
     * @param {string} feature - 機能タイプ（period, member, navigation）
     */
    show(feature = '') {
        if (!this.modal) {
            return;
        }
        
        // 機能別メッセージの表示制御
        const featureElements = this.modal.querySelectorAll('[data-feature-type]');
        featureElements.forEach(el => {
            if (el.dataset.featureType === feature) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
        
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
    document.addEventListener('DOMContentLoaded', () => SubscriptionAlertModal.init());
} else {
    SubscriptionAlertModal.init();
}
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
