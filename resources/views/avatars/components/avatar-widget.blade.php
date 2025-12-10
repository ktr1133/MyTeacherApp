@if($avatar && $avatar->is_visible && $avatar->generation_status === 'completed')
    @php
        // ちびキャラの場合は全身画像、通常キャラの場合はバストアップ画像を使用
        $isChibi = $avatar->is_chibi ?? false;
        $imageType = $isChibi ? 'full_body' : 'bust';
        
        // 各表情の画像を取得
        $defaultImage = $avatar->images()
            ->where('image_type', $imageType)
            ->where('expression_type', config('const.avatar_expressions.normal'))
            ->first();
        $happyImage = $avatar->images()
            ->where('image_type', $imageType)
            ->where('expression_type', config('const.avatar_expressions.happy'))
            ->first();
        $surprisedImage = $avatar->images()
            ->where('image_type', $imageType)
            ->where('expression_type', config('const.avatar_expressions.surprised'))
            ->first();
        $angryImage = $avatar->images()
            ->where('image_type', $imageType)
            ->where('expression_type', config('const.avatar_expressions.angry'))
            ->first();
        $sadImage = $avatar->images()
            ->where('image_type', $imageType)
            ->where('expression_type', config('const.avatar_expressions.sad'))
            ->first();
            
        $defaultUrl = $defaultImage?->public_url ?? asset('images/avatar-placeholder.png');
        $happyUrl = $happyImage?->public_url ?? $defaultUrl;
        $surprisedUrl = $surprisedImage?->public_url ?? $defaultUrl;
        $angryUrl = $angryImage?->public_url ?? $defaultUrl;
        $sadUrl = $sadImage?->public_url ?? $defaultUrl;
    @endphp
    <div 
        id="avatar-widget"
        class="avatar-widget hidden opacity-0 translate-y-4"
        style="left: 0px; top: 0px;"
        data-default-image="{{ $defaultUrl }}"
        data-happy-image="{{ $happyUrl }}"
        data-surprised-image="{{ $surprisedUrl }}"
        data-angry-image="{{ $angryUrl }}"
        data-sad-image="{{ $sadUrl }}"
    >
        <div class="avatar-container">
            {{-- 吹き出し --}}
            <div class="avatar-bubble">
                <p class="text-sm text-gray-900 dark:text-white"></p>
                <div class="avatar-bubble-arrow"></div>
            </div>

            {{-- アバター画像 --}}
            <img 
                src="{{ $defaultUrl }}" 
                alt="Teacher Avatar"
                class="avatar-image avatar-idle"
            />

            {{-- 閉じるボタン --}}
            <button 
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
    /* ウィジェットの基本スタイル */
    .avatar-widget {
        position: fixed;
        z-index: 9999;
        cursor: move;
        user-select: none;
        -webkit-user-select: none;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    /* 強制非表示クラス（!important で確実に非表示） */
    .avatar-force-hidden {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
        visibility: hidden !important;
    }

    /* コンテナ */
    .avatar-container {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    /* アバター画像 */
    .avatar-image {
        width: 250px;
        height: 300px;
        object-fit: contain;
        filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.1));
        transition: transform 0.3s ease;
    }

    @media (max-width: 640px) {
        .avatar-image {
            width: 180px;
            height: 220px;
        }
    }

    /* 吹き出し */
    .avatar-bubble {
        position: relative;
        max-width: 300px;
        padding: 1rem 1.5rem;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .dark .avatar-bubble {
        background: #1f2937;
        border-color: rgba(255, 255, 255, 0.1);
    }

    /* 吹き出しの矢印 */
    .avatar-bubble-arrow {
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid white;
    }

    .dark .avatar-bubble-arrow {
        border-top-color: #1f2937;
    }

    /* 閉じるボタン */
    .avatar-close-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        width: 32px;
        height: 32px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
    }

    .avatar-close-btn:hover {
        background: #dc2626;
        transform: scale(1.1);
    }
</style>