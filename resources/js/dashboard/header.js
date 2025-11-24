/**
 * ダッシュボードヘッダー制御（Vanilla JS実装）
 * 通知カウント表示を管理
 */
class DashboardHeaderController {
    constructor() {
        this.header = null;
        this.notificationCount = 0;
        this.notificationBadge = null;
        this.notificationBadgeText = null;
        
        // 初期化
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    /**
     * 初期化
     */
    init() {
        this.header = document.querySelector('.dashboard-header-blur');
        if (!this.header) {
            console.warn('[Header] Dashboard header not found');
            return;
        }
        
        // 初期の通知カウントを取得（data属性から）
        const initialCount = this.header.dataset.notificationCount;
        this.notificationCount = initialCount ? parseInt(initialCount, 10) : 0;
        
        // 通知バッジ要素
        const notificationBtn = this.header.querySelector('.notification-btn');
        if (notificationBtn) {
            this.notificationBadge = notificationBtn.querySelector('.notification-badge');
            if (this.notificationBadge) {
                this.notificationBadgeText = this.notificationBadge;
            }
        }
        
        console.log('[Header] Initialized:', {
            notificationBadge: !!this.notificationBadge,
            initialNotificationCount: this.notificationCount,
        });
        
        // 初期状態を適用
        this.applyNotificationCount();
        
        // イベントリスナーを設定
        this.setupEventListeners();
    }
    
    /**
     * イベントリスナー設定
     */
    setupEventListeners() {
        // 通知カウント更新イベント（notification-polling.jsから）
        window.addEventListener('notification-count-updated', (event) => {
            console.log('[Header] Notification count updated:', event.detail);
            this.notificationCount = event.detail.count;
            this.applyNotificationCount();
        });
    }
    
    /**
     * 通知カウント表示を更新
     */
    applyNotificationCount() {
        if (!this.notificationBadge || !this.notificationBadgeText) {
            return;
        }
        
        if (this.notificationCount > 0) {
            // バッジを表示
            this.notificationBadge.style.display = '';
            
            // カウント表示（99+で切り捨て）
            const displayCount = this.notificationCount > 99 ? '99+' : this.notificationCount;
            this.notificationBadgeText.textContent = displayCount;
            
            console.log('[Header] Notification badge shown:', displayCount);
        } else {
            // カウントが0ならバッジを非表示
            this.notificationBadge.style.display = 'none';
            console.log('[Header] Notification badge hidden');
        }
    }
}

// インスタンス化（自動初期化）
new DashboardHeaderController();
