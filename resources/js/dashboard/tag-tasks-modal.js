/**
 * タグ別タスク表示モーダルコントローラー
 * Alpine.jsから移行: Vanilla JavaScript実装
 */
class TagTasksModalController {
    /**
     * コンストラクタ
     * 指定されたIDのタグ別タスク表示モーダルを初期化
     * @param {number} tagId - タグID
     */
    constructor(tagId) {
        this.tagId = tagId;
        this.modal = null;
        this.overlay = null;
        this.content = null;
        this.isOpen = false;
        this.spanFilter = 'all';
        this.allTasks = [];
        
        this.init();
    }
    
    /**
     * 初期化処理
     * DOM要素を取得し、タスクデータをパース、イベントリスナーとタスクカウントを設定
     */
    init() {
        // DOM要素を取得
        this.modal = document.querySelector(`[data-tag-tasks-modal="${this.tagId}"]`);
        if (!this.modal) return;
        
        this.overlay = this.modal.querySelector('[data-modal-overlay]');
        this.content = this.modal.querySelector('[data-modal-content]');
        
        // タスクデータを取得
        const tasksData = this.modal.dataset.tasks;
        if (tasksData) {
            try {
                this.allTasks = JSON.parse(tasksData);
            } catch (e) {
                console.error('Failed to parse tasks data:', e);
                this.allTasks = [];
            }
        }
        
        this.setupEventListeners();
        this.updateTaskCount();
    }
    
    /**
     * イベントリスナーの登録
     * 閉じるボタン、オーバーレイクリック、Escapeキー、スパンフィルターボタンの各イベントを設定
     */
    setupEventListeners() {
        // 閉じるボタン
        const closeBtn = this.modal.querySelector('[data-close-modal]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }
        
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
        
        // スパンフィルターボタン
        const filterButtons = this.modal.querySelectorAll('[data-span-filter]');
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.setSpanFilter(btn.dataset.spanFilter);
            });
        });
    }
    
    /**
     * スパンフィルターを設定
     * 指定されたスパン（all/1/2/3）でタスクをフィルタリングし、ボタンのアクティブ状態を更新
     * @param {string} filter - フィルター値（'all', '1', '2', '3'）
     */
    setSpanFilter(filter) {
        this.spanFilter = filter;
        
        // フィルターボタンの状態更新
        const filterButtons = this.modal.querySelectorAll('[data-span-filter]');
        filterButtons.forEach(btn => {
            const isActive = btn.dataset.spanFilter === filter;
            btn.setAttribute('aria-pressed', isActive);
            
            if (isActive) {
                btn.classList.add('tag-modal-filter-active');
                btn.classList.remove('tag-modal-filter-inactive');
            } else {
                btn.classList.remove('tag-modal-filter-active');
                btn.classList.add('tag-modal-filter-inactive');
            }
        });
        
        // タスクカードの表示/非表示
        this.filterTasks();
        this.updateTaskCount();
    }
    
    /**
     * タスクカードのフィルタリング
     * 現在のスパンフィルターに基づいてタスクカードを表示/非表示し、フェードインアニメーションを適用
     */
    filterTasks() {
        const taskCards = this.modal.querySelectorAll('[data-task-card]');
        
        taskCards.forEach(card => {
            const taskSpan = card.dataset.taskSpan;
            const shouldShow = this.spanFilter === 'all' || taskSpan === this.spanFilter;
            
            if (shouldShow) {
                card.classList.remove('hidden');
                // アニメーション
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                requestAnimationFrame(() => {
                    card.style.transition = 'opacity 200ms, transform 200ms';
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                });
            } else {
                card.classList.add('hidden');
            }
        });
    }
    
    /**
     * タスクカウントの更新
     * 現在のフィルターに応じたタスク数と各フィルターボタンのカウントを表示
     */
    updateTaskCount() {
        const countElement = this.modal.querySelector('[data-task-count]');
        if (!countElement) return;
        
        const filteredCount = this.getFilteredTasksCount();
        countElement.textContent = filteredCount;
        
        // 各フィルターボタンのカウント更新
        const filterButtons = this.modal.querySelectorAll('[data-span-filter]');
        filterButtons.forEach(btn => {
            const countEl = btn.querySelector('[data-filter-count]');
            if (!countEl) return;
            
            const filter = btn.dataset.spanFilter;
            let count;
            
            if (filter === 'all') {
                count = this.allTasks.length;
            } else {
                count = this.allTasks.filter(t => t.span == filter).length;
            }
            
            countEl.textContent = `(${count})`;
        });
    }
    
    /**
     * フィルターされたタスク数を取得
     * @returns {number} フィルターされたタスクの数
     */
    getFilteredTasksCount() {
        if (this.spanFilter === 'all') {
            return this.allTasks.length;
        }
        return this.allTasks.filter(t => t.span == this.spanFilter).length;
    }
    
    /**
     * モーダルを開く
     * フェードインアニメーションとともにモーダルを表示し、初期フィルターを'all'に設定
     */
    open() {
        if (!this.modal) return;
        
        this.isOpen = true;
        this.modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // 初期フィルター適用
        this.setSpanFilter('all');
        
        // アニメーション
        requestAnimationFrame(() => {
            this.overlay?.classList.add('opacity-100');
            this.overlay?.classList.remove('opacity-0');
            this.content?.classList.add('opacity-100', 'scale-100');
            this.content?.classList.remove('opacity-0', 'scale-95');
        });
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
}

// グローバルコントローラーマネージャー
window.TagTasksModalController = {
    instances: new Map(),
    
    /**
     * タグIDに対応するコントローラーを登録
     * @param {number} tagId - タグID
     * @returns {TagTasksModalController} コントローラーインスタンス
     */
    register(tagId) {
        if (!this.instances.has(tagId)) {
            this.instances.set(tagId, new TagTasksModalController(tagId));
        }
        return this.instances.get(tagId);
    },
    
    /**
     * 指定されたタグIDのモーダルを開く
     * @param {number} tagId - タグID
     */
    open(tagId) {
        const controller = this.register(tagId);
        controller.open();
    },
    
    /**
     * 指定されたタグIDのモーダルを閉じる
     * @param {number} tagId - タグID
     */
    close(tagId) {
        const controller = this.instances.get(tagId);
        if (controller) {
            controller.close();
        }
    }
};

// DOMContentLoaded時に既存のモーダルを登録
document.addEventListener('DOMContentLoaded', () => {
    const modals = document.querySelectorAll('[data-tag-tasks-modal]');
    modals.forEach(modal => {
        const tagId = modal.dataset.tagTasksModal;
        if (tagId) {
            window.TagTasksModalController.register(parseInt(tagId));
        }
    });
});
