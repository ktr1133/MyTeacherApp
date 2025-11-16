/**
 * 通知ポーリングシステム
 * 
 * - 10秒ごとに未読件数を取得
 * - 新規通知があればトースト表示
 * - トーストはアバターより前面に表示（z-index: 10000）
 * 
 * @module notification-polling
 */

// ========================================
// 設定
// ========================================
const POLLING_INTERVAL = 10000; // 10秒
const TOAST_DURATION = 5000;    // 5秒
const MAX_TOASTS = 3;           // 最大表示数
const ALPINE_WAIT_TIMEOUT = 5000; // Alpine.js 初期化待機タイムアウト（5秒）

// ========================================
// グローバル状態
// ========================================
let pollingTimer = null;
let lastCheckedAt = null;
let activeToasts = [];
let alpineReady = false;
let headerElement = null;

// ========================================
// 初期化
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Notification Polling] Initializing...');
    
    // トーストコンテナを作成
    createToastContainer();
    
    // Alpine.js の準備を待ってからポーリング開始
    waitForAlpine();
});

/**
 * Alpine.js の準備を待つ
 */
function waitForAlpine() {
    const startTime = Date.now();
    
    const checkAlpine = setInterval(() => {
        // Alpine.js が利用可能かチェック
        if (window.Alpine) {
            // ヘッダー要素を探す
            headerElement = document.querySelector('header[x-data*="notificationCount"]');
            
            if (headerElement) {
                const alpineComponent = Alpine.$data(headerElement);
                
                if (alpineComponent && typeof alpineComponent.notificationCount !== 'undefined') {
                    clearInterval(checkAlpine);
                    alpineReady = true;
                    console.log('[Notification Polling] Alpine.js ready, starting polling');
                    startPolling();
                    return;
                }
            }
        }
        
        // タイムアウトチェック
        if (Date.now() - startTime > ALPINE_WAIT_TIMEOUT) {
            clearInterval(checkAlpine);
            console.warn('[Notification Polling] Alpine.js timeout, starting polling anyway');
            startPolling();
        }
    }, 100);
}

/**
 * トーストコンテナを作成
 */
function createToastContainer() {
    if (document.getElementById('notification-toast-container')) {
        return; // 既に存在する
    }

    const container = document.createElement('div');
    container.id = 'notification-toast-container';
    container.className = 'fixed bottom-6 right-6 z-[10000] flex flex-col gap-3 pointer-events-none';
    container.style.maxWidth = '400px';
    
    document.body.appendChild(container);
    
    console.log('[Notification Polling] Toast container created');
}

/**
 * ポーリング開始
 */
function startPolling() {
    // 初回実行（トーストなし）
    fetchUnreadCount(true);
    
    // 10秒ごとに実行
    pollingTimer = setInterval(() => {
        fetchUnreadCount(false);
    }, POLLING_INTERVAL);
    
    console.log('[Notification Polling] Polling started (interval: 10s)');
}

/**
 * ポーリング停止
 */
function stopPolling() {
    if (pollingTimer) {
        clearInterval(pollingTimer);
        pollingTimer = null;
        console.log('[Notification Polling] Polling stopped');
    }
}

/**
 * 未読件数を取得
 * 
 * @param {boolean} isInitial - 初回実行フラグ
 */
