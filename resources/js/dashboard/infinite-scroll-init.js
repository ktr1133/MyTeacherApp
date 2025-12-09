/**
 * ダッシュボード用無限スクロール初期化
 * 既存のBentoレイアウトに統合された無限スクロール機能
 */

import InfiniteScrollManager from '../infinite-scroll.js';

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('task-list-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    const mainScrollContainer = document.getElementById('main-scroll-container');
    
    if (!container || !loadingIndicator) {
        console.warn('Task list container or loading indicator not found');
        return;
    }

    // 初期データ属性から設定を取得
    const hasMore = container.dataset.hasMore === 'true';
    const nextPage = parseInt(container.dataset.nextPage) || 2;
    const perPage = parseInt(container.dataset.perPage) || 50;

    // カスタム無限スクロールマネージャー（Bentoレイアウト対応）
    class DashboardInfiniteScroll extends InfiniteScrollManager {
        constructor(options) {
            super(options);
            this.hasMore = hasMore;
            this.currentPage = nextPage;
            this.mainScrollContainer = mainScrollContainer;
            
            // カスタムスクロール監視（main要素を監視）
            if (this.mainScrollContainer) {
                this.mainScrollContainer.removeEventListener('scroll', this.handleScroll);
                this.mainScrollContainer.addEventListener('scroll', () => this.handleMainScroll());
            }
        }

        /**
         * 初期化をオーバーライド（初回データ読み込みをスキップ）
         */
        init() {
            // スクロールイベントリスナー設定のみ
            // 初回データは既にサーバー側で読み込み済みのため、loadInitialTasks()は呼ばない
            if (this.mainScrollContainer) {
                this.mainScrollContainer.addEventListener('scroll', () => this.handleMainScroll());
            } else {
                window.addEventListener('scroll', () => this.handleScroll());
            }
        }

        /**
         * メインコンテナのスクロールハンドラー
         */
        handleMainScroll() {
            const scrollElement = this.mainScrollContainer;
            const scrollHeight = scrollElement.scrollHeight;
            const scrollTop = scrollElement.scrollTop;
            const clientHeight = scrollElement.clientHeight;
            
            const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);
            
            if (distanceFromBottom < this.threshold && !this.isLoading && this.hasMore) {
                this.loadMoreTasks();
            }
        }

        /**
         * タスクをDOMに追加（Bentoレイアウト用にカスタマイズ）
         */
        appendTasks(tasks) {
            if (!tasks || tasks.length === 0) {
                return;
            }

            // 既存のBentoグリッドを取得
            const bentoGrid = this.container.querySelector('.bento-grid');
            
            if (!bentoGrid) {
                console.error('Bento grid not found');
                return;
            }

            // タスクをタグごとにグループ化
            const tasksByTag = this.groupTasksByTag(tasks);
            
            // 各タググループをBentoカードとして追加
            Object.entries(tasksByTag).forEach(([tagName, tagTasks]) => {
                const existingCard = this.findExistingBentoCard(tagName);
                
                if (existingCard) {
                    // 既存のカードにタスクを追加
                    this.appendTasksToCard(existingCard, tagTasks);
                } else {
                    // 新しいBentoカードを作成
                    const newCard = this.createBentoCard(tagName, tagTasks);
                    bentoGrid.appendChild(newCard);
                }
            });
        }

        /**
         * タスクをタグごとにグループ化
         */
        groupTasksByTag(tasks) {
            const grouped = {};
            
            tasks.forEach(task => {
                if (task.tags && task.tags.length > 0) {
                    task.tags.forEach(tag => {
                        if (!grouped[tag.name]) {
                            grouped[tag.name] = [];
                        }
                        grouped[tag.name].push(task);
                    });
                } else {
                    const uncategorized = '未分類';
                    if (!grouped[uncategorized]) {
                        grouped[uncategorized] = [];
                    }
                    grouped[uncategorized].push(task);
                }
            });
            
            return grouped;
        }

        /**
         * 既存のBentoカードを検索
         */
        findExistingBentoCard(tagName) {
            const cards = this.container.querySelectorAll('.bento-card h3');
            for (const title of cards) {
                if (title.textContent.trim() === tagName) {
                    return title.closest('.bento-card');
                }
            }
            return null;
        }

        /**
         * 既存カードにタスクを追加
         */
        appendTasksToCard(card, tasks) {
            const taskList = card.querySelector('.space-y-2, .space-y-3');
            if (!taskList) {
                console.warn('Task list not found in card');
                return;
            }

            tasks.forEach(task => {
                const taskElement = this.createSimpleTaskElement(task);
                taskList.appendChild(taskElement);
            });

            // カウントバッジを更新
            const badge = card.querySelector('.badge, [class*="badge"]');
            if (badge) {
                const currentCount = parseInt(badge.textContent) || 0;
                badge.textContent = currentCount + tasks.length;
            }
        }

        /**
         * 新しいBentoカードを作成（簡易版）
         */
        createBentoCard(tagName, tasks) {
            const card = document.createElement('div');
            card.className = 'bento-card group relative rounded-2xl shadow-lg hover:shadow-2xl p-4 lg:p-6 cursor-pointer transition-all duration-300 col-span-1 row-span-1';
            
            card.innerHTML = `
                <div class="flex items-start justify-between mb-3 lg:mb-4 gap-2">
                    <div class="flex items-center gap-2 lg:gap-3 min-w-0 flex-1 overflow-hidden">
                        <span class="inline-flex items-center justify-center w-8 h-8 lg:w-10 lg:h-10 rounded-lg lg:rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 text-white shadow-lg flex-shrink-0">
                            <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 12a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2H5zM12 5a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V5zM12 14a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"/>
                            </svg>
                        </span>
                        <h3 class="text-base lg:text-lg font-bold text-gray-900 dark:text-white truncate min-w-0">${this.escapeHtml(tagName)}</h3>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                        <span class="badge inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-[#59B9C6]/20 text-[#59B9C6]">
                            ${tasks.length}
                        </span>
                    </div>
                </div>
                <div class="space-y-2 lg:space-y-3"></div>
            `;

            const taskList = card.querySelector('.space-y-2');
            tasks.forEach(task => {
                const taskElement = this.createSimpleTaskElement(task);
                taskList.appendChild(taskElement);
            });

            return card;
        }

        /**
         * シンプルなタスク要素を作成
         */
        createSimpleTaskElement(task) {
            const div = document.createElement('div');
            div.className = 'task-preview-item p-2 lg:p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors';
            div.dataset.taskId = task.id;
            
            const priorityClass = this.getPriorityClass(task.priority);
            
            div.innerHTML = `
                <div class="flex items-start gap-2">
                    <div class="w-1 h-full ${priorityClass} rounded-full flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${this.escapeHtml(task.title)}</p>
                        ${task.due_date ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${this.escapeHtml(task.due_date)}</p>` : ''}
                    </div>
                </div>
            `;
            
            return div;
        }

        /**
         * 優先度に応じたクラスを取得
         */
        getPriorityClass(priority) {
            const classes = {
                1: 'bg-red-500',
                2: 'bg-orange-500',
                3: 'bg-yellow-500',
                4: 'bg-blue-500',
                5: 'bg-gray-400'
            };
            return classes[priority] || 'bg-gray-400';
        }

        /**
         * HTMLエスケープ
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // 無限スクロールマネージャーを初期化
    const scrollManager = new DashboardInfiniteScroll({
        apiEndpoint: '/tasks/paginated',
        container: container,
        loadingElement: loadingIndicator,
        perPage: perPage,
        threshold: 300
    });

    // グローバルに公開（デバッグ用）
    window.dashboardScrollManager = scrollManager;

    console.log('Dashboard infinite scroll initialized', {
        hasMore: hasMore,
        nextPage: nextPage,
        perPage: perPage
    });
});
