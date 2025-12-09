/**
 * タスク編集モーダルコントローラー
 * Alpine.jsから移行: Vanilla JavaScript実装
 */
class TaskModalController {
    /**
     * コンストラクタ
     * 指定されたIDのタスク編集モーダルを初期化
     * @param {number} taskId - タスクID
     */
    constructor(taskId) {
        this.taskId = taskId;
        this.modal = null;
        this.overlay = null;
        this.form = null;
        this.isOpen = false;
        
        // フォームフィールド
        this.fields = {
            title: null,
            description: null,
            span: null,
            dueDate: null,
            selectedTags: []
        };
        
        this.init();
    }
    
    /**
     * 初期化処理
     * DOM要素を取得し、イベントリスナー、スパン処理、タグ処理を設定
     */
    init() {
        // DOM要素を取得
        this.modal = document.querySelector(`[data-task-modal="${this.taskId}"]`);
        if (!this.modal) return;
        
        this.overlay = this.modal.querySelector('[data-modal-overlay]');
        this.content = this.modal.querySelector('[data-modal-content]');
        this.form = this.modal.querySelector('form');
        
        // フィールドを取得
        this.fields.title = this.form.querySelector('[name="title"]');
        this.fields.description = this.form.querySelector('[name="description"]');
        this.fields.span = this.form.querySelector('[name="span"]');
        
        this.setupEventListeners();
        this.setupSpanHandling();
        this.setupTagHandling();
    }
    
