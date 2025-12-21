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
        console.log('[Notification Polling] Toast container already exists');
        return;
    }

    const container = document.createElement('div');
    container.id = 'notification-toast-container';
    
    // ★ インラインスタイルで確実に設定
    container.style.position = 'fixed';
    container.style.bottom = '24px';
    container.style.right = '24px';
    container.style.zIndex = '99999';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.gap = '12px';
    container.style.maxWidth = '400px';
    // pointer-events: none は削除（コンテナ自体はクリック可能にする必要がある）
    
    document.body.appendChild(container);
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

        const response = await fetch(`/notifications/unread-count?${params.toString()}`, {
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
    console.log('[showToasts] Called with', notifications.length, 'notifications');
    
    // 複数通知がある場合はまとめて表示
    if (notifications.length > 1) {
        console.log('[showToasts] Showing summary toast');
        showSummaryToast(notifications);
    } else {
        // 1件のみの場合は詳細表示
        console.log('[showToasts] Showing detail toast');
        showDetailToast(notifications[0]);
    }
}

/**
 * 詳細トースト表示（1件）
 * 
 * @param {Object} notification - 通知オブジェクト
 */
function showDetailToast(notification) {
    console.log('[showDetailToast] Showing notification:', notification);
    const container = document.getElementById('notification-toast-container');
    if (!container) {
        console.error('[showDetailToast] Container not found!');
        return;
    }
    console.log('[showDetailToast] Container found:', container);

    // 優先度に応じた左端バーの色
    const borderColors = {
        important: '#ef4444', // red-500
        normal: '#3b82f6',    // blue-500
        info: '#6b7280',      // gray-500
    };

    const priorityLabels = {
        important: '重要',
        normal: '通常',
        info: '情報',
    };

    const toast = document.createElement('div');
    toast.className = 'notification-toast';
    toast.dataset.notificationId = notification.id;
    
    // ★ インラインスタイルで初期状態を設定
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease-out';
    toast.style.pointerEvents = 'auto';
    toast.style.minWidth = '320px';
    toast.style.cursor = 'pointer';
    
    // ★ インラインスタイルでレンダリング
    toast.innerHTML = `
        <div style="
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        ">
            <!-- 優先度バー（左端） -->
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: ${borderColors[notification.priority] || borderColors.normal};
            "></div>
            
            <!-- コンテンツ -->
            <div style="
                padding: 12px 12px 12px 16px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            ">
                <!-- アイコン -->
                <div style="flex-shrink: 0; margin-top: 2px;">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border-radius: 8px;
                        background: linear-gradient(135deg, #59B9C6, #3b82f6);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(89, 185, 198, 0.3);
                    ">
                        <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- テキスト部分 -->
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="
                            display: inline-flex;
                            align-items: center;
                            padding: 2px 8px;
                            border-radius: 6px;
                            font-size: 12px;
                            font-weight: 600;
                            background: linear-gradient(to right, #e9d5ff, #fbcfe8);
                            color: #7c3aed;
                        ">
                            ${priorityLabels[notification.priority] || '通常'}
                        </span>
                        <span style="
                            font-size: 12px;
                            color: #6b7280;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        ">
                            管理者: ${notification.sender}
                        </span>
                    </div>
                    <p style="
                        font-weight: 600;
                        color: #111827;
                        font-size: 14px;
                        margin-bottom: 4px;
                        line-height: 1.4;
                    ">
                        ${notification.title}
                    </p>
                    <p style="
                        font-size: 12px;
                        color: #6b7280;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    ">
                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        クリックして詳細を表示
                    </p>
                </div>
                
                <!-- 閉じるボタン -->
                <button class="toast-close-btn" style="
                    flex-shrink: 0;
                    color: #9ca3af;
                    padding: 4px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    border: none;
                    background: transparent;
                " onmouseover="this.style.color='#4b5563'; this.style.background='#f3f4f6';" onmouseout="this.style.color='#9ca3af'; this.style.background='transparent';">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20">
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
    
    console.log('[showDetailToast] Toast added to DOM');
    console.log('[showDetailToast] Toast rect:', toast.getBoundingClientRect());

    // アニメーション（スライドイン）
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
        console.log('[showDetailToast] Animation started');
    }, 50);

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
    toast.className = 'notification-toast';
    
    // ★ インラインスタイルで初期状態を設定
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease-out';
    toast.style.pointerEvents = 'auto';
    toast.style.minWidth = '320px';
    toast.style.cursor = 'pointer';
    
    toast.innerHTML = `
        <div style="
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        ">
            <!-- グラデーションバー（左端） -->
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: linear-gradient(to bottom, #a855f7, #ec4899, #6366f1);
            "></div>
            
            <!-- コンテンツ -->
            <div style="
                padding: 12px 12px 12px 16px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            ">
                <!-- アイコン -->
                <div style="flex-shrink: 0; margin-top: 2px;">
                    <div style="
                        position: relative;
                        width: 40px;
                        height: 40px;
                        border-radius: 8px;
                        background: linear-gradient(135deg, #a855f7, #ec4899, #6366f1);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
                    ">
                        <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                        <!-- 件数バッジ -->
                        <div style="
                            position: absolute;
                            top: -4px;
                            right: -4px;
                            width: 20px;
                            height: 20px;
                            background: #ef4444;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 11px;
                            font-weight: bold;
                            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
                        ">
                            ${count}
                        </div>
                    </div>
                </div>
                
                <!-- テキスト部分 -->
                <div style="flex: 1; min-width: 0;">
                    <p style="
                        font-weight: bold;
                        color: #7c3aed;
                        font-size: 16px;
                        margin-bottom: 4px;
                    ">
                        新着通知 ${count} 件
                    </p>
                    <p style="
                        font-size: 14px;
                        color: #374151;
                        margin-bottom: 4px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    ">
                        最新: ${latestNotification.title}
                    </p>
                    <p style="
                        font-size: 12px;
                        color: #6b7280;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    ">
                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        クリックして一覧を表示
                    </p>
                </div>
                
                <!-- 閉じるボタン -->
                <button class="toast-close-btn" style="
                    flex-shrink: 0;
                    color: #9ca3af;
                    padding: 4px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    border: none;
                    background: transparent;
                " onmouseover="this.style.color='#4b5563'; this.style.background='#f3f4f6';" onmouseout="this.style.color='#9ca3af'; this.style.background='transparent';">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20">
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

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 50);

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