/**
 * サイドバー制御クラス（純粋JavaScript実装 - Alpine.js不使用）
 * デスクトップサイドバーの開閉、モバイルサイドバーの表示/非表示、
 * 管理者用の一般メニュー表示切替、ポータルメニュー展開機能を提供
 */
class SidebarController {
    constructor() {
        // 状態管理
        this.isCollapsed = this.loadState('sidebar-collapsed', false);
        this.isMobileOpen = false;
        this.showGeneralMenu = this.loadState('sidebar-general-menu', true);
        this.portalExpanded = this.loadState('sidebar-portal-expanded', false);
        this.portalExpandedMobile = this.loadState('sidebar-portal-expanded-mobile', false);
        
        // DOM要素は初期化時に取得
        this.desktopSidebar = null;
        this.mobileSidebar = null;
        this.mobileOverlay = null;
        
        // 初期化の遅延実行（DOMが完全に構築されるまで待つ）
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
        // DOM要素を取得
        this.desktopSidebar = document.querySelector('[data-sidebar="desktop"]');
        this.mobileSidebar = document.querySelector('[data-sidebar="mobile"]');
        this.mobileOverlay = document.querySelector('[data-sidebar-overlay="mobile"]');
        
        console.log('[Sidebar] DOM elements found:', {
            desktopSidebar: !!this.desktopSidebar,
            mobileSidebar: !!this.mobileSidebar,
            mobileOverlay: !!this.mobileOverlay,
        });
        
        // デスクトップサイドバーの初期状態を適用
        if (this.desktopSidebar) {
            this.applyDesktopState();
            this.applyGeneralMenuState();
        }
        
        // ポータルメニューの初期状態を適用
        this.applyPortalState();
        
        // イベントリスナーを設定
        this.setupEventListeners();
    }
    
    /**
     * ローカルストレージから状態を読み込み
     */
    loadState(key, defaultValue) {
        try {
            const saved = localStorage.getItem(key);
            return saved === null ? defaultValue : saved === 'true';
        } catch (error) {
            console.warn(`[Sidebar] Failed to load state from localStorage (${key}):`, error);
            return defaultValue;
        }
    }
    
    /**
     * ローカルストレージに状態を保存
     */
    saveState(key, value) {
        try {
            localStorage.setItem(key, value.toString());
        } catch (error) {
            console.warn(`[Sidebar] Failed to save state to localStorage (${key}):`, error);
        }
    }
    
