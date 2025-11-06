/**
 * タスク検索・フィルタリング機能
 */
class TaskSearchController {
    constructor() {
        this.searchInput = null;
        this.filterSelect = null;
        this.resultsContainer = null;
        this.searchTimeout = null;
        this.currentFocusIndex = -1;
        this.searchResults = [];
        this.currentSearchType = 'title'; // 現在の検索タイプを保持
    }

    /**
     * 初期化
     */
    init() {
        this.searchInput = document.querySelector('input[type="search"]');
        this.filterSelect = document.querySelector('select');
        
        if (!this.searchInput || !this.filterSelect) {
            console.warn('Search or filter elements not found');
            return;
        }

        // 検索結果表示用のコンテナを作成
        this.createResultsContainer();
        
        // イベントリスナーを設定
        this.setupEventListeners();
    }

    /**
     * 検索結果表示用のコンテナを作成
     */
    createResultsContainer() {
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.id = 'search-results';
        this.resultsContainer.className = 'absolute top-full left-0 right-0 mt-2 bg-white border rounded-lg shadow-lg z-50 hidden max-h-96 overflow-y-auto';
        
        const parent = this.searchInput.closest('.relative');
        if (parent) {
            parent.appendChild(this.resultsContainer);
        }
    }

    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        // 検索入力（デバウンス）
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            
            if (e.target.value.trim() === '') {
                this.hideResults();
                this.resetDashboard();
                return;
            }
            
