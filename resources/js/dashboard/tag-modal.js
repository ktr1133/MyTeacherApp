/**
 * タグモーダル制御（Vanilla JS）
 * 
 * バケツクリック → モーダル表示
 * Alpine.jsの@openイベントをVanilla JSで再実装
 */
(function() {
    'use strict';
    
    /**
     * タグモーダルを開く
     * @param {string} modalId - モーダルID（例: 'todo-5', 'completed-0'）
     */
    window.openTagModal = function(modalId) {
        // Alpine.jsのx-showディレクティブに依存しているため、
        // Alpine.jsが利用可能な場合はカスタムイベントをディスパッチ
        if (window.Alpine) {
            window.dispatchEvent(new CustomEvent(`open-tag-modal-${modalId}`));
        } else {
            console.error('[TagModal] Alpine.js not available');
        }
    };
    
    console.log('[TagModal] Initialized');
})();