async function fetchUnreadCount(isInitial = false) {
    try {
        const params = new URLSearchParams();
        if (lastCheckedAt) {
            params.append('last_checked_at', lastCheckedAt);
        }

        const response = await fetch(`/api/notifications/unread-count?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        
        // 未読件数をバッジに反映
        updateBadge(data.unread_count);
        
        // 新規通知があればトースト表示（初回は除く）
        if (!isInitial && data.new_notifications && data.new_notifications.length > 0) {
            showToasts(data.new_notifications);
        }
        
        // タイムスタンプ更新
        lastCheckedAt = data.timestamp;
        
    } catch (error) {
        console.error('[Notification Polling] Error:', error);
    }
}

/**
 * 未読バッジを更新
 * 
 * @param {number} count - 未読件数
 */
function updateBadge(count) {
    // Alpine.js の準備ができていない場合はスキップ
    if (!alpineReady) {
        return;
    }

    // ヘッダー要素が見つからない場合、再取得を試みる
    if (!headerElement) {
        headerElement = document.querySelector('header[x-data*="notificationCount"]');
    }

    if (!headerElement) {
        // 初回のみ警告（以降は静かに失敗）
        if (count > 0) {
            console.warn('[Notification Polling] Header element not found (count:', count, ')');
        }
        return;
    }

    try {
        // Alpine.js の $data を使って直接更新
        const alpineComponent = Alpine.$data(headerElement);
        if (alpineComponent && typeof alpineComponent.notificationCount !== 'undefined') {
            alpineComponent.notificationCount = count;
            console.log('[Notification Polling] Badge updated:', count);
        }
    } catch (error) {
        console.error('[Notification Polling] Failed to update badge:', error);
    }
}

/**
 * トーストを表示
 * 
 * @param {Array} notifications - 通知配列
 */
function showToasts(notifications) {
    // 複数通知がある場合はまとめて表示
    if (notifications.length > 1) {
        showSummaryToast(notifications);
    } else {
        // 1件のみの場合は詳細表示
        showDetailToast(notifications[0]);
    }
}

/**
 * 詳細トースト表示（1件）
 * 
 * @param {Object} notification - 通知オブジェクト
 */
function showDetailToast(notification) {
    const container = document.getElementById('notification-toast-container');
    if (!container) return;

    // 優先度に応じた左端バーの色
    const borderColors = {
        important: 'bg-gradient-to-b from-red-400 to-pink-500',
        normal: 'bg-gradient-to-b from-blue-400 to-indigo-500',
        info: 'bg-gradient-to-b from-gray-400 to-slate-500',
    };

    const priorityLabels = {
        important: '重要',
        normal: '通常',
        info: '情報',
    };

    const toast = document.createElement('div');
    toast.className = 'notification-toast pointer-events-auto';
    toast.dataset.notificationId = notification.id;
    
    toast.innerHTML = `
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-[1.02] hover:shadow-3xl border border-gray-200 dark:border-gray-700">
            <!-- 優先度バー（左端） -->
            <div class="absolute left-0 top-0 bottom-0 w-1 ${borderColors[notification.priority] || borderColors.normal}"></div>
            
            <!-- コンテンツ -->
            <div class="pl-4 pr-3 py-3 flex items-start gap-3">
                <!-- アイコン -->
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- テキスト部分 -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 text-purple-700 dark:text-purple-300">
                            ${priorityLabels[notification.priority] || '通常'}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            管理者: ${notification.sender}
                        </span>
                    </div>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">
                        ${notification.title}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        クリックして詳細を表示
                    </p>
                </div>
                
                <!-- 閉じるボタン -->
                <button class="toast-close-btn flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `;

    // クリックイベント
    toast.addEventListener('click', (e) => {
        if (!e.target.closest('.toast-close-btn')) {
            window.location.href = `/notification/${notification.id}`;
        }
    });

    // 閉じるボタン
    const closeBtn = toast.querySelector('.toast-close-btn');
    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        removeToast(toast);
    });

    // トースト追加
    container.appendChild(toast);
    activeToasts.push(toast);

    // アニメーション（スライドイン）
    requestAnimationFrame(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        
        requestAnimationFrame(() => {
            toast.style.transition = 'all 0.3s ease-out';
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });
    });

    // 5秒後に自動削除
    setTimeout(() => {
        removeToast(toast);
    }, TOAST_DURATION);

    // 最大数を超えたら古いものを削除
    if (activeToasts.length > MAX_TOASTS) {
        removeToast(activeToasts[0]);
    }
}

/**
 * サマリートースト表示（複数件）
 * 
 * @param {Array} notifications - 通知配列
 */
function showSummaryToast(notifications) {
    const container = document.getElementById('notification-toast-container');
    if (!container) return;

    const count = notifications.length;
    const latestNotification = notifications[0];

    const toast = document.createElement('div');
    toast.className = 'notification-toast pointer-events-auto';
    
    toast.innerHTML = `
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-[1.02] hover:shadow-3xl border border-gray-200 dark:border-gray-700">
            <!-- グラデーションバー（左端） -->
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-purple-400 via-pink-500 to-indigo-500"></div>
            
            <!-- コンテンツ -->
            <div class="pl-4 pr-3 py-3 flex items-start gap-3">
                <!-- アイコン -->
                <div class="flex-shrink-0 mt-0.5">
                    <div class="relative w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 via-pink-500 to-indigo-600 flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                        <!-- 件数バッジ -->
                        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-lg">
                            ${count}
                        </div>
                    </div>
                </div>
                
                <!-- テキスト部分 -->
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-purple-900 dark:text-purple-100 text-base mb-1">
                        新着通知 ${count} 件
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-1 line-clamp-1">
                        最新: ${latestNotification.title}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        クリックして一覧を表示
                    </p>
                </div>
                
                <!-- 閉じるボタン -->
                <button class="toast-close-btn flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `;

    // クリックイベント（一覧ページへ）
    toast.addEventListener('click', (e) => {
        if (!e.target.closest('.toast-close-btn')) {
            window.location.href = '/notification';
        }
    });

    // 閉じるボタン
    const closeBtn = toast.querySelector('.toast-close-btn');
    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        removeToast(toast);
    });

    container.appendChild(toast);
    activeToasts.push(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        
        requestAnimationFrame(() => {
            toast.style.transition = 'all 0.3s ease-out';
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });
    });

    setTimeout(() => {
        removeToast(toast);
    }, TOAST_DURATION);

    if (activeToasts.length > MAX_TOASTS) {
        removeToast(activeToasts[0]);
    }
}

/**
 * トーストを削除
 * 
 * @param {HTMLElement} toast - トースト要素
 */
function removeToast(toast) {
    if (!toast || !toast.parentElement) return;

    // フェードアウトアニメーション
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';

    setTimeout(() => {
        toast.remove();
        activeToasts = activeToasts.filter(t => t !== toast);
    }, 300);
}

// ページ離脱時にポーリング停止
window.addEventListener('beforeunload', () => {
    stopPolling();
});

// エクスポート（テスト用）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        startPolling,
        stopPolling,
        fetchUnreadCount,
    };
}