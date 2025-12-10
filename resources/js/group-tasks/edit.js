/**
 * グループタスク管理画面 - 編集画面のJavaScript
 * 
 * 機能:
 * - タグの追加・削除（動的入力フィールド）
 * - フォームバリデーション
 */

document.addEventListener('DOMContentLoaded', function() {
    // タグ追加ボタン
    const addTagButton = document.getElementById('add-tag-button');
    const tagsContainer = document.getElementById('tags-container');
    
    if (addTagButton && tagsContainer) {
        addTagButton.addEventListener('click', function() {
            addTagInput();
        });
        
        // 既存のタグ削除ボタンにイベントリスナーを設定
        attachRemoveTagListeners();
    }
    
    /**
     * 新しいタグ入力フィールドを追加
     */
    function addTagInput(value = '') {
        const tagDiv = document.createElement('div');
        tagDiv.className = 'flex gap-2';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'tags[]';
        input.value = value;
        input.placeholder = 'タグ名を入力';
        input.className = 'flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent';
        input.maxLength = 50;
        
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition';
        removeButton.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        `;
        removeButton.addEventListener('click', function() {
            tagDiv.remove();
        });
        
        tagDiv.appendChild(input);
        tagDiv.appendChild(removeButton);
        tagsContainer.appendChild(tagDiv);
        
        // 新しく追加した入力フィールドにフォーカス
        input.focus();
    }
    
    /**
     * 既存のタグ削除ボタンにイベントリスナーを設定
     */
    function attachRemoveTagListeners() {
        const removeButtons = tagsContainer.querySelectorAll('[data-remove-tag]');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.flex').remove();
            });
        });
    }
    
    // フォーム送信時のバリデーション
    const form = document.querySelector('form[action*="group-tasks"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            // 空のタグ入力を削除
            const tagInputs = tagsContainer.querySelectorAll('input[name="tags[]"]');
            tagInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.closest('.flex').remove();
                }
            });
            
            // 基本的なバリデーション
            const title = document.getElementById('title');
            if (title && !title.value.trim()) {
                e.preventDefault();
                alert('タイトルを入力してください。');
                title.focus();
                return false;
            }
        });
    }
});
