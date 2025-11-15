/**
 * タスク検索結果画面用のスクリプト
 * タスクカードクリック時にモーダルを開く
 */

document.addEventListener('DOMContentLoaded', function() {
    // タスクカードのクリックイベントを設定
    initializeTaskCardClickHandlers();
});

/**
 * タスクカードのクリックイベントハンドラーを初期化
 */
function initializeTaskCardClickHandlers() {
    // .task-card要素を全て取得
    const taskCards = document.querySelectorAll('.task-card');

    taskCards.forEach(card => {
        // data-task-id属性からタスクIDを取得
        const taskId = card.dataset.taskId;
        
        if (!taskId) {
            console.warn('[Search Tasks] Task card missing data-task-id:', card);
            return;
        }
        
        // クリックイベントを追加
        card.addEventListener('click', function(event) {
            // 内部のボタンやリンクのクリックは除外
            if (event.target.closest('button') || event.target.closest('a')) {
                return;
            }
            
            console.log('[Search Tasks] Task card clicked:', taskId);
            openTaskModal(taskId);
        });
        
        // マウスカーソルをポインターに変更
        card.style.cursor = 'pointer';
    });
}

/**
 * タスク詳細モーダルを開く
 * 
 * @param {number|string} taskId タスクID
 */
function openTaskModal(taskId) {
    // Alpine.jsのカスタムイベントを発火
    const eventName = `open-task-modal-${taskId}`;
    window.dispatchEvent(new CustomEvent(eventName));
}