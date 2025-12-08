/**
 * 無限スクロール機能
 * タスク一覧画面でスクロールすると自動的に次のタスクを読み込む
 */

class InfiniteScrollManager {
    constructor(options = {}) {
        // 設定
        this.apiEndpoint = options.apiEndpoint || '/tasks/paginated';
        this.container = options.container || document.getElementById('task-list');
        this.loadingElement = options.loadingElement || document.getElementById('loading-indicator');
        this.perPage = options.perPage || 50;
        this.threshold = options.threshold || 200; // スクロール位置の閾値（px）
        
        // 状態管理
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMore = true;
        this.filters = {};
        
        // 初期化
        this.init();
    }

    /**
     * 初期化
     */
    init() {
        // スクロールイベントリスナー設定
        window.addEventListener('scroll', () => this.handleScroll());
        
        // 初回データ読み込み
        this.loadInitialTasks();
    }

    /**
     * 初回タスク読み込み
     */
    async loadInitialTasks() {
        this.currentPage = 1;
        this.hasMore = true;
        this.container.innerHTML = '';
        
        await this.loadMoreTasks();
    }

    /**
     * スクロールハンドラー
     */
    handleScroll() {
        // ページ下部に到達したか判定
        const scrollHeight = document.documentElement.scrollHeight;
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const clientHeight = window.innerHeight;
        
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);
        
        // 閾値以内で、かつ読み込み中でなく、まだデータがある場合
        if (distanceFromBottom < this.threshold && !this.isLoading && this.hasMore) {
            this.loadMoreTasks();
        }
    }

    /**
     * 次のページのタスクを読み込む
     */
    async loadMoreTasks() {
        if (this.isLoading || !this.hasMore) {
            return;
        }

        this.isLoading = true;
        this.showLoading();

        try {
            // APIリクエスト
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                ...this.filters
            });

            const response = await fetch(`${this.apiEndpoint}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // タスクをDOMに追加
                this.appendTasks(data.data.tasks);
                
                // ページネーション情報を更新
                this.hasMore = data.data.pagination.has_more;
                this.currentPage = data.data.pagination.next_page;
                
                console.log(`Loaded page ${data.data.pagination.current_page}, has more: ${this.hasMore}`);
            } else {
                console.error('API returned error:', data.message);
                this.showError('タスクの読み込みに失敗しました。');
            }

        } catch (error) {
            console.error('Failed to load tasks:', error);
            this.showError('タスクの読み込み中にエラーが発生しました。');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    /**
     * タスクをDOMに追加
     * @param {Array} tasks タスク配列
     */
    appendTasks(tasks) {
        tasks.forEach(task => {
            const taskElement = this.createTaskElement(task);
            this.container.appendChild(taskElement);
        });
    }

    /**
     * タスク要素を生成
     * @param {Object} task タスクオブジェクト
     * @returns {HTMLElement} タスク要素
     */
    createTaskElement(task) {
        const article = document.createElement('article');
        article.className = 'task-item bg-white rounded-lg shadow p-4 mb-3 hover:shadow-lg transition-shadow';
        article.dataset.taskId = task.id;

        // タイトル
        const title = document.createElement('h3');
        title.className = 'text-lg font-semibold mb-2';
        title.textContent = task.title;

        // 説明
        const description = document.createElement('p');
        description.className = 'text-gray-600 text-sm mb-2';
        description.textContent = task.description || '';

        // メタ情報（期限、優先度など）
        const meta = document.createElement('div');
        meta.className = 'flex items-center gap-3 text-sm text-gray-500';
        
        if (task.due_date) {
            const dueDate = document.createElement('span');
            dueDate.className = 'flex items-center gap-1';
            dueDate.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> ${task.due_date}`;
            meta.appendChild(dueDate);
        }

        // 優先度表示
        const priority = document.createElement('span');
        priority.className = `px-2 py-1 rounded text-xs font-medium priority-${task.priority}`;
        priority.textContent = this.getPriorityLabel(task.priority);
        meta.appendChild(priority);

        // タグ表示
        if (task.tags && task.tags.length > 0) {
            const tagsContainer = document.createElement('div');
            tagsContainer.className = 'flex flex-wrap gap-1 mt-2';
            
            task.tags.forEach(tag => {
                const tagElement = document.createElement('span');
                tagElement.className = 'px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs';
                tagElement.textContent = tag.name;
                tagsContainer.appendChild(tagElement);
            });
            
            article.appendChild(tagsContainer);
        }

        article.appendChild(title);
        article.appendChild(description);
        article.appendChild(meta);

        return article;
    }

    /**
     * 優先度ラベル取得
     * @param {number} priority 優先度（1-5）
     * @returns {string} ラベル
     */
    getPriorityLabel(priority) {
        const labels = {
            1: '最優先',
            2: '高',
            3: '中',
            4: '低',
            5: '最低'
        };
        return labels[priority] || '中';
    }

    /**
     * フィルター設定
     * @param {Object} filters フィルター条件
     */
    setFilters(filters) {
        this.filters = filters;
        this.loadInitialTasks();
    }

    /**
     * ローディング表示
     */
    showLoading() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'block';
        }
    }

    /**
     * ローディング非表示
     */
    hideLoading() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'none';
        }
    }

    /**
     * エラー表示
     * @param {string} message エラーメッセージ
     */
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-3';
        errorDiv.textContent = message;
        this.container.parentElement.insertBefore(errorDiv, this.container);
        
        // 3秒後に自動削除
        setTimeout(() => errorDiv.remove(), 3000);
    }

    /**
     * リセット
     */
    reset() {
        this.currentPage = 1;
        this.hasMore = true;
        this.filters = {};
        this.container.innerHTML = '';
    }
}

// ES6モジュールとしてエクスポート
export default InfiniteScrollManager;
