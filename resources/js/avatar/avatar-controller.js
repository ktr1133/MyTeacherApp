/**
 * アバターウィジェット制御
 */
function avatarWidget() {
    return {
        visible: false,
        comment: '',
        currentImage: '',
        animationClass: 'avatar-idle',
        displayTimer: null,
        position: {
            x: window.innerWidth - 300,
            y: window.innerHeight - 350,
        },
        isDragging: false,
        dragOffset: { x: 0, y: 0 },

        init() {
            console.log('Avatar widget initialized');
            this.updatePosition();
            
            window.addEventListener('resize', () => {
                this.updatePosition();
            });
        },

        handleEvent(eventData) {
            if (!eventData) return;

            this.comment = eventData.comment;
            this.currentImage = eventData.imageUrl;
            this.animationClass = eventData.animation || 'avatar-idle';
            this.visible = true;

            const duration = 20000;

            clearTimeout(this.displayTimer);
            this.displayTimer = setTimeout(() => {
                this.close();
            }, duration);
        },

        /**
         * アバターを閉じる（ストアとローカル両方をクリア）
         */
        closeAvatar() {
            // Alpine.js ストアを閉じる
            const store = Alpine.store('avatar');
            if (store && typeof store.hide === 'function') {
                store.hide();
            }
            
            // ローカル state もクリア
            this.close();
        },

        close() {
            this.visible = false;
            this.animationClass = 'avatar-idle';
            clearTimeout(this.displayTimer);
        },

        updatePosition() {
            const margin = 20;
            this.position.x = Math.min(
                this.position.x,
                window.innerWidth - 270 - margin
            );
            this.position.y = Math.min(
                this.position.y,
                window.innerHeight - 350 - margin
            );
        },

        startDrag(event) {
            if (event.target.closest('.avatar-close-btn')) {
                return;
            }
            this.isDragging = true;
            this.dragOffset.x = event.clientX - this.position.x;
            this.dragOffset.y = event.clientY - this.position.y;

            const handleMouseMove = (e) => {
                if (!this.isDragging) return;

                this.position.x = e.clientX - this.dragOffset.x;
                this.position.y = e.clientY - this.dragOffset.y;

                const margin = 20;
                this.position.x = Math.max(margin, Math.min(
                    this.position.x,
                    window.innerWidth - 270 - margin
                ));
                this.position.y = Math.max(margin, Math.min(
                    this.position.y,
                    window.innerHeight - 350 - margin
                ));
            };

            const handleMouseUp = () => {
                this.isDragging = false;
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            };

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        }
    };
}

// グローバルに公開
window.avatarWidget = avatarWidget;

/**
 * アバターイベント発火関数
 */
window.dispatchAvatarEvent = function(eventType, additionalData = {}) {
    const startTime = performance.now();
    console.log('[dispatchAvatarEvent] START', {
        eventType,
        additionalData,
        timestamp: new Date().toISOString(),
        url: `/avatars/comment/${eventType}`,
    });
    
    fetch(`/avatars/comment/${eventType}`)
        .then(response => {
            const fetchTime = performance.now() - startTime;
            console.log('[dispatchAvatarEvent] Response received', {
                eventType,
                status: response.status,
                statusText: response.statusText,
                contentType: response.headers.get('content-type'),
                ok: response.ok,
                fetchTime: `${fetchTime.toFixed(2)}ms`,
                timestamp: new Date().toISOString(),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Content-Type が application/json か確認
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
            console.log('[dispatchAvatarEvent] Data received', {
                eventType,
                data,
                totalTime: `${totalTime.toFixed(2)}ms`,
                timestamp: new Date().toISOString(),
            });
            
            if (data.comment) {
                window.dispatchEvent(new CustomEvent('avatar-event', {
                    detail: {
                        comment: data.comment,
                        imageUrl: data.imageUrl,
                        animation: data.animation,
                        ...additionalData
                    }
                }));
                
                console.log('[dispatchAvatarEvent] CustomEvent dispatched', {
                    eventType,
                    timestamp: new Date().toISOString(),
                });
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
    console.log('[showAvatarComment] Called with data:', commentData);
    
    if (!commentData || !commentData.comment) {
        console.warn('[showAvatarComment] No comment data provided');
        return;
    }

    // Alpine.js が読み込まれているか確認
    if (typeof Alpine === 'undefined') {
        console.error('[showAvatarComment] Alpine.js not loaded');
        return;
    }

    const store = Alpine.store('avatar');
    if (!store) {
        console.error('[showAvatarComment] Avatar store not found');
        return;
    }

    // showDirect() メソッドを使用
    if (typeof store.showDirect === 'function') {
        console.log('[showAvatarComment] Calling store.showDirect()');
        store.showDirect(commentData);
    } else {
        // フォールバック: 直接プロパティを更新
        console.warn('[showAvatarComment] showDirect() not found, using fallback');
        
        store.currentComment = commentData.comment;
        store.currentImageUrl = commentData.imageUrl || '';
        store.currentAnimation = commentData.animation || 'avatar-idle';
        store.isVisible = true;

        console.log('[showAvatarComment] Avatar displayed (fallback)', {
            comment: commentData.comment,
            animation: commentData.animation,
        });
    }
};

console.log('[avatar-controller.js] Loaded', {
    timestamp: new Date().toISOString(),
});

// Alpine.js グローバルストア
document.addEventListener('alpine:init', () => {
    console.log('[avatar-controller.js] alpine:init event fired', {
        timestamp: new Date().toISOString(),
    });
    
    Alpine.store('avatar', {
        // アバターの表示状態
        isVisible: false,
        currentComment: '',
        currentImageUrl: '',
        currentAnimation: 'avatar-idle',
        displayTimer: null,

        /**
         * アバターを表示（イベントタイプから取得）
         * @param {string} eventType - イベントタイプ
         */
        show(eventType) {
            console.log('[Alpine Store] show() called', {
                eventType,
                timestamp: new Date().toISOString(),
            });
            window.dispatchAvatarEvent(eventType);
        },

        /**
         * アバターを直接表示（コメントデータから）
         * @param {Object} commentData - { comment, imageUrl, animation }
         */
        showDirect(commentData) {
            console.log('[Alpine Store] showDirect() called', {
                commentData,
                timestamp: new Date().toISOString(),
            });

            if (!commentData || !commentData.comment) {
                console.warn('[Alpine Store] No comment data provided');
                return;
            }

            this.currentComment = commentData.comment;
            this.currentImageUrl = commentData.imageUrl || '';
            this.currentAnimation = commentData.animation || 'avatar-idle';
            this.isVisible = true;

            // 既存のタイマーをクリア
            if (this.displayTimer) {
                clearTimeout(this.displayTimer);
            }

            // 20秒後に自動的に閉じる
            this.displayTimer = setTimeout(() => {
                this.hide();
            }, 20000);

            console.log('[Alpine Store] Avatar displayed', {
                comment: this.currentComment,
                animation: this.currentAnimation,
            });
        },

        /**
         * アバターを非表示
         */
        hide() {
            console.log('[Alpine Store] hide() called', {
                timestamp: new Date().toISOString(),
            });

            this.isVisible = false;
            this.currentAnimation = 'avatar-idle';

            if (this.displayTimer) {
                clearTimeout(this.displayTimer);
                this.displayTimer = null;
            }
        }
    });
});
