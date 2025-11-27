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
     */
    setupTagHandling() {
        const tagCheckboxes = this.form.querySelectorAll('input[name="tags[]"]');
        
        tagCheckboxes.forEach(checkbox => {
            const label = checkbox.closest('.tag-chip');
            if (!label) return;
            
            // 初期状態の反映
            this.updateTagUI(checkbox, label);
            
            // 変更時の処理
            checkbox.addEventListener('change', () => {
                this.updateTagUI(checkbox, label);
            });
        });
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