            this.searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 2000);
        });

        // キーボード操作
        this.searchInput.addEventListener('keydown', (e) => {
            if (this.resultsContainer.classList.contains('hidden')) return;
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.focusNext();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.focusPrevious();
                    break;
                case 'Enter':
                    e.preventDefault();
                    this.selectCurrent();
                    break;
                case 'Escape':
                    this.hideResults();
                    break;
                case 'Tab':
                    e.preventDefault();
                    this.focusNext();
                    break;
            }
        });

        // フィルター変更
        this.filterSelect.addEventListener('change', (e) => {
            this.applyFilter(e.target.value);
        });

        // 外側をクリックしたら結果を閉じる
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                this.hideResults();
            }
        });
    }

    /**
     * 検索を実行
     */
    async performSearch(query) {
        try {
            const searchParams = this.parseSearchQuery(query);
            this.currentSearchType = searchParams.type;
            const response = await this.searchTasks(searchParams);
            
            this.searchResults = response.tasks || [];
            this.displayResults(this.searchResults);
        } catch (error) {
            console.error('Search error:', error);
            this.showError('検索中にエラーが発生しました');
        }
    }

    /**
     * 検索クエリを解析
     */
    parseSearchQuery(query) {
        const params = {
            type: 'title',
            terms: [],
            operator: 'or'
        };

        // タグ検索
        if (query.startsWith('#')) {
            params.type = 'tag';
            query = query.substring(1);
        }

        // AND検索
        if (query.includes('&')) {
            params.operator = 'and';
            params.terms = query.split('&').map(t => t.trim()).filter(t => t);
        }
        // OR検索（空白区切り）
        else {
            params.operator = 'or';
            params.terms = query.split(/\s+/).filter(t => t);
        }

        return params;
    }

    /**
     * タスク検索APIリクエスト
     */
    async searchTasks(params) {
        const url = new URL('/tasks/search', window.location.origin);
        
        url.searchParams.append('type', params.type);
        url.searchParams.append('operator', params.operator);
        params.terms.forEach(term => url.searchParams.append('terms[]', term));

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;

        const response = await fetch(url, {
            method: 'GET',
            headers,
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`Search failed: ${response.status}`);
        }

        return response.json();
    }

    /**
     * 検索結果を表示
     */
    displayResults(tasks) {
        this.currentFocusIndex = -1;
        
        if (!tasks || tasks.length === 0) {
            this.resultsContainer.innerHTML = '<div class="p-4 text-gray-500 text-center">検索結果は0件でした</div>';
            this.resultsContainer.classList.remove('hidden');
            return;
        }

        // タグ検索の場合は、タグでグループ化
        if (this.currentSearchType === 'tag') {
            this.displayTagResults(tasks);
        } else {
            this.displayTaskResults(tasks);
        }
    }

    /**
     * タスク検索結果を表示
     */
    displayTaskResults(tasks) {
        const html = tasks.map((task, index) => {
            const tags = task.tags?.map(tag => `#${tag}`).join(' ') || '';
            const span = this.formatSpan(task.span);
            const dueDate = task.due_date || '-';
            
            return `
                <div class="search-result-item p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0" 
                     data-index="${index}"
                     data-task-id="${task.id}"
                     data-result-type="task">
                    <div class="font-medium">${this.escapeHtml(task.title)}</div>
                    <div class="text-sm text-gray-600 mt-1">
                        <span class="text-[#59B9C6]">${tags}</span>
                        <span class="mx-2">|</span>
                        <span>${span}</span>
                        <span class="mx-2">|</span>
                        <span>${dueDate}</span>
                    </div>
                </div>
            `;
        }).join('');

        this.resultsContainer.innerHTML = html;
        this.resultsContainer.classList.remove('hidden');

        // クリックイベントを設定
        this.resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const taskId = item.dataset.taskId;
                this.filterDashboardByTask(taskId);
                this.hideResults();
            });
        });
    }

    /**
     * タグ検索結果を表示
     */
    displayTagResults(tasks) {
        // タグごとにグループ化
        const tagGroups = {};
        tasks.forEach(task => {
            task.tags?.forEach(tag => {
                if (!tagGroups[tag]) {
                    tagGroups[tag] = [];
                }
                tagGroups[tag].push(task);
            });
        });

        const html = Object.entries(tagGroups).map(([tag, tagTasks]) => {
            return `
                <div class="search-result-item p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0" 
                     data-tag-name="${tag}"
                     data-result-type="tag">
                    <div class="font-medium text-[#59B9C6]">#${this.escapeHtml(tag)}</div>
                    <div class="text-sm text-gray-600 mt-1">
                        ${tagTasks.length}件のタスク
                    </div>
                </div>
            `;
        }).join('');

        this.resultsContainer.innerHTML = html;
        this.resultsContainer.classList.remove('hidden');

        // クリックイベントを設定
        this.resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const tagName = item.dataset.tagName;
                this.filterDashboardByTag(tagName);
                this.hideResults();
            });
        });
    }

    /**
     * ダッシュボードを特定のタスクのみ表示
     */
    filterDashboardByTask(taskId) {
        const gridContainer = document.querySelector('.grid');
        if (!gridContainer) return;

        const allTaskWrappers = gridContainer.querySelectorAll(':scope > div');
        
        allTaskWrappers.forEach(wrapper => {
            const taskCard = wrapper.querySelector('[data-task-id]');
            if (taskCard && taskCard.dataset.taskId === taskId) {
                wrapper.style.display = 'flex';
            } else {
                wrapper.style.display = 'none';
            }
        });

        // リセットボタンを表示
        this.showResetButton();
    }

    /**
     * ダッシュボードをタグで絞り込み
     */
    filterDashboardByTag(tagName) {
        const gridContainer = document.querySelector('.grid');
        if (!gridContainer) return;

        const allTaskWrappers = gridContainer.querySelectorAll(':scope > div');
        
        allTaskWrappers.forEach(wrapper => {
            const taskCard = wrapper.querySelector('[data-task-id]');
            if (taskCard) {
                const taskTags = taskCard.dataset.tags?.split(',') || [];
                if (taskTags.includes(tagName)) {
                    wrapper.style.display = 'flex';
                } else {
                    wrapper.style.display = 'none';
                }
            }
        });

        // リセットボタンを表示
        this.showResetButton();
    }

    /**
     * ダッシュボードをリセット
     */
    resetDashboard() {
        const gridContainer = document.querySelector('.grid');
        if (!gridContainer) return;

        const allTaskWrappers = gridContainer.querySelectorAll(':scope > div');
        allTaskWrappers.forEach(wrapper => {
            wrapper.style.display = 'flex';
        });

        // リセットボタンを非表示
        this.hideResetButton();
    }

    /**
     * フィルターを適用
     */
    applyFilter(filterValue) {
        const gridContainer = document.querySelector('.grid');
        if (!gridContainer) return;

        // 表示中のタスクのみを取得
        const visibleWrappers = Array.from(gridContainer.querySelectorAll(':scope > div'))
            .filter(wrapper => wrapper.style.display !== 'none');

        const tasks = visibleWrappers.map(wrapper => wrapper.querySelector('[data-task-id]')).filter(Boolean);
        
        if (filterValue === '期限順') {
            this.sortByDueDate(tasks, visibleWrappers);
        } else if (filterValue === 'タグ') {
            this.sortByTag(tasks, visibleWrappers);
        }
    }

    /**
     * 期限順にソート
     */
    sortByDueDate(tasks, wrappers) {
        const sortedData = tasks.map((task, index) => ({
            task,
            wrapper: wrappers[index],
            dueDate: task.dataset.dueDate || '9999-12-31'
        }));

        sortedData.sort((a, b) => a.dueDate.localeCompare(b.dueDate));

        const parent = wrappers[0]?.parentElement;
        if (parent) {
            sortedData.forEach(({ wrapper }) => parent.appendChild(wrapper));
        }
    }

    /**
     * タグ順にソート
     */
    sortByTag(tasks, wrappers) {
        const sortedData = tasks.map((task, index) => ({
            task,
            wrapper: wrappers[index],
            tag: task.dataset.tags?.split(',')[0] || ''
        }));

        sortedData.sort((a, b) => a.tag.localeCompare(b.tag));

        const parent = wrappers[0]?.parentElement;
        if (parent) {
            sortedData.forEach(({ wrapper }) => parent.appendChild(wrapper));
        }
    }

    /**
     * リセットボタンを表示
     */
    showResetButton() {
        let resetBtn = document.getElementById('reset-filter-btn');
        
        if (!resetBtn) {
            resetBtn = document.createElement('button');
            resetBtn.id = 'reset-filter-btn';
            resetBtn.className = 'fixed bottom-8 right-8 bg-[#59B9C6] text-white px-4 py-2 rounded-lg shadow-lg hover:bg-[#4AA5B2] transition-colors z-40';
            resetBtn.innerHTML = '<i class="fas fa-times mr-2"></i>フィルターをリセット';
            resetBtn.addEventListener('click', () => {
                this.resetDashboard();
                this.searchInput.value = '';
            });
            document.body.appendChild(resetBtn);
        } else {
            resetBtn.style.display = 'block';
        }
    }

    /**
     * リセットボタンを非表示
     */
    hideResetButton() {
        const resetBtn = document.getElementById('reset-filter-btn');
        if (resetBtn) {
            resetBtn.style.display = 'none';
        }
    }

    /**
     * スパンをフォーマット
     */
    formatSpan(span) {
        const spanMap = {
            1: '短期',
            2: '中期',
            3: '長期',
            'short': '短期',
            'mid': '中期',
            'long': '長期'
        };
        return spanMap[span] || span;
    }

    /**
     * HTMLエスケープ
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * エラーメッセージを表示
     */
    showError(message) {
        this.resultsContainer.innerHTML = `<div class="p-4 text-red-500 text-center">${message}</div>`;
        this.resultsContainer.classList.remove('hidden');
    }

    /**
     * 検索結果を非表示
     */
    hideResults() {
        this.resultsContainer.classList.add('hidden');
        this.currentFocusIndex = -1;
    }

    /**
     * 次の項目にフォーカス
     */
    focusNext() {
        const items = this.resultsContainer.querySelectorAll('.search-result-item');
        if (items.length === 0) return;

        this.currentFocusIndex = (this.currentFocusIndex + 1) % items.length;
        this.updateFocus(items);
    }

    /**
     * 前の項目にフォーカス
     */
    focusPrevious() {
        const items = this.resultsContainer.querySelectorAll('.search-result-item');
        if (items.length === 0) return;

        this.currentFocusIndex = this.currentFocusIndex <= 0 ? items.length - 1 : this.currentFocusIndex - 1;
        this.updateFocus(items);
    }

    /**
     * フォーカスを更新
     */
    updateFocus(items) {
        items.forEach((item, index) => {
            if (index === this.currentFocusIndex) {
                item.classList.add('bg-gray-100');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('bg-gray-100');
            }
        });
    }

    /**
     * 現在フォーカスされている項目を選択
     */
    selectCurrent() {
        const items = this.resultsContainer.querySelectorAll('.search-result-item');
        if (this.currentFocusIndex >= 0 && this.currentFocusIndex < items.length) {
            const item = items[this.currentFocusIndex];
            const resultType = item.dataset.resultType;
            
            if (resultType === 'tag') {
                const tagName = item.dataset.tagName;
                this.filterDashboardByTag(tagName);
            } else {
                const taskId = item.dataset.taskId;
                this.filterDashboardByTask(taskId);
            }
            
            this.hideResults();
        }
    }

    /**
     * フィルターを適用
     */
    applyFilter(filterValue) {
        const taskList = document.querySelector('.space-y-4');
        if (!taskList) return;

        const tasks = Array.from(taskList.querySelectorAll('[data-task-id]')).filter(task => {
            return task.style.display !== 'none';
        });
        
        if (filterValue === '期限順') {
            this.sortByDueDate(tasks);
        } else if (filterValue === 'タグ') {
            this.sortByTag(tasks);
        }
    }

    /**
     * 期限順にソート
     */
    sortByDueDate(tasks) {
        tasks.sort((a, b) => {
            const dateA = a.dataset.dueDate || '9999-12-31';
            const dateB = b.dataset.dueDate || '9999-12-31';
            return dateA.localeCompare(dateB);
        });

        const parent = tasks[0]?.parentElement;
        if (parent) {
            tasks.forEach(task => parent.appendChild(task));
        }
    }

    /**
     * タグ順にソート
     */
    sortByTag(tasks) {
        tasks.sort((a, b) => {
            const tagA = a.dataset.tags?.split(',')[0] || '';
            const tagB = b.dataset.tags?.split(',')[0] || '';
            return tagA.localeCompare(tagB);
        });

        const parent = tasks[0]?.parentElement;
        if (parent) {
            tasks.forEach(task => parent.appendChild(task));
        }
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    const searchController = new TaskSearchController();
    searchController.init();
});