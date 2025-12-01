{{-- グループマスター削除確認ダイアログ（専用） --}}
<div id="delete-group-master-dialog" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true" aria-labelledby="delete-group-master-dialog-title">
    {{-- オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" id="delete-group-master-dialog-overlay"></div>
    
    {{-- ダイアログコンテナ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            {{-- ダイアログカード --}}
            <div id="delete-group-master-dialog-card" 
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-lg">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-red-500/10 to-orange-500/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-600 to-orange-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 id="delete-group-master-dialog-title" class="text-lg font-bold text-gray-900 dark:text-white">
                            アカウント削除の確認
                        </h3>
                    </div>
                </div>
                
                {{-- メッセージ --}}
                <div class="px-6 py-6 space-y-4">
                    {{-- 警告メッセージ --}}
                    <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                                    以下の操作が実行されます：
                                </p>
                                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                                    <li><strong>全メンバー（<span id="dialog-members-count">0</span>名）が削除</strong>されます</li>
                                    <li id="dialog-subscription-warning" class="hidden"><strong>サブスクリプションが即時解約</strong>されます</li>
                                    <li>グループのすべてのデータが削除されます</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 注意書き --}}
                    <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">
                                    💡 メンバーとサブスクリプションを維持する方法
                                </p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    <strong>マスター権限を他のメンバーに譲渡</strong>することで、全メンバーとサブスクリプション契約を継続したまま、あなたのアカウントのみを削除できます。
                                </p>
                                <button type="button"
                                   id="transfer-master-btn"
                                   class="inline-flex items-center gap-2 mt-3 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 rounded-lg shadow-md hover:shadow-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    マスター権限を譲渡する
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 確認チェックボックス --}}
                    <div class="space-y-3 pt-2">
                        <label class="flex items-start gap-3 p-3 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-600 cursor-pointer transition">
                            <input type="checkbox" 
                                   id="confirm-members-delete"
                                   class="mt-1 w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>全メンバー（<span class="dialog-members-count">0</span>名）の削除</strong>に同意します
                            </span>
                        </label>

                        <label id="confirm-subscription-container" class="hidden flex items-start gap-3 p-3 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-600 cursor-pointer transition">
                            <input type="checkbox" 
                                   id="confirm-subscription-cancel"
                                   class="mt-1 w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>サブスクリプションの即時解約</strong>に同意します
                            </span>
                        </label>
                    </div>

                    {{-- パスワード入力 --}}
                    <div class="pt-2">
                        <label for="delete-group-master-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            パスワードを入力してください
                        </label>
                        <input type="password"
                               id="delete-group-master-password"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                               placeholder="パスワード">
                    </div>
                </div>
                
                {{-- ボタン --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
                    <button type="button" 
                            id="delete-group-master-dialog-cancel-btn"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition shadow-sm">
                        キャンセル
                    </button>
                    <button type="button" 
                            id="delete-group-master-dialog-delete-btn"
                            disabled
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed transition shadow-lg">
                        グループごと削除
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * グループマスター削除確認ダイアログ
 */
(function() {
    'use strict';
    
    const dialog = document.getElementById('delete-group-master-dialog');
    const overlay = document.getElementById('delete-group-master-dialog-overlay');
    const card = document.getElementById('delete-group-master-dialog-card');
    const cancelBtn = document.getElementById('delete-group-master-dialog-cancel-btn');
    const deleteBtn = document.getElementById('delete-group-master-dialog-delete-btn');
    const passwordInput = document.getElementById('delete-group-master-password');
    
    const membersCheckbox = document.getElementById('confirm-members-delete');
    const subscriptionCheckbox = document.getElementById('confirm-subscription-cancel');
    const subscriptionContainer = document.getElementById('confirm-subscription-container');
    const subscriptionWarning = document.getElementById('dialog-subscription-warning');
    
    let currentFormId = null;
    let hasSubscription = false;
    
    /**
     * ダイアログを表示
     * @param {number} membersCount - メンバー数
     * @param {boolean} hasActiveSubscription - サブスクリプション契約中か
     * @param {string} formId - 送信するフォームのID
     */
    window.showDeleteGroupMasterDialog = function(membersCount, hasActiveSubscription, formId) {
        if (!dialog) {
            console.error('[DeleteGroupMasterDialog] Dialog element not found');
            return;
        }
        
        currentFormId = formId;
        hasSubscription = hasActiveSubscription;
        
        // メンバー数を表示
        document.querySelectorAll('.dialog-members-count, #dialog-members-count').forEach(el => {
            el.textContent = membersCount;
        });
        
        // サブスクリプション関連の表示制御
        if (hasActiveSubscription) {
            subscriptionContainer.classList.remove('hidden');
            subscriptionWarning.classList.remove('hidden');
        } else {
            subscriptionContainer.classList.add('hidden');
            subscriptionWarning.classList.add('hidden');
        }
        
        // 状態リセット
        membersCheckbox.checked = false;
        if (subscriptionCheckbox) subscriptionCheckbox.checked = false;
        passwordInput.value = '';
        updateDeleteButtonState();
        
        // 表示アニメーション
        dialog.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
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
            currentFormId = null;
            hasSubscription = false;
        }, 300);
    }
    
    /**
     * 削除ボタンの有効/無効を更新
     */
    function updateDeleteButtonState() {
        if (!deleteBtn) return;
        
        const membersChecked = membersCheckbox?.checked || false;
        const subscriptionChecked = hasSubscription ? (subscriptionCheckbox?.checked || false) : true;
        const passwordEntered = passwordInput?.value.trim().length > 0;
        
        const allConditionsMet = membersChecked && subscriptionChecked && passwordEntered;
        
        deleteBtn.disabled = !allConditionsMet;
    }
    
    /**
     * 削除実行
     */
    function handleDelete() {
        if (!currentFormId) {
            console.error('[DeleteGroupMasterDialog] No form ID specified');
            return;
        }
        
        const form = document.getElementById(currentFormId);
        if (!form) {
            console.error('[DeleteGroupMasterDialog] Form not found:', currentFormId);
            return;
        }
        
        // パスワードをフォームに設定
        const passwordField = form.querySelector('#group-delete-password');
        if (passwordField) {
            passwordField.value = passwordInput.value;
        }
        
        closeDialog();
        form.submit();
    }
    
    // イベントリスナー登録
    const transferBtn = document.getElementById('transfer-master-btn');
    
    cancelBtn?.addEventListener('click', closeDialog);
    deleteBtn?.addEventListener('click', handleDelete);
    overlay?.addEventListener('click', closeDialog);
    
    // マスター譲渡ボタン: モーダルを閉じてから遷移
    transferBtn?.addEventListener('click', () => {
        closeDialog();
        // モーダルのクローズアニメーション完了後に遷移
        setTimeout(() => {
            window.location.href = '{{ route('group.edit') }}#member-list';
        }, 350);
    });
    
    membersCheckbox?.addEventListener('change', updateDeleteButtonState);
    subscriptionCheckbox?.addEventListener('change', updateDeleteButtonState);
    passwordInput?.addEventListener('input', updateDeleteButtonState);
    
    // Escapeキーでキャンセル
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
            closeDialog();
        }
    });
    
    console.log('[DeleteGroupMasterDialog] Initialized');
})();
</script>
@endpush
