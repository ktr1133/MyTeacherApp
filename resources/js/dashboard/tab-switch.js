/**
 * ダッシュボードタブ切り替え機能（Vanilla JS）
 * 
 * Alpine.jsの activeTab 状態管理を置き換え
 */
(function() {
    'use strict';
    
    let currentTab = 'todo'; // デフォルトは未完了タブ
    
    /**
     * タブを切り替える
     * @param {string} tabName - 'todo' または 'completed'
     */
    function switchTab(tabName) {
        if (currentTab === tabName) return;
        
        currentTab = tabName;
        
        // タブボタンの状態を更新
        updateTabButtons();
        
        // タブコンテンツの表示/非表示を切り替え
        updateTabContent();
        
        console.log(`[TabSwitch] Switched to ${tabName} tab`);
    }
    
    /**
     * タブボタンのアクティブ状態を更新
     */
    function updateTabButtons() {
        const todoBtn = document.querySelector('[data-tab="todo"]');
        const completedBtn = document.querySelector('[data-tab="completed"]');
        
        if (!todoBtn || !completedBtn) {
            console.warn('[TabSwitch] Tab buttons not found');
            return;
        }
        
        // アクティブクラスの定義
        const activeClasses = ['border-[#59B9C6]', 'text-[#59B9C6]', 'dashboard-tab', 'active'];
        const inactiveClasses = ['border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:hover:text-gray-300', 'dashboard-tab'];
        
        if (currentTab === 'todo') {
            todoBtn.classList.remove(...inactiveClasses);
            todoBtn.classList.add(...activeClasses);
            completedBtn.classList.remove(...activeClasses);
            completedBtn.classList.add(...inactiveClasses);
        } else {
            completedBtn.classList.remove(...inactiveClasses);
            completedBtn.classList.add(...activeClasses);
            todoBtn.classList.remove(...activeClasses);
            todoBtn.classList.add(...inactiveClasses);
        }
    }
    
    /**
     * タブコンテンツの表示を更新
     */
    function updateTabContent() {
        const todoContent = document.querySelector('[data-tab-content="todo"]');
        const completedContent = document.querySelector('[data-tab-content="completed"]');
        
        if (!todoContent || !completedContent) {
            console.warn('[TabSwitch] Tab content not found');
            return;
        }
        
        if (currentTab === 'todo') {
            todoContent.style.display = 'block';
            completedContent.style.display = 'none';
            
            // フェードインアニメーション
            todoContent.style.opacity = '0';
            requestAnimationFrame(() => {
                todoContent.style.transition = 'opacity 300ms';
                todoContent.style.opacity = '1';
            });
        } else {
            completedContent.style.display = 'block';
            todoContent.style.display = 'none';
            
            // フェードインアニメーション
            completedContent.style.opacity = '0';
            requestAnimationFrame(() => {
                completedContent.style.transition = 'opacity 300ms';
                completedContent.style.opacity = '1';
            });
        }
    }
    
    /**
     * タブボタンのイベントリスナーを登録
     */
    function initTabButtons() {
        const todoBtn = document.querySelector('[data-tab="todo"]');
        const completedBtn = document.querySelector('[data-tab="completed"]');
        
        if (!todoBtn || !completedBtn) {
            console.error('[TabSwitch] Tab buttons not found');
            return;
        }
        
        todoBtn.addEventListener('click', () => switchTab('todo'));
        completedBtn.addEventListener('click', () => switchTab('completed'));
        
        // 初期状態を設定
        updateTabButtons();
        updateTabContent();
        
        console.log('[TabSwitch] Initialized');
    }
    
    /**
     * 現在のアクティブタブを取得（他のJSから参照可能）
     */
    window.getActiveTab = function() {
        return currentTab;
    };
    
    // DOMContentLoaded後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabButtons);
    } else {
        initTabButtons();
    }
})();