    /**
     * デスクトップサイドバーの状態を適用
     */
    applyDesktopState() {
        if (!this.desktopSidebar) return;
        
        // サイドバー全体のクラス制御（collapsedクラスでアニメーション）
        if (this.isCollapsed) {
            this.desktopSidebar.classList.add('collapsed');
            this.desktopSidebar.classList.remove('gap-3', 'px-3', 'py-2');
            this.desktopSidebar.classList.add('justify-center', 'p-3');
        } else {
            this.desktopSidebar.classList.remove('collapsed');
            this.desktopSidebar.classList.add('gap-3', 'px-3', 'py-2');
            this.desktopSidebar.classList.remove('justify-center', 'p-3');
        }
        
        // 展開時のみ表示する要素
        const showWhenExpanded = this.desktopSidebar.querySelectorAll('[data-show-when="expanded"]');
        showWhenExpanded.forEach(el => {
            el.style.display = this.isCollapsed ? 'none' : '';
        });
        
        // 最小化時のみ表示する要素
        const showWhenCollapsed = this.desktopSidebar.querySelectorAll('[data-show-when="collapsed"]');
        showWhenCollapsed.forEach(el => {
            el.style.display = this.isCollapsed ? '' : 'none';
        });
        
        // トグルボタンのアイコン切り替え
        const expandIcon = this.desktopSidebar.querySelector('[data-icon="expand"]');
        const collapseIcon = this.desktopSidebar.querySelector('[data-icon="collapse"]');
        if (expandIcon) expandIcon.style.display = this.isCollapsed ? '' : 'none';
        if (collapseIcon) collapseIcon.style.display = this.isCollapsed ? 'none' : '';
        
        // aria属性の更新
        const toggleBtn = this.desktopSidebar.querySelector('[data-sidebar-action="toggle-desktop"]');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', (!this.isCollapsed).toString());
            toggleBtn.setAttribute(
                'aria-label', 
                this.isCollapsed ? 'サイドバーを展開' : 'サイドバーを最小化'
            );
        }
    }
    
    /**
     * 一般メニューの表示状態を適用
     */
    applyGeneralMenuState() {
        if (!this.desktopSidebar) return;
        
        const generalMenuContainer = this.desktopSidebar.querySelector('[data-general-menu]');
        if (generalMenuContainer) {
            generalMenuContainer.style.display = this.showGeneralMenu ? '' : 'none';
        }
        
        // トグルボタンのアイコン切り替え
        const showIcon = this.desktopSidebar.querySelector('[data-icon="general-show"]');
        const hideIcon = this.desktopSidebar.querySelector('[data-icon="general-hide"]');
        if (showIcon) showIcon.style.display = this.showGeneralMenu ? '' : 'none';
        if (hideIcon) hideIcon.style.display = this.showGeneralMenu ? 'none' : '';
        
        // aria属性の更新
        const toggleBtn = this.desktopSidebar.querySelector('[data-action="toggle-general-menu"]');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-label', 
                this.showGeneralMenu ? '一般メニューを非表示' : '一般メニューを表示'
            );
        }
    }
    
    /**
     * ポータルメニューの展開状態を適用
     */
    applyPortalState() {
        // デスクトップ
        if (this.desktopSidebar) {
            const portalSubmenu = this.desktopSidebar.querySelector('[data-portal-submenu]');
            if (portalSubmenu) {
                portalSubmenu.style.display = this.portalExpanded ? '' : 'none';
            }
            
            const portalIcon = this.desktopSidebar.querySelector('[data-portal-icon]');
            if (portalIcon) {
                portalIcon.classList.toggle('rotate-180', this.portalExpanded);
            }
        }
        
        // モバイル
        if (this.mobileSidebar) {
            const portalSubmenuMobile = this.mobileSidebar.querySelector('[data-portal-submenu-mobile]');
            if (portalSubmenuMobile) {
                portalSubmenuMobile.style.display = this.portalExpandedMobile ? '' : 'none';
            }
            
            const portalIconMobile = this.mobileSidebar.querySelector('[data-portal-icon-mobile]');
            if (portalIconMobile) {
                portalIconMobile.classList.toggle('rotate-180', this.portalExpandedMobile);
            }
        }
    }
    
    /**
     * デスクトップサイドバーのトグル
     */
    toggleDesktop() {
        this.isCollapsed = !this.isCollapsed;
        this.saveState('sidebar-collapsed', this.isCollapsed);
        this.applyDesktopState();
    }
    
    /**
     * 一般メニューの表示切替
     */
    toggleGeneralMenu() {
        this.showGeneralMenu = !this.showGeneralMenu;
        this.saveState('sidebar-general-menu', this.showGeneralMenu);
        this.applyGeneralMenuState();
    }
    
    /**
     * ポータルメニューの展開切替（デスクトップ）
     */
    togglePortal() {
        this.portalExpanded = !this.portalExpanded;
        this.saveState('sidebar-portal-expanded', this.portalExpanded);
        this.applyPortalState();
    }
    
    /**
     * ポータルメニューの展開切替（モバイル）
     */
    togglePortalMobile() {
        this.portalExpandedMobile = !this.portalExpandedMobile;
        this.saveState('sidebar-portal-expanded-mobile', this.portalExpandedMobile);
        this.applyPortalState();
    }
    
    /**
     * モバイルサイドバーを開く
     */
    openMobile() {
        if (!this.mobileSidebar || !this.mobileOverlay) {
            console.warn('[Sidebar] Mobile sidebar or overlay not found');
            return;
        }

        this.isMobileOpen = true;
        
        // オーバーレイとサイドバーを表示
        this.mobileOverlay.classList.remove('hidden');
        this.mobileSidebar.classList.remove('hidden');
        
        // 初期状態を確実に設定（透明＆画面外）
        this.mobileOverlay.classList.remove('opacity-100');
        this.mobileOverlay.classList.add('opacity-0');
        this.mobileSidebar.classList.add('-translate-x-full');
        this.mobileSidebar.classList.remove('translate-x-0');
        
        // 次フレームでアニメーション開始（ブラウザに初期状態を認識させる）
        requestAnimationFrame(() => {
            this.mobileOverlay.classList.remove('opacity-0');
            this.mobileOverlay.classList.add('opacity-100');
            
            this.mobileSidebar.classList.remove('-translate-x-full');
            this.mobileSidebar.classList.add('translate-x-0');
        });
        
        // スクロールを無効化
        document.body.style.overflow = 'hidden';
        
        console.log('[Sidebar] Mobile sidebar opened');
    }
    
    /**
     * モバイルサイドバーを閉じる
     */
    closeMobile() {
        if (!this.mobileSidebar || !this.mobileOverlay) {
            console.warn('[Sidebar] Mobile sidebar or overlay not found');
            return;
        }
        
        this.isMobileOpen = false;
        
        // アニメーション開始（透明化＆画面外へ移動）
        this.mobileOverlay.classList.remove('opacity-100');
        this.mobileOverlay.classList.add('opacity-0');
        
        this.mobileSidebar.classList.add('-translate-x-full');
        this.mobileSidebar.classList.remove('translate-x-0');
        
        // アニメーション終了後にhiddenクラスを追加（300msはtransition-durationと同期）
        setTimeout(() => {
            this.mobileOverlay.classList.add('hidden');
            this.mobileSidebar.classList.add('hidden');
        }, 300);
        
        // スクロールを有効化
        document.body.style.overflow = '';
        
        console.log('[Sidebar] Mobile sidebar closed');
    }
    
    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        // デスクトップ: トグルボタン
        const desktopToggleBtn = this.desktopSidebar?.querySelector('[data-sidebar-action="toggle-desktop"]');
        if (desktopToggleBtn) {
            desktopToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDesktop();
            });
        }
        
        // デスクトップ: 一般メニュー表示切替ボタン
        const generalMenuToggleBtn = this.desktopSidebar?.querySelector('[data-action="toggle-general-menu"]');
        if (generalMenuToggleBtn) {
            generalMenuToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleGeneralMenu();
            });
        }
        
        // デスクトップ: ポータルメニュー展開ボタン
        const portalToggleBtn = this.desktopSidebar?.querySelector('[data-action="toggle-portal"]');
        if (portalToggleBtn) {
            portalToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.togglePortal();
            });
        }
        
        // モバイル: 一般メニュー表示切替ボタン
        const generalMenuToggleBtnMobile = this.mobileSidebar?.querySelector('[data-action="toggle-general-menu-mobile"]');
        if (generalMenuToggleBtnMobile) {
            generalMenuToggleBtnMobile.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleGeneralMenu(); // デスクトップと状態を共有
            });
        }
        
        // モバイル: ポータルメニュー展開ボタン
        const portalToggleBtnMobile = this.mobileSidebar?.querySelector('[data-action="toggle-portal-mobile"]');
        if (portalToggleBtnMobile) {
            portalToggleBtnMobile.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.togglePortalMobile();
            });
        }
        
        // モバイル: オーバーレイクリックで閉じる
        if (this.mobileOverlay) {
            this.mobileOverlay.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobile();
            });
            
            // タッチイベント（iPad対応）
            this.mobileOverlay.addEventListener('touchstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobile();
            }, { passive: false });
        }
        
        // モバイル: 閉じるボタン
        const mobileCloseBtn = this.mobileSidebar?.querySelector('[data-action="close-mobile"]');
        if (mobileCloseBtn) {
            mobileCloseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobile();
            });
            
            // タッチイベント
            mobileCloseBtn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobile();
            }, { passive: false });
        }
        
        // モバイル: リンククリックで閉じる
        const mobileLinks = this.mobileSidebar?.querySelectorAll('[data-close-on-click]');
        if (mobileLinks) {
            mobileLinks.forEach((link) => {
                link.addEventListener('click', () => {
                    setTimeout(() => this.closeMobile(), 100);
                });
            });
            console.log(`[Sidebar] Mobile link listeners added: ${mobileLinks.length} links`);
        }
        
        // ハンバーガーメニューボタン（イベント委譲で処理）
        document.addEventListener('click', (e) => {
            const hamburgerBtn = e.target.closest('[data-sidebar-toggle="mobile"]');
            if (hamburgerBtn) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Sidebar] Hamburger button clicked');
                this.openMobile();
            }
        });
        
        // タッチイベント（iPad対応）
        document.addEventListener('touchstart', (e) => {
            const hamburgerBtn = e.target.closest('[data-sidebar-toggle="mobile"]');
            if (hamburgerBtn) {
                e.preventDefault();
                e.stopPropagation();
                this.openMobile();
            }
        }, { passive: false });
        
        console.log('[Sidebar] Event listeners setup completed');
    }
}

// グローバル変数として公開
window.SidebarController = SidebarController;

// DOM読み込み完了後に初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sidebarController = new SidebarController();
    });
} else {
    window.sidebarController = new SidebarController();
}

// エクスポート（モジュールとして使用する場合）
export default SidebarController;