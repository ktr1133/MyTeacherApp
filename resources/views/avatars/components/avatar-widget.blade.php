@if($avatar && $avatar->is_visible)
    <div 
        x-data="avatarWidget()" 
        x-show="($store.avatar.isVisible || visible) && !isForcedHidden"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="avatar-widget"
        :style="`left: ${position.x}px; top: ${position.y}px;`"
        @mousedown="startDrag($event)"
        @avatar-event.window="handleEvent($event.detail)"
        x-cloak
    >
        <div class="avatar-container">
            {{-- 吹き出し（ストアまたはローカル state から取得） --}}
            <div 
                class="avatar-bubble" 
                x-show="$store.avatar.currentComment || comment"
            >
                <p 
                    class="text-sm text-gray-900 dark:text-white" 
                    x-text="$store.avatar.currentComment || comment"
                ></p>
                <div class="avatar-bubble-arrow"></div>
            </div>

            {{-- アバター画像（ストアまたはローカル state から取得） --}}
            <img 
                :src="$store.avatar.currentImageUrl || currentImage" 
                alt="Teacher Avatar"
                class="avatar-image"
                :class="$store.avatar.currentAnimation || animationClass"
            />

            {{-- 閉じるボタン --}}
            <button 
                @click.stop="
                    console.log('[Avatar Widget] Close button clicked');
                    console.log('[Avatar Widget] Before close:', { 
                        visible: visible, 
                        storeVisible: $store.avatar.isVisible,
                        isForcedHidden: isForcedHidden
                    });
                    closeAvatar();
                    console.log('[Avatar Widget] After close:', { 
                        visible: visible, 
                        storeVisible: $store.avatar.isVisible,
                        isForcedHidden: isForcedHidden
                    });
                " 
                class="avatar-close-btn"
                type="button"
                title="閉じる"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
@endif

<style>
    .avatar-widget {
        position: fixed;
        z-index: 9999;
        cursor: move;
    }

    /* ★ 強制非表示クラス（!important で確実に非表示） */
    .avatar-force-hidden {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
        visibility: hidden !important;
    }

    .avatar-container {
        position: relative;
    }

    .avatar-image {
        width: 270px;
        height: auto;
        object-fit: contain;
    }

    .avatar-bubble {
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: 1rem;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        padding: 1rem;
        max-width: 20rem;
        min-width: 12rem;
    }

    .avatar-bubble-arrow {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translate(-50%, 100%);
        width: 0;
        height: 0;
        border-left: 0.5rem solid transparent;
        border-right: 0.5rem solid transparent;
        border-top: 0.5rem solid white;
    }

    .avatar-close-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: rgb(249, 64, 64);
        border-radius: 9999px;
        padding: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: background-color 0.2s;
        color: white;
    }

    .avatar-close-btn:hover {
        background: rgb(220, 38, 38);
    }

    /* アニメーション */
    .avatar-idle {
        animation: idle 3s ease-in-out infinite;
    }

    .avatar-secretary {
        animation: secretary 2s ease-in-out infinite;
    }

    @keyframes idle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    @keyframes secretary {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        25% { transform: translateY(-3px) rotate(-2deg); }
        75% { transform: translateY(-3px) rotate(2deg); }
    }
</style>