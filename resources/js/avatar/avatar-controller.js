/**
 * アバターウィジェット制御（Alpine.js不使用版）
 */
window.AvatarManager = {
    // DOM要素
    widget: null,
    container: null,
    bubble: null,
    bubbleText: null,
    avatarImage: null,
    closeBtn: null,

    // 状態管理
    isVisible: false,
    isForcedHidden: false,
    displayTimer: null,
    
    // ドラッグ関連
    position: {
        x: 0,
        y: 0
    },
    isDragging: false,
    dragOffset: { x: 0, y: 0 },

    /**
     * 初期化
     */
    init() {
        console.log('[AvatarManager] Initializing...');
        
        // DOM要素を取得
        this.widget = document.getElementById('avatar-widget');
        
        if (!this.widget) {
            console.warn('[AvatarManager] Widget element not found');
            return;
        }

        this.container = this.widget.querySelector('.avatar-container');
        this.bubble = this.widget.querySelector('.avatar-bubble');
        this.bubbleText = this.bubble?.querySelector('p');
        this.avatarImage = this.widget.querySelector('.avatar-image');
        this.closeBtn = this.widget.querySelector('.avatar-close-btn');

        // 初期位置を設定
        this.setInitialPosition();

        // イベントリスナーを設定
        this.setupEventListeners();

        console.log('[AvatarManager] Initialized successfully');
    },

    /**
     * 初期位置を設定（右下）
     */
    setInitialPosition() {
        const margin = 20;
        this.position.x = window.innerWidth - 270 - margin;
        this.position.y = window.innerHeight - 350 - margin;
        this.updatePosition();
    },

    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        // 閉じるボタン
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log('[AvatarManager] Close button clicked');
                this.forceHide();
            });
        }

        // カスタムイベント（統一された表示トリガー）
        window.addEventListener('avatar-event', (event) => {
            console.log('[AvatarManager] Avatar event received:', event.detail);
            this.handleEvent(event.detail);
        });

        // ドラッグ開始（マウス）
        this.widget.addEventListener('mousedown', (e) => {
            this.startDrag(e);
        });

        // ドラッグ開始（タッチ）
        this.widget.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                this.startDrag(e.touches[0]);
            }
        }, { passive: false });

        // ウィンドウリサイズ
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.adjustPositionOnResize();
            }, 100);
        });
    },

    /**
     * アバターイベントを処理
     * @param {Object} eventData - { comment, imageUrl, animation }
     */
    handleEvent(eventData) {
        if (!eventData || !eventData.comment) {
            console.warn('[AvatarManager] Invalid event data:', eventData);
            return;
        }

        // 強制非表示フラグをクリア（新しいイベントで再表示を許可）
        this.isForcedHidden = false;
        this.widget.classList.remove('avatar-force-hidden');

        // コンテンツを更新
        this.updateContent(eventData);

        // 表示
        this.show();

        // 20秒後に自動非表示
        this.setAutoHideTimer();
    },

    /**
     * コンテンツを更新
     * @param {Object} data - { comment, imageUrl, animation, expression }
     */
    updateContent(data) {
        // 吹き出しのテキストを更新
        if (this.bubbleText) {
            this.bubbleText.textContent = data.comment || '';
        }

        // アバター画像を更新
        if (this.avatarImage) {
            // 画像URLの決定（優先順位: データ指定 > 表情別 > デフォルト）
            let imageUrl = data.imageUrl;
            
            if (!imageUrl && data.expression) {
                // data-*-image 属性から表情別の画像を取得
                const expressionKey = `${data.expression}Image`;
                imageUrl = this.widget.dataset[expressionKey];
            }
            
            if (!imageUrl) {
                // デフォルト画像を使用
                imageUrl = this.widget.dataset.defaultImage;
            }
            
            if (imageUrl) {
                this.avatarImage.src = imageUrl;
            }

            // アニメーションクラスを更新
            const animationClass = data.animation || 'avatar-idle';
            this.avatarImage.className = `avatar-image ${animationClass}`;
        }
    },

    /**
     * アバターを表示
     */
    show() {
        if (this.isForcedHidden) {
            console.log('[AvatarManager] Widget is force hidden, ignoring show()');
            return;
        }
        
        this.widget.classList.remove('hidden', 'opacity-0', 'translate-y-4');
        this.widget.classList.add('opacity-100', 'translate-y-0');
        
        this.isVisible = true;
    },

    /**
     * アバターを非表示（一時的）
     */
    hide() {
        console.log('[AvatarManager] Hiding widget');
        
        this.widget.classList.remove('opacity-100', 'translate-y-0');
        this.widget.classList.add('opacity-0', 'translate-y-4');
        
        setTimeout(() => {
            this.widget.classList.add('hidden');
        }, 200);

        this.isVisible = false;
        this.clearTimer();
    },

    /**
     * アバターを強制非表示（閉じるボタン押下時）
     */
    forceHide() {
        this.hide();
        this.isForcedHidden = true;
        this.widget.classList.add('avatar-force-hidden');
    },

    /**
     * 自動非表示タイマーを設定
     */
    setAutoHideTimer() {
        this.clearTimer();
        
        this.displayTimer = setTimeout(() => {
            this.forceHide();
        }, 20000);
    },

    /**
     * タイマーをクリア
     */
    clearTimer() {
        if (this.displayTimer) {
            clearTimeout(this.displayTimer);
            this.displayTimer = null;
        }
    },

    /**
     * 位置を更新
     */
    updatePosition() {
        const margin = 20;
        
        // 画面外に出ないように制限
        this.position.x = Math.max(margin, Math.min(
            this.position.x,
            window.innerWidth - 270 - margin
        ));
        this.position.y = Math.max(margin, Math.min(
            this.position.y,
            window.innerHeight - 350 - margin
        ));

        // スタイルを適用
        this.widget.style.left = `${this.position.x}px`;
        this.widget.style.top = `${this.position.y}px`;
    },

    /**
     * ウィンドウリサイズ時の位置調整
     */
    adjustPositionOnResize() {
        const margin = 20;
        this.position.x = Math.min(
            this.position.x,
            window.innerWidth - 270 - margin
        );
        this.position.y = Math.min(
            this.position.y,
            window.innerHeight - 350 - margin
        );
        this.updatePosition();

        // 強制非表示状態を維持
        if (this.isForcedHidden) {
            this.widget.classList.add('avatar-force-hidden');
        }
    },

    /**
     * ドラッグ開始（マウス + タッチ対応）
     * @param {MouseEvent|Touch} event
     */
    startDrag(event) {
        // 閉じるボタンをクリックした場合は無視
        if (event.target.closest('.avatar-close-btn')) {
            return;
        }

        this.isDragging = true;

        const clientX = event.clientX || event.pageX;
        const clientY = event.clientY || event.pageY;

        this.dragOffset.x = clientX - this.position.x;
        this.dragOffset.y = clientY - this.position.y;

        // マウス移動ハンドラー
        const handleMove = (e) => {
            if (!this.isDragging) return;

            e.preventDefault();

            const moveEvent = e.touches ? e.touches[0] : e;
            this.position.x = moveEvent.clientX - this.dragOffset.x;
            this.position.y = moveEvent.clientY - this.dragOffset.y;

            this.updatePosition();
        };

        // ドラッグ終了ハンドラー
        const handleEnd = () => {
            this.isDragging = false;

            document.removeEventListener('mousemove', handleMove);
            document.removeEventListener('mouseup', handleEnd);
            document.removeEventListener('touchmove', handleMove);
            document.removeEventListener('touchend', handleEnd);
        };

        // イベントリスナーを追加
        document.addEventListener('mousemove', handleMove);
        document.addEventListener('mouseup', handleEnd);
        document.addEventListener('touchmove', handleMove, { passive: false });
        document.addEventListener('touchend', handleEnd);
    }
};

