/**
 * グループタスク管理画面 - 編集画面のJavaScript
 * 
 * 機能:
 * - タグの追加・削除（動的入力フィールド）
 * - フォームバリデーション
 * - 期間（span）選択による期限フィールドの切り替え
 */

document.addEventListener('DOMContentLoaded', function() {
    // 期限フィールドの切り替え設定
    initDueDateFieldSwitcher();
    
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
     * 期限フィールドの切り替え機能を初期化
     */
    function initDueDateFieldSwitcher() {
        const spanSelect = document.getElementById('span');
        const dueDateContainers = {
            short: document.getElementById('due-date-short-container'),
            mid: document.getElementById('due-date-mid-container'),
            long: document.getElementById('due-date-long-container')
        };
        const dueDateInputs = {
            short: document.getElementById('due_date_short'),
            mid: document.getElementById('due_date_mid'),
            long: document.getElementById('due_date_long')
        };
        
        if (!spanSelect) return;
        
        // span選択時のイベントリスナー
        spanSelect.addEventListener('change', function() {
            const span = parseInt(this.value);
            switchDueDateField(span, dueDateContainers, dueDateInputs);
        });
    }
    
    /**
     * 期限フィールドを切り替える
     * @param {number} span - スパン値（1: 短期, 2: 中期, 3: 長期）
     * @param {Object} containers - 期限コンテナのDOM要素
     * @param {Object} inputs - 期限入力フィールドのDOM要素
     */
    function switchDueDateField(span, containers, inputs) {
        // すべてのコンテナを非表示にし、入力を無効化
        Object.values(containers).forEach(container => {
            if (container) {
                container.style.display = 'none';
            }
        });
        
        Object.values(inputs).forEach(input => {
            if (input) {
                input.disabled = true;
            }
        });
        
        // 選択されたspanに応じて表示とデフォルト値を設定
        let targetContainer = null;
        let targetInput = null;
        let defaultValue = '';
        
        if (span === 1) {
            // 短期: 日付選択
            targetContainer = containers.short;
            targetInput = inputs.short;
            defaultValue = new Date().toISOString().split('T')[0];
        } else if (span === 3) {
            // 中期: 年選択
            targetContainer = containers.mid;
            targetInput = inputs.mid;
            defaultValue = new Date().getFullYear().toString();
        } else if (span === 6) {
            // 長期: テキスト入力
            targetContainer = containers.long;
            targetInput = inputs.long;
            defaultValue = '';
        }
        
        if (targetContainer && targetInput) {
            targetContainer.style.display = 'block';
            targetInput.disabled = false;
            
            // 値が空の場合のみデフォルト値を設定
            if (!targetInput.value) {
                targetInput.value = defaultValue;
            }
        }
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
