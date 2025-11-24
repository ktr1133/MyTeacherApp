/**
 * 検索モーダル制御
 * タスク検索用の全画面モーダルを制御
 */
class SearchModalController {
    constructor() {
        this.modal = null;
        this.backdrop = null;
        this.openButton = null;
        this.closeButton = null;
        this.isOpen = false;
    }

    /**
     * 初期化
     */
    init() {
        this.modal = document.querySelector('[data-search-modal]');
        this.backdrop = this.modal?.querySelector('[data-modal-backdrop]');
        this.openButton = document.querySelector('[data-open-search-modal]');
        this.closeButton = this.modal?.querySelector('[data-close-modal]');
        
        if (!this.modal || !this.openButton) {
            console.warn('[SearchModal] Required elements not found');
            return;
        }

        this.setupEventListeners();
    }

    /**
     * イベントリスナー設定
     */
    setupEventListeners() {
        // 開くボタン
        this.openButton.addEventListener('click', () => this.open());

        // 閉じるボタン
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => this.close());
        }

        // 背景クリックで閉じる
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => this.close());
        }

        // ESCキーで閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }

    /**
     * モーダルを開く
     */
    open() {
        if (!this.modal || this.isOpen) return;

        this.modal.classList.remove('hidden');
        this.isOpen = true;

        // body のスクロールを無効化
        document.body.style.overflow = 'hidden';

        // モーダルコンテンツ要素を取得
        const modalContent = this.modal.querySelector('[data-modal-content]');
        const backdrop = this.modal.querySelector('[data-modal-backdrop]');

        // 初期状態（非表示）
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
            modalContent.style.opacity = '0';
        }
        if (backdrop) {
            backdrop.style.opacity = '0';
        }

        // アニメーション開始（次フレーム）
        requestAnimationFrame(() => {
            if (backdrop) {
                backdrop.style.opacity = '1';
            }
            if (modalContent) {
                modalContent.style.transform = 'scale(1)';
                modalContent.style.opacity = '1';
            }
        });

        // モーダル内の検索inputにフォーカス
        setTimeout(() => {
            const searchInput = this.modal.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.focus();
                
                // Tab移動を検索欄内に制限
                searchInput.addEventListener('keydown', this.handleSearchKeydown.bind(this));
            }
        }, 350); // アニメーション完了後にフォーカス

        // task-search.jsを再初期化（モーダル内の検索inputを検知させる）
        this.reinitializeTaskSearch();

        console.log('[SearchModal] Opened');
    }

    /**
     * 検索inputのキーダウンハンドラー（Tab移動を制御）
     */
    handleSearchKeydown(e) {
        if (e.key === 'Tab') {
            // Tabキーを無効化（検索欄から移動させない）
            e.preventDefault();
        }
    }

    /**
     * task-search.jsを再初期化
     */
    reinitializeTaskSearch() {
        // モーダル内の検索inputを取得
        const modalSearchInput = this.modal.querySelector('[data-search-input]');
        
        if (!modalSearchInput) {
            console.warn('[SearchModal] Search input not found in modal');
            return;
        }

        // 既存の検索結果コンテナがあれば削除
        const existingResults = this.modal.querySelector('#search-results');
        if (existingResults) {
            existingResults.remove();
        }

        // 検索結果表示用のコンテナを作成
        const resultsContainer = document.createElement('div');
        resultsContainer.id = 'search-results';
        resultsContainer.className = 'absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 hidden max-h-96 overflow-y-auto';
        
        const parent = modalSearchInput.closest('.relative');
        if (parent) {
            parent.appendChild(resultsContainer);
        }

        // TaskSearchControllerの新しいインスタンスを作成してモーダル専用に初期化
        if (window.TaskSearchController) {
            const modalSearchController = new window.TaskSearchController();
            // 検索inputを直接指定
            modalSearchController.searchInput = modalSearchInput;
            modalSearchController.resultsContainer = resultsContainer;
            modalSearchController.filterSelect = this.modal.querySelector('select');
            
            // イベントリスナーを設定
            modalSearchController.setupEventListeners();
            
            console.log('[SearchModal] TaskSearchController initialized for modal', {
                searchInput: !!modalSearchController.searchInput,
                resultsContainer: !!modalSearchController.resultsContainer,
                filterSelect: !!modalSearchController.filterSelect
            });
        } else {
            console.error('[SearchModal] TaskSearchController class not found');
        }
    }

    /**
     * モーダルを閉じる
     */
    close() {
        if (!this.modal || !this.isOpen) return;

        const modalContent = this.modal.querySelector('[data-modal-content]');
        const backdrop = this.modal.querySelector('[data-modal-backdrop]');

        // フェードアウトアニメーション
        if (backdrop) {
            backdrop.style.opacity = '0';
        }
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
            modalContent.style.opacity = '0';
        }

        // アニメーション完了後に非表示
        setTimeout(() => {
            this.modal.classList.add('hidden');
            this.isOpen = false;

            // body のスクロールを有効化
            document.body.style.overflow = '';

            // 検索結果をクリア
            const resultsContainer = this.modal.querySelector('#search-results');
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
                resultsContainer.classList.add('hidden');
            }

            // 検索inputをクリア
            const searchInput = this.modal.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.value = '';
            }

            console.log('[SearchModal] Closed');
        }, 300); // アニメーション時間と同期
    }
}

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', () => {
    const searchModalController = new SearchModalController();
    searchModalController.init();
});
