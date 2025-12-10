/**
 * グループタスク管理画面 - 一覧画面のJavaScript
 * 
 * 機能:
 * - 削除ボタンのconfirm確認
 * - 削除時のフォーム送信
 */

document.addEventListener('DOMContentLoaded', function() {
    // 削除ボタンのイベントリスナー
    const deleteButtons = document.querySelectorAll('[data-delete-group-task]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const groupTaskId = this.dataset.deleteGroupTask;
            const groupTaskTitle = this.dataset.groupTaskTitle || 'このグループタスク';
            
            // confirm-dialogイベントを発火
            const confirmEvent = new CustomEvent('show-confirm-dialog', {
                detail: {
                    title: 'グループタスクの削除',
                    message: `${groupTaskTitle}を削除してもよろしいですか？\n\n削除すると、このグループに割り当てられた全メンバーのタスクが削除されます。この操作は取り消せません。`,
                    confirmText: '削除する',
                    cancelText: 'キャンセル',
                    confirmButtonClass: 'bg-red-600 hover:bg-red-700',
                    onConfirm: () => {
                        // 削除フォームを送信
                        const form = document.getElementById(`delete-form-${groupTaskId}`);
                        if (form) {
                            form.submit();
                        }
                    }
                }
            });
            
            window.dispatchEvent(confirmEvent);
        });
    });
});
