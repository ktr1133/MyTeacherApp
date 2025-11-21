/**
 * サイドバー制御クラス（Alpine.js代替）
 * iPadを含むすべてのデバイスで動作する純粋なJavaScript実装
 */
class SidebarController {
    constructor() {
        console.log('[Sidebar] Constructor called');
        
        // DOM要素の取得（より正確なセレクター）
        this.desktopSidebar = document.querySelector('aside[x-data*="sidebar"]');
        this.mobileSidebar = document.querySelector('.lg\\:hidden aside');
        this.mobileOverlay = document.querySelector('.lg\\:hidden > div[x-show*="showSidebar"]');
        
        // ボタン要素
        this.desktopToggleBtn = document.querySelector('button[\\@click*="sidebar.toggle"]');
        this.mobileCloseBtn = document.querySelector('.lg\\:hidden button[\\@click*="showSidebar = false"]');
        
        // モバイルのナビゲーションリンク
        this.mobileLinks = document.querySelectorAll('.lg\\:hidden x-nav-link[\\@click*="showSidebar = false"]');
        
        // 状態管理
        this.isCollapsed = this.loadState();
        this.isMobileOpen = false;
        
        console.log('[Sidebar] DOM elements found:', {
            desktopSidebar: !!this.desktopSidebar,
            mobileSidebar: !!this.mobileSidebar,
            mobileOverlay: !!this.mobileOverlay,
            desktopToggleBtn: !!this.desktopToggleBtn,
            mobileCloseBtn: !!this.mobileCloseBtn,
            mobileLinksCount: this.mobileLinks.length,
        });
        
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
        console.log('[Sidebar] Initializing...');
        
        // Alpine.jsのストアをエミュレート（互換性のため）
        this.setupAlpineCompatibility();
        
        // デスクトップサイドバーの初期状態を適用
        if (this.desktopSidebar) {
            this.applyDesktopState();
        }
        
        // イベントリスナーを設定
        this.setupEventListeners();
        
        console.log('[Sidebar] Initialized successfully', {
            isCollapsed: this.isCollapsed,
            initialState: this.isCollapsed ? 'collapsed' : 'expanded',
        });
    }
    
    /**
     * ローカルストレージから状態を読み込み
     */
    loadState() {
        try {
            const saved = localStorage.getItem('sidebar-collapsed');
            console.log('[Sidebar] Loaded state from localStorage:', saved);
            return saved === 'true';
        } catch (error) {
            console.warn('[Sidebar] Failed to load state from localStorage:', error);
            return false; // デフォルトは展開
        }
    }
    
    /**
     * ローカルストレージに状態を保存
     */
    saveState() {
        try {
            localStorage.setItem('sidebar-collapsed', this.isCollapsed.toString());
            console.log('[Sidebar] State saved to localStorage:', this.isCollapsed);
        } catch (error) {
            console.warn('[Sidebar] Failed to save state to localStorage:', error);
        }
    }
    
