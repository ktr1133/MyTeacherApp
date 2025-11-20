/**
 * 通知検索機能
 */
class NotificationSearchController {
    constructor() {
        this.searchInput = null;
        this.resultsContainer = null;
        this.searchTimeout = null;
        this.currentFocusIndex = -1;
        this.searchResults = [];
        this.currentTerms = [];
        this.currentOperator = 'or';
    }

    /**
     * 初期化
     */
    init() {
        this.searchInput = document.querySelector('#notification-search-input');
        
        if (!this.searchInput) {
            console.warn('Notification search input not found');
            return;
        }

        this.createResultsContainer();
        this.setupEventListeners();
    }

    /**
     * 検索結果表示用のコンテナを作成
     */
    createResultsContainer() {
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.id = 'notification-search-results';
        this.resultsContainer.className = 'absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 hidden max-h-96 overflow-y-auto';
        
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
                return;
            }
            
            this.searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
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
            }
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
            this.currentTerms = searchParams.terms;
            this.currentOperator = searchParams.operator;
            
            const response = await this.searchNotifications(searchParams);
            
            this.searchResults = response.notifications || [];
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
            terms: [],
            operator: 'or'
        };

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
     * 通知検索APIリクエスト
     */
    async searchNotifications(params) {
        const url = new URL('/notification/search/api', window.location.origin);
        
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
    displayResults(notifications) {
        this.currentFocusIndex = -1;
        
        if (!notifications || notifications.length === 0) {
            this.resultsContainer.innerHTML = '<div class="p-4 text-gray-500 dark:text-gray-400 text-center">検索結果は0件でした</div>';
            this.resultsContainer.classList.remove('hidden');
            return;
        }

        const html = notifications.map((notification, index) => {
            const priorityBadge = this.getPriorityBadge(notification.priority);
            const sourceBadge = this.getSourceBadge(notification.source, notification.source_label);
            const readBadge = notification.is_read ? '' : '<span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400">未読</span>';
            
            return `
                <div class="search-result-item p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition ${notification.is_read ? 'opacity-60' : ''}" 
                     data-index="${index}"
                     data-notification-id="${notification.id}"
                     tabindex="0">
                    <div class="flex items-center gap-2 mb-2">
                        ${priorityBadge}
                        ${sourceBadge}
                        ${readBadge}
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white mb-1">${this.escapeHtml(notification.title)}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span>${notification.sender}</span>
                        <span class="mx-2">•</span>
                        <span>${notification.publish_at || '日時不明'}</span>
                    </div>
                </div>
            `;
        }).join('');

        // 「すべて表示」ボタンを追加
        const viewAllButton = `
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <button 
                    id="view-all-results-btn"
                    class="w-full py-2 px-4 bg-[#59B9C6] hover:bg-[#4a9fb0] text-white font-medium rounded-lg transition-colors">
                    すべての検索結果を表示
                </button>
            </div>
        `;

        this.resultsContainer.innerHTML = html + viewAllButton;
        this.resultsContainer.classList.remove('hidden');

        // クリックイベント
        this.resultsContainer.querySelectorAll('.search-result-item').forEach((item) => {
            item.addEventListener('click', () => {
                const notificationId = item.dataset.notificationId;
                window.location.href = `/notification/${notificationId}`;
            });
            
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const notificationId = item.dataset.notificationId;
                    window.location.href = `/notification/${notificationId}`;
                }
            });
        });

        // 「すべて表示」ボタンのイベント
        document.getElementById('view-all-results-btn')?.addEventListener('click', () => {
            this.navigateToSearchResults();
        });
    }

    /**
     * 検索結果ページに遷移
     */
    navigateToSearchResults() {
        const url = new URL('/notification/search/results', window.location.origin);
        url.searchParams.append('operator', this.currentOperator);
        this.currentTerms.forEach(term => url.searchParams.append('terms[]', term));
        
        window.location.href = url.toString();
    }

    /**
     * 優先度バッジを取得
     */
    getPriorityBadge(priority) {
        if (priority === 'important') {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">重要</span>';
        } else if (priority === 'normal') {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">通常</span>';
        } else {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400">情報</span>';
        }
    }

    /**
     * ソースバッジを取得
     */
    getSourceBadge(source, label) {
        if (source === 'admin') {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400">公式</span>';
        } else {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">システム</span>';
        }
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
        this.resultsContainer.innerHTML = `<div class="p-4 text-red-500 dark:text-red-400 text-center">${message}</div>`;
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
                item.classList.add('bg-gray-100', 'dark:bg-gray-700');
                item.scrollIntoView({ block: 'nearest' });
                item.focus();
            } else {
                item.classList.remove('bg-gray-100', 'dark:bg-gray-700');
            }
        });
    }

    /**
     * 現在フォーカスされている項目を選択
     */
    selectCurrent() {
        const items = this.resultsContainer.querySelectorAll('.search-result-item');
        if (this.currentFocusIndex >= 0 && this.currentFocusIndex < items.length) {
            const notificationId = items[this.currentFocusIndex].dataset.notificationId;
            window.location.href = `/notification/${notificationId}`;
        }
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    const searchController = new NotificationSearchController();
    searchController.init();
});