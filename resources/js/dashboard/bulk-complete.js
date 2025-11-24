/**
 * タスク一括完了機能（Vanilla JS）
 * 
 * ボタンクリック → 確認ダイアログ → API呼び出し → 成功時リロード
 */
(function() {
    'use strict';
    
    /**
     * 一括完了ボタンのイベントリスナーを登録
     */
    function initBulkCompleteButtons() {
        const buttons = document.querySelectorAll('.bulk-complete-btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', handleBulkComplete);
        });
        
        console.log(`[BulkComplete] Initialized ${buttons.length} buttons`);
    }
    
    /**
     * 一括完了ボタンクリックハンドラー
     * @param {Event} event - クリックイベント
     */
    function handleBulkComplete(event) {
        event.stopPropagation(); // バケツクリックイベント伝播を防止
        
        const button = event.currentTarget;
        const hasGroupTask = button.dataset.hasGroupTask === 'true';
        
        // グループタスクが含まれている場合はアラート表示して処理中断
        if (hasGroupTask) {
            if (typeof window.showAlertDialog === 'function') {
                window.showAlertDialog(
                    'このタグにはグループタスクが含まれているため、一括操作できません。\nグループタスクは個別に操作してください。',
                    '一括操作不可'
                );
            } else {
                alert('このタグにはグループタスクが含まれているため、一括操作できません。\nグループタスクは個別に操作してください。');
            }
            return;
        }
        
        const taskIds = button.dataset.bucketTasks?.split(',').filter(Boolean) || [];
        const bucketName = button.dataset.bucketName || 'このタグ';
        const isCompleted = button.dataset.isCompleted === 'true';
        
        if (taskIds.length === 0) {
            console.warn('[BulkComplete] No tasks found');
            return;
        }
        
        const action = isCompleted ? '完了' : '未完了に戻す';
        const message = `「${bucketName}」のタスク${taskIds.length}件を${action}にしますか？`;
        
        // 確認ダイアログ表示
        if (typeof window.showConfirmDialog !== 'function') {
            console.error('[BulkComplete] Confirm dialog not available');
            alert(message);
            if (confirm(message)) {
                executeBulkComplete(taskIds, isCompleted);
            }
            return;
        }
        
        window.showConfirmDialog(
            message,
            () => executeBulkComplete(taskIds, isCompleted)
        );
    }
    
    /**
     * 一括完了APIを実行
     * @param {Array<string>} taskIds - タスクID配列
     * @param {boolean} isCompleted - 完了状態
     */
    async function executeBulkComplete(taskIds, isCompleted) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        if (!csrfToken) {
            console.error('[BulkComplete] CSRF token not found');
            alert('認証エラーが発生しました。ページを再読み込みしてください。');
            return;
        }
        
        try {
            const response = await fetch('/tasks/bulk-complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    task_ids: taskIds.map(id => parseInt(id, 10)),
                    is_completed: isCompleted,
                }),
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }
            
            const data = await response.json();
            console.log('[BulkComplete] Success:', data);
            
            // 成功メッセージをセッションストレージに保存
            if (data.message) {
                sessionStorage.setItem('flash_success', data.message);
            }
            
            // アバターイベントをセッションストレージに保存（リロード後に発火）
            if (data.avatar_event) {
                console.log('[BulkComplete] Saving avatar event for after reload:', data.avatar_event);
                sessionStorage.setItem('pending_avatar_event', data.avatar_event);
            }
            
            // ページをリロード
            console.log('[BulkComplete] Reloading page...');
            window.location.reload();
            
        } catch (error) {
            console.error('[BulkComplete] Error:', error);
            alert(`タスクの一括更新に失敗しました: ${error.message}`);
        }
    }
    
    /**
     * リロード後に保留中のアバターイベントを発火
     */
    function checkPendingAvatarEvent() {
        const pendingEvent = sessionStorage.getItem('pending_avatar_event');
        
        if (pendingEvent) {
            console.log('[BulkComplete] Found pending avatar event:', pendingEvent);
            
            // セッションストレージからクリア（1回のみ発火）
            sessionStorage.removeItem('pending_avatar_event');
            
            // アバターイベント発火（少し遅延させて確実に発火）
            setTimeout(() => {
                if (typeof window.dispatchAvatarEvent === 'function') {
                    console.log('[BulkComplete] Dispatching saved avatar event:', pendingEvent);
                    window.dispatchAvatarEvent(pendingEvent);
                } else {
                    console.warn('[BulkComplete] dispatchAvatarEvent not available yet');
                }
            }, 500);
        }
    }
    
    // DOMContentLoaded後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initBulkCompleteButtons();
            checkPendingAvatarEvent();
        });
    } else {
        initBulkCompleteButtons();
        checkPendingAvatarEvent();
    }
})();
