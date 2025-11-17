/**
 * 管理者通知管理ページのクライアントサイドロジック
 * 
 * - 配信対象選択の表示切替
 * 
 * @module admin-notifications
 */

/**
 * 初期化処理
 */
document.addEventListener('DOMContentLoaded', function() {
    initTargetTypeToggle();
});

/**
 * 配信対象選択の表示切替
 * 
 * target_type（全ユーザー/特定ユーザー/特定グループ）に応じて、
 * 対象選択のセレクトボックスを表示/非表示する。
 */
function initTargetTypeToggle() {
    const targetTypeInputs = document.querySelectorAll('input[name="target_type"]');
    const usersSelection = document.getElementById('users-selection');
    const groupsSelection = document.getElementById('groups-selection');

    if (!targetTypeInputs.length) {
        return; // 作成/編集ページ以外では何もしない
    }

    /**
     * 選択状態に応じて表示を切り替える
     */
    function toggleTargetSelection() {
        const selectedType = document.querySelector('input[name="target_type"]:checked')?.value;

        if (!usersSelection || !groupsSelection) {
            return;
        }

        usersSelection.classList.add('hidden');
        groupsSelection.classList.add('hidden');

        if (selectedType === 'users') {
            usersSelection.classList.remove('hidden');
        } else if (selectedType === 'groups') {
            groupsSelection.classList.remove('hidden');
        }
    }

    // イベントリスナーを登録
    targetTypeInputs.forEach(input => {
        input.addEventListener('change', toggleTargetSelection);
    });

    // 初期表示
    toggleTargetSelection();
}