    /**
     * イベントリスナーの登録
     * 閉じるボタン、オーバーレイクリック、Escapeキー、保存ボタンの各イベントを設定
     */
    setupEventListeners() {
        // 閉じるボタン
        const closeButtons = this.modal.querySelectorAll('[data-close-modal]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });
        
        // オーバーレイクリック
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });
        }
        
        // Escapeキー
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // 保存ボタン
        const submitBtn = this.modal.querySelector('[data-submit-form]');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submit());
        }
    }
    
    /**
     * スパン（短期/中期/長期）に応じた期限フィールドの表示/非表示制御
     * スパン選択に応じて適切な期限入力フィールドを表示
     */
    setupSpanHandling() {
        if (!this.fields.span) return;
        
        const dueDateContainers = {
            short: this.modal.querySelector('[data-due-date-short]'),
            mid: this.modal.querySelector('[data-due-date-mid]'),
            long: this.modal.querySelector('[data-due-date-long]')
        };
        
        const updateDueDateField = () => {
            const spanValue = parseInt(this.fields.span.value);
            
            // すべて非表示
            Object.values(dueDateContainers).forEach(container => {
                if (container) container.classList.add('hidden');
            });
            
            // 選択されたスパンに応じて表示
            if (spanValue === 1 && dueDateContainers.short) {
                dueDateContainers.short.classList.remove('hidden');
            } else if (spanValue === 2 && dueDateContainers.mid) {
                dueDateContainers.mid.classList.remove('hidden');
            } else if (spanValue === 3 && dueDateContainers.long) {
                dueDateContainers.long.classList.remove('hidden');
            }
        };
        
        this.fields.span.addEventListener('change', updateDueDateField);
        updateDueDateField(); // 初期表示
    }
    
    /**
     * タグチェックボックスのUI制御
     * チェック状態に応じてタグチップのスタイルを変更（アクティブ: グラデーション）
     * 検索・展開機能を含む
     */
    setupTagHandling() {
        // タグ検索
        const searchInput = this.form.querySelector(`#tag-search-${this.taskId}`);
        const expandBtn = this.form.querySelector(`#tag-expand-btn-${this.taskId}`);
        const tagList = this.form.querySelector(`#tag-list-${this.taskId}`);
        const selectedTagsContainer = this.form.querySelector(`#selected-tags-${this.taskId}`);
        const tagCount = this.form.querySelector(`[data-tag-count-${this.taskId}]`);
        
        let isExpanded = false;
        
        // 展開ボタンのクリック処理
        if (expandBtn && tagList) {
            expandBtn.addEventListener('click', () => {
                isExpanded = !isExpanded;
                if (isExpanded) {
                    tagList.classList.remove('hidden');
                    expandBtn.querySelector('[data-expand-text]').textContent = 'タグを追加 ▲';
                } else {
                    tagList.classList.add('hidden');
                    expandBtn.querySelector('[data-expand-text]').textContent = 'タグを追加 ▼';
                }
            });
        }
        
        // タグ検索処理
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase();
                const availableTags = tagList?.querySelectorAll('[data-available-tags] .tag-chip');
                let visibleCount = 0;
                
                availableTags?.forEach(tag => {
                    const tagName = tag.getAttribute('data-tag-name')?.toLowerCase() || '';
                    if (tagName.includes(query)) {
                        tag.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        tag.classList.add('hidden');
                    }
                });
                
                // 検索結果なしメッセージ
                const noResults = tagList?.querySelector('[data-no-results]');
                if (noResults) {
                    if (visibleCount === 0) {
                        noResults.classList.remove('hidden');
                    } else {
                        noResults.classList.add('hidden');
                    }
                }
            });
        }
        
        const tagCheckboxes = this.form.querySelectorAll('input[name="tags[]"]');
        
        tagCheckboxes.forEach(checkbox => {
            const label = checkbox.closest('.tag-chip');
            if (!label) return;
            
            // 初期状態の反映
            this.updateTagUI(checkbox, label);
            
            // 変更時の処理
            checkbox.addEventListener('change', () => {
                this.updateTagUI(checkbox, label);
                this.updateTagSelection(checkbox, label);
            });
            
            // ラベルクリックでチェックボックスをトグル
            label.addEventListener('click', (e) => {
                e.preventDefault();
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            });
        });
        
        // タグ選択状態の更新
        this.updateSelectedTagsDisplay();
    }
    
    /**
     * タグ選択状態の更新
     * 選択/解除時に選択済みエリアと未選択エリア間でタグを移動
     */
    updateTagSelection(checkbox, label) {
        const selectedTagsContainer = this.form.querySelector(`#selected-tags-${this.taskId}`);
        const tagList = this.form.querySelector(`#tag-list-${this.taskId}`);
        const tagCount = this.form.querySelector(`[data-tag-count-${this.taskId}]`);
        const selectedTagsArea = selectedTagsContainer?.querySelector('[data-selected-tags]');
        const availableTagsArea = tagList?.querySelector('[data-available-tags]');
        
        if (checkbox.checked) {
            // 選択済みエリアに移動
            if (selectedTagsArea && label.parentElement === availableTagsArea) {
                selectedTagsArea.appendChild(label);
                selectedTagsContainer?.classList.remove('hidden');
            }
        } else {
            // 未選択エリアに戻す
            if (availableTagsArea && label.parentElement === selectedTagsArea) {
                availableTagsArea.appendChild(label);
            }
        }
        
        this.updateSelectedTagsDisplay();
    }
    
    /**
     * 選択済みタグ表示の更新
     * 選択数のカウント表示と選択済みコンテナの表示/非表示を制御
     */
    updateSelectedTagsDisplay() {
        const selectedTagsContainer = this.form.querySelector(`#selected-tags-${this.taskId}`);
        const tagCount = this.form.querySelector(`[data-tag-count-${this.taskId}]`);
        const selectedCheckboxes = this.form.querySelectorAll('input[name="tags[]"]:checked');
        
        // カウント表示更新
        if (tagCount) {
            if (selectedCheckboxes.length > 0) {
                tagCount.textContent = `(${selectedCheckboxes.length})`;
            } else {
                tagCount.textContent = '';
            }
        }
        
        // 選択済みコンテナの表示/非表示
        if (selectedTagsContainer) {
            if (selectedCheckboxes.length > 0) {
                selectedTagsContainer.classList.remove('hidden');
            } else {
                selectedTagsContainer.classList.add('hidden');
            }
        }
    }
    
    /**
     * タグチップのUI更新
     * チェック状態に応じて背景色と文字色を変更
     * @param {HTMLInputElement} checkbox - チェックボックス要素
     * @param {HTMLElement} label - タグチップ要素
     */
    updateTagUI(checkbox, label) {
        if (checkbox.checked) {
            label.classList.add('bg-gradient-to-r', 'from-[#59B9C6]', 'to-purple-600', 'text-white');
            label.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
        } else {
            label.classList.remove('bg-gradient-to-r', 'from-[#59B9C6]', 'to-purple-600', 'text-white');
            label.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
        }
    }
    
    /**
     * モーダルを開く
     * フェードインアニメーションとともにモーダルを表示し、タイトルフィールドにフォーカス
     */
    open() {
        if (!this.modal) return;
        
        this.isOpen = true;
        this.modal.classList.remove('hidden');
        this.overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // アニメーション
        requestAnimationFrame(() => {
            this.overlay?.classList.add('opacity-100');
            this.overlay?.classList.remove('opacity-0');
            this.content?.classList.add('opacity-100', 'scale-100');
            this.content?.classList.remove('opacity-0', 'scale-95');
        });
        
        // フォーカス
        if (this.fields.title) {
            setTimeout(() => this.fields.title.focus(), 100);
        }
    }
    
    /**
     * モーダルを閉じる
     * フェードアウトアニメーション後にモーダルを非表示
     */
    close() {
        if (!this.modal) return;
        
        // アニメーション（閉じる）
        this.overlay?.classList.add('opacity-0');
        this.overlay?.classList.remove('opacity-100');
        this.content?.classList.add('opacity-0', 'scale-95');
        this.content?.classList.remove('opacity-100', 'scale-100');
        
        setTimeout(() => {
            this.modal.classList.add('hidden');
            this.overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            this.isOpen = false;
        }, 200);
    }
    
    /**
     * フォーム送信
     * タイトルの入力チェック後、フォームを送信
     */
    submit() {
        if (!this.form) return;
        
        // バリデーション
        if (!this.fields.title?.value.trim()) {
            if (window.showAlertDialog) {
                window.showAlertDialog('タスク名を入力してください');
            } else {
                alert('タスク名を入力してください');
            }
            this.fields.title?.focus();
            return;
        }
        
        this.form.submit();
    }
}

// グローバルコントローラーマネージャー
window.TaskModalController = {
    instances: new Map(),
    
    /**
     * タスクIDに対応するコントローラーを登録
     * @param {number} taskId - タスクID
     * @returns {TaskModalController} コントローラーインスタンス
     */
    register(taskId) {
        if (!this.instances.has(taskId)) {
            this.instances.set(taskId, new TaskModalController(taskId));
        }
        return this.instances.get(taskId);
    },
    
    /**
     * 指定されたタスクIDのモーダルを開く
     * @param {number} taskId - タスクID
     */
    open(taskId) {
        const controller = this.register(taskId);
        controller.open();
    },
    
    /**
     * 指定されたタスクIDのモーダルを閉じる
     * @param {number} taskId - タスクID
     */
    close(taskId) {
        const controller = this.instances.get(taskId);
        if (controller) {
            controller.close();
        }
    }
};

// DOMContentLoaded時に既存のモーダルを登録
document.addEventListener('DOMContentLoaded', () => {
    const modals = document.querySelectorAll('[data-task-modal]');
    modals.forEach(modal => {
        const taskId = modal.dataset.taskModal;
        if (taskId) {
            window.TaskModalController.register(parseInt(taskId));
        }
    });
});