    /**
     * デスクトップサイドバーの状態を適用
     */
    applyDesktopState() {
        if (!this.desktopSidebar) {
            console.warn('[Sidebar] Desktop sidebar element not found');
            return;
        }
        
        // Alpine.jsの :class バインディングをJSで再現
        if (this.isCollapsed) {
            this.desktopSidebar.classList.remove('gap-3', 'px-3', 'py-2');
            this.desktopSidebar.classList.add('justify-center', 'p-3');
        } else {
            this.desktopSidebar.classList.add('gap-3', 'px-3', 'py-2');
            this.desktopSidebar.classList.remove('justify-center', 'p-3');
        }
        
        // x-show ディレクティブの要素を手動で表示/非表示
        const showWhenExpanded = this.desktopSidebar.querySelectorAll('[x-show*="!collapsed"]');
        const showWhenCollapsed = this.desktopSidebar.querySelectorAll('[x-show*="collapsed"]:not([x-show*="!collapsed"])');
        
        showWhenExpanded.forEach(el => {
            if (this.isCollapsed) {
                el.style.display = 'none';
            } else {
                el.style.display = '';
            }
        });
        
        showWhenCollapsed.forEach(el => {
            if (this.isCollapsed) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
        
        // トグルボタンのaria属性を更新
        if (this.desktopToggleBtn) {
            this.desktopToggleBtn.setAttribute('aria-expanded', (!this.isCollapsed).toString());
            this.desktopToggleBtn.setAttribute(
                'aria-label', 
                this.isCollapsed ? 'サイドバーを展開' : 'サイドバーを最小化'
            );
        }
        
        console.log('[Sidebar] Desktop state applied:', this.isCollapsed ? 'collapsed' : 'expanded');
    }
    
    /**
     * デスクトップサイドバーのトグル
     */
    toggleDesktop() {
        this.isCollapsed = !this.isCollapsed;
        this.saveState();
        this.applyDesktopState();
        
        // Alpine.jsのストアも更新（互換性のため）
        if (window.Alpine?.stores?.sidebar) {
            window.Alpine.stores.sidebar.isCollapsed = this.isCollapsed;
        }
        
        console.log('[Sidebar] Desktop toggled:', this.isCollapsed ? 'collapsed' : 'expanded');
    }
    
    /**
     * モバイルサイドバーを開く
     */
    openMobile() {
        if (!this.mobileSidebar || !this.mobileOverlay) {
            console.warn('[Sidebar] Mobile sidebar or overlay not found');
            return;
        }
        
        console.log('[Sidebar] Opening mobile sidebar...');
        
        this.isMobileOpen = true;
        
        // オーバーレイを表示
        this.mobileOverlay.style.display = 'block';
        
        // サイドバーを表示（x-showの代替）
        this.mobileSidebar.style.display = 'block';
        
        // アニメーション用のクラスを追加（Alpine.jsのx-transitionの代替）
        requestAnimationFrame(() => {
            this.mobileOverlay.classList.add('opacity-100');
            this.mobileOverlay.classList.remove('opacity-0');
            
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
        
        console.log('[Sidebar] Closing mobile sidebar...');
        
        this.isMobileOpen = false;
        
        // アニメーション開始
        this.mobileOverlay.classList.remove('opacity-100');
        this.mobileOverlay.classList.add('opacity-0');
        
        this.mobileSidebar.classList.add('-translate-x-full');
        this.mobileSidebar.classList.remove('translate-x-0');
        
        // アニメーション終了後に非表示
        setTimeout(() => {
            this.mobileOverlay.style.display = 'none';
            this.mobileSidebar.style.display = 'none';
        }, 200);
        
        // スクロールを有効化
        document.body.style.overflow = '';
        
        console.log('[Sidebar] Mobile sidebar closed');
    }
    
    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        console.log('[Sidebar] Setting up event listeners...');
        
        // デスクトップ: トグルボタン
        if (this.desktopToggleBtn) {
            this.desktopToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDesktop();
            });
            console.log('[Sidebar] Desktop toggle button listener added');
        } else {
            console.warn('[Sidebar] Desktop toggle button not found');
        }
        
        // モバイル: オーバーレイクリックで閉じる
        if (this.mobileOverlay) {
            // クリックイベント
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
            
            console.log('[Sidebar] Mobile overlay listeners added (click + touch)');
        } else {
            console.warn('[Sidebar] Mobile overlay not found');
        }
        
        // モバイル: 閉じるボタン
        if (this.mobileCloseBtn) {
            this.mobileCloseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobile();
            });
            
            // タッチイベント
            this.mobileCloseBtn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobile();
            }, { passive: false });
            
            console.log('[Sidebar] Mobile close button listeners added');
        } else {
            console.warn('[Sidebar] Mobile close button not found');
        }
        
        // モバイル: リンククリックで閉じる
        this.mobileLinks.forEach((link, index) => {
            link.addEventListener('click', () => {
                console.log(`[Sidebar] Mobile link ${index} clicked, closing sidebar`);
                setTimeout(() => this.closeMobile(), 100);
            });
        });
        console.log(`[Sidebar] Mobile link listeners added: ${this.mobileLinks.length} links`);
        
        // ヘッダーのハンバーガーメニューボタン（複数のセレクタで検索）
        const hamburgerBtn = document.querySelector('[data-sidebar-toggle="mobile"]') 
            || document.querySelector('button[\\@click*="toggleSidebar"]')
            || document.querySelector('button[\\@click*="showSidebar = true"]')
            || document.querySelector('.lg\\:hidden button[aria-label*="メニュー"]');
        
        if (hamburgerBtn) {
            // クリックイベント
            hamburgerBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Sidebar] Hamburger button clicked');
                this.openMobile();
            });
            
            // タッチイベント（iPad対応）
            hamburgerBtn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openMobile();
            }, { passive: false });
            
            console.log('[Sidebar] Hamburger button listeners added');
        } else {
            console.warn('[Sidebar] Hamburger button not found');
        }
        
        console.log('[Sidebar] Event listeners setup complete');
    }
    
    /**
     * Alpine.jsのストアとの互換性を提供
     */
    setupAlpineCompatibility() {
        console.log('[Sidebar] Setting up Alpine.js compatibility...');
        
        // Alpine.jsが読み込まれる前の場合
        if (typeof window.Alpine === 'undefined') {
            window.Alpine = {
                stores: {},
                store: (name, data) => {
                    window.Alpine.stores[name] = data;
                    console.log(`[Sidebar] Alpine.store('${name}') registered (polyfill)`);
                }
            };
        }
        
        // Alpine.storesが存在しない場合
        if (!window.Alpine.stores) {
            window.Alpine.stores = {};
        }
        
        // sidebarストアを作成（既存のsidebar-store.jsと互換性を保つ）
        const sidebarStore = {
            isCollapsed: this.isCollapsed,
            toggle: () => {
                console.log('[Sidebar] Alpine store toggle() called');
                this.toggleDesktop();
            },
            expand: () => {
                console.log('[Sidebar] Alpine store expand() called');
                if (this.isCollapsed) {
                    this.toggleDesktop();
                }
            },
            collapse: () => {
                console.log('[Sidebar] Alpine store collapse() called');
                if (!this.isCollapsed) {
                    this.toggleDesktop();
                }
            }
        };
        
        // Proxy で isCollapsed の変更を監視
        window.Alpine.stores.sidebar = new Proxy(sidebarStore, {
            set: (target, prop, value) => {
                console.log(`[Sidebar] Alpine store.${prop} set to:`, value);
                target[prop] = value;
                
                // isCollapsed が変更されたら状態を同期
                if (prop === 'isCollapsed' && value !== this.isCollapsed) {
                    this.isCollapsed = value;
                    this.saveState();
                    this.applyDesktopState();
                }
                
                return true;
            }
        });
        
        console.log('[Sidebar] Alpine.js compatibility layer added', window.Alpine.stores.sidebar);
        
        // Alpine.jsが初期化されたときにストアを再登録（確実に認識させるため）
        document.addEventListener('alpine:init', () => {
            console.log('[Sidebar] Alpine.js initialized, re-registering store...');
            if (window.Alpine && window.Alpine.store) {
                window.Alpine.store('sidebar', sidebarStore);
                console.log('[Sidebar] Store re-registered successfully');
            }
        });
    }
}

// グローバル変数として公開
window.SidebarController = SidebarController;

// DOM読み込み完了後に初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[Sidebar] DOM loaded, initializing...');
        window.sidebarController = new SidebarController();
    });
} else {
    console.log('[Sidebar] Document already loaded, initializing immediately...');
    window.sidebarController = new SidebarController();
}

// エクスポート（モジュールとして使用する場合）
export default SidebarController;