/**
 * アバターイベント発火関数（統一された表示トリガー）
 * @param {string} eventType - イベントタイプ（task_created, group_task_created等）
 */
window.dispatchAvatarEvent = function(eventType) {
    const startTime = performance.now();

    fetch(`/avatars/comment/${eventType}`)
        .then(response => {
            const fetchTime = performance.now() - startTime;

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('[dispatchAvatarEvent] Expected JSON', {
                    contentType,
                    eventType,
                });
                throw new Error(`Expected JSON, got ${contentType}`);
            }

            return response.json();
        })
        .then(data => {
            const totalTime = performance.now() - startTime;

            if (data.comment) {
                // カスタムイベントを発火
                window.dispatchEvent(new CustomEvent('avatar-event', {
                    detail: {
                        comment: data.comment,
                        imageUrl: data.imageUrl,
                        animation: data.animation,
                    }
                }));
            } else {
                console.warn('[dispatchAvatarEvent] No comment in response', {
                    eventType,
                    data,
                });
            }
        })
        .catch(error => {
            const totalTime = performance.now() - startTime;
            console.error('[dispatchAvatarEvent] ERROR', {
                eventType,
                error: error.message,
                stack: error.stack,
                totalTime: `${totalTime.toFixed(2)}ms`,
                timestamp: new Date().toISOString(),
            });
        });
};

/**
 * アバターコメントを直接表示（API呼び出しなし）
 * @param {Object} commentData - { comment, imageUrl, animation }
 */
window.showAvatarComment = function(commentData) {
    if (!commentData || !commentData.comment) {
        console.warn('[showAvatarComment] No comment data provided');
        return;
    }

    // AvatarManagerが初期化されているか確認
    if (!window.AvatarManager || !window.AvatarManager.widget) {
        console.error('[showAvatarComment] AvatarManager not initialized');
        return;
    }

    // カスタムイベントを発火
    window.dispatchEvent(new CustomEvent('avatar-event', {
        detail: commentData
    }));
};

// DOM読み込み完了後に初期化
document.addEventListener('DOMContentLoaded', () => {
    // 初期化を少し遅延（DOM が完全にレンダリングされるまで待つ）
    setTimeout(() => {
        if (window.AvatarManager) {
            window.AvatarManager.init();
        }
    }, 100);
});
