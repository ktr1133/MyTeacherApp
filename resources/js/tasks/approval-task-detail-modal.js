/**
 * 承認タスク詳細モーダルコントローラー
 * Alpine.jsから移行: Vanilla JavaScript実装
 */
class ApprovalTaskDetailModalController {
    /**
     * コンストラクタ
     * 指定されたIDの承認タスク詳細モーダルを初期化
     * @param {number} taskId - タスクID
     */
    constructor(taskId) {
        this.taskId = taskId;
        this.modal = null;
        this.overlay = null;
        this.content = null;
        this.isOpen = false;
        
        // フォーム状態
        this.descriptionTextarea = null;
        this.description = '';
        this.originalDescription = '';
        this.isDirty = false;
        this.isSubmitting = false;
        
        this.init();
    }
    
    /**
     * 初期化処理
     * DOM要素を取得し、初期値を保存、イベントリスナーを設定
     */
    init() {
        // DOM要素を取得
        this.modal = document.querySelector(`[data-approval-modal="${this.taskId}"]`);
        if (!this.modal) return;
        
        this.overlay = this.modal.querySelector('[data-modal-overlay]');
        this.content = this.modal.querySelector('[data-modal-content]');
        this.descriptionTextarea = this.modal.querySelector('[data-description-field]');
        this.saveForm = this.modal.querySelector('[data-save-form]');
        
        // 初期値を保存
        if (this.descriptionTextarea) {
            this.originalDescription = this.descriptionTextarea.value;
            this.description = this.originalDescription;
        }
        
        this.setupEventListeners();
    }
    
    /**
     * イベントリスナーの登録
     * 閉じるボタン、オーバーレイクリック、Escapeキー、テキストエリア入力監視、保存ボタン、
     * フォーム送信の各イベントを設定
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
        
        // テキストエリアの入力監視
        if (this.descriptionTextarea) {
            this.descriptionTextarea.addEventListener('input', () => {
                this.description = this.descriptionTextarea.value;
                this.isDirty = this.description !== this.originalDescription;
                this.updateUI();
            });
        }
        
        // 保存ボタン
        const saveBtn = this.modal.querySelector('[data-save-btn]');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.save());
        }
        
        // フォーム送信
        if (this.saveForm) {
            this.saveForm.addEventListener('submit', () => {
                this.isSubmitting = true;
                this.updateUI();
            });
        }
    }
    
    /**
     * UI全体の更新
     * 編集中インジケーター、文字数カウント、保存ボタンの状態（無効/有効）、
     * 保存中テキストを更新
     */
    updateUI() {
        // 編集中インジケーター
        const dirtyIndicator = this.modal.querySelector('[data-dirty-indicator]');
        if (dirtyIndicator) {
            if (this.isDirty) {
                dirtyIndicator.classList.remove('hidden');
            } else {
                dirtyIndicator.classList.add('hidden');
            }
        }
        
        // 文字数カウント
        const charCount = this.modal.querySelector('[data-char-count]');
        if (charCount) {
            const count = this.description ? this.description.length : 0;
            charCount.textContent = `${count}/500文字`;
        }
        
        // 保存ボタンの状態
        const saveBtn = this.modal.querySelector('[data-save-btn]');
        if (saveBtn) {
            saveBtn.disabled = !this.isDirty || this.isSubmitting;
            
            if (!this.isDirty || this.isSubmitting) {
                saveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                saveBtn.classList.remove('approval-btn-save');
            } else {
                saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                saveBtn.classList.add('approval-btn-save');
            }
        }
        
        // 保存ボタンのテキスト
        const saveBtnText = this.modal.querySelector('[data-save-btn-text]');
        const savingText = this.modal.querySelector('[data-saving-text]');
        if (saveBtnText && savingText) {
            if (this.isSubmitting) {
                saveBtnText.classList.add('hidden');
                savingText.classList.remove('hidden');
            } else {
                saveBtnText.classList.remove('hidden');
                savingText.classList.add('hidden');
            }
        }
    }
    
    /**
     * フォーム保存
     * 変更があり、送信中でない場合にhidden inputを更新してフォームを送信
     */
    save() {
        if (!this.isDirty || this.isSubmitting || !this.saveForm) return;
        
        // hidden inputを更新
        const hiddenInput = this.saveForm.querySelector('input[name="description"]');
        if (hiddenInput) {
            hiddenInput.value = this.description;
        }
        
        this.isSubmitting = true;
        this.updateUI();
        this.saveForm.submit();
    }
    
    /**
     * モーダルを開く
     * フェードインアニメーションとともにモーダルを表示し、値をリセット、テキストエリアにフォーカス
     */
    open() {
        if (!this.modal) return;
        
        this.isOpen = true;
        
        // 値をリセット
        this.description = this.originalDescription;
        if (this.descriptionTextarea) {
            this.descriptionTextarea.value = this.originalDescription;
        }
        this.isDirty = false;
        this.isSubmitting = false;
        this.updateUI();
        
        // モーダル表示
        this.modal.classList.remove('hidden');
        this.overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // アニメーション
        requestAnimationFrame(() => {
            this.overlay?.classList.add('opacity-100');
            this.overlay?.classList.remove('opacity-0');
            this.content?.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            this.content?.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
        });
        
        // フォーカス
        if (this.descriptionTextarea) {
            setTimeout(() => this.descriptionTextarea.focus(), 100);
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
        this.content?.classList.add('opacity-0', 'translate-y-4', 'scale-95');
        this.content?.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        
        setTimeout(() => {
            this.modal.classList.add('hidden');
            this.overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            this.isOpen = false;
        }, 200);
    }
}

// グローバルコントローラーマネージャー
window.ApprovalTaskDetailModalController = {
    instances: new Map(),
    
    /**
     * タスクIDに対応するコントローラーを登録
     * @param {number} taskId - タスクID
     * @returns {ApprovalTaskDetailModalController} コントローラーインスタンス
     */
    register(taskId) {
        if (!this.instances.has(taskId)) {
            this.instances.set(taskId, new ApprovalTaskDetailModalController(taskId));
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
    const modals = document.querySelectorAll('[data-approval-modal]');
    modals.forEach(modal => {
        const taskId = modal.dataset.approvalModal;
        if (taskId) {
            window.ApprovalTaskDetailModalController.register(parseInt(taskId));
        }
    });
});
