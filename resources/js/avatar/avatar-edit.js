/**
 * アバター編集画面の制御（Vanilla JS版）
 * Alpine.jsを使用せず、純粋なJavaScriptで実装
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[Avatar Edit] Initializing...');
    
    // モバイルメニュートグルボタン
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            // sidebar.jsのグローバル関数を呼び出し
            if (window.sidebarController) {
                window.sidebarController.openMobile();
            }
        });
    }
    
    // 表情スライダーの初期化
    initExpressionSlider();
    
    // アバターフォームの初期化
    initAvatarForm();
    
    console.log('[Avatar Edit] Initialization complete');
});

/**
 * 表情スライダーの初期化
 */
function initExpressionSlider() {
    const sliderContainer = document.getElementById('expression-slider');
    if (!sliderContainer) {
        console.log('[Expression Slider] Container not found, skipping initialization');
        return;
    }
    
    const expressionsData = sliderContainer.dataset.expressions;
    const isChibi = sliderContainer.dataset.isChibi === 'true';
    
    if (!expressionsData) {
        console.error('[Expression Slider] No expressions data found');
        return;
    }
    
    let expressions;
    try {
        expressions = JSON.parse(expressionsData);
    } catch (e) {
        console.error('[Expression Slider] Failed to parse expressions data:', e);
        return;
    }
    
    console.log('[Expression Slider] Loaded expressions:', expressions.length, 'isChibi:', isChibi);
    
    const state = {
        currentIndex: 0,
        touchStartX: 0,
        touchEndX: 0,
        minSwipeDistance: 50
    };
    
    const sliderBg = document.getElementById('slider-bg');
    const sliderImages = document.getElementById('slider-images');
    const thumbnailList = document.getElementById('thumbnail-list');
    
    // 子どもテーマかどうかを判定
    const isChildTheme = document.documentElement.classList.contains('child-theme');
    
    /**
     * 背景画像を更新
     */
    function updateBackground() {
        const currentExpr = expressions[state.currentIndex];
        if (currentExpr && currentExpr.image && sliderBg) {
            const imageUrl = currentExpr.image.s3_url || currentExpr.image.public_url;
            sliderBg.style.backgroundImage = `url('${imageUrl}')`;
        }
    }
    
    /**
     * メイン画像を表示
     */
    function renderMainImage() {
        if (!sliderImages) return;
        
        sliderImages.innerHTML = '';
        const currentExpr = expressions[state.currentIndex];
        
        const container = document.createElement('div');
        container.className = 'relative';
        
        // 表情ラベル
        const label = document.createElement('div');
        label.className = `absolute top-3 left-3 z-20 ${isChildTheme ? 'avatar-expression-label-child' : 'avatar-expression-label'}`;
        label.innerHTML = `<span class="font-bold">${currentExpr.label}</span>`;
        container.appendChild(label);
        
        // 画像または プレースホルダー
        if (currentExpr.image) {
            const img = document.createElement('img');
            img.src = currentExpr.image.s3_url || currentExpr.image.public_url;
            img.alt = currentExpr.label;
            img.className = `w-full h-auto rounded-lg relative z-10 ${isChildTheme ? 'avatar-image' : ''}`;
            container.appendChild(img);
        } else {
            const placeholder = document.createElement('div');
            placeholder.className = 'aspect-square flex flex-col items-center justify-center text-center p-6 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 relative z-10';
            placeholder.innerHTML = `
                <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">生成中...</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">${currentExpr.label}画像</p>
            `;
            container.appendChild(placeholder);
        }
        
        sliderImages.appendChild(container);
    }
    
    /**
     * サムネイルを生成
     */
    function renderThumbnails() {
        if (!thumbnailList) {
            console.error('[Expression Slider] Thumbnail list element not found');
            return;
        }
        
        console.log('[Expression Slider] Rendering thumbnails for', expressions.length, 'expressions');
        
        thumbnailList.innerHTML = '';
        
        expressions.forEach((expr, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            // position: relative が必要
            button.className = `avatar-thumbnail ${index === state.currentIndex ? 'avatar-thumbnail-active' : 'avatar-thumbnail-inactive'}`;
            button.setAttribute('aria-label', expr.label);
            button.dataset.index = index;
            
            // サムネイル画像または プレースホルダー
            if (expr.image) {
                const img = document.createElement('img');
                img.src = expr.image.s3_url || expr.image.public_url;
                img.alt = expr.label;
                img.className = 'w-full h-full object-cover rounded-lg';
                button.appendChild(img);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg';
                placeholder.innerHTML = `
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                `;
                button.appendChild(placeholder);
            }
            
            // ラベル（position: absolute で配置される）
            const labelDiv = document.createElement('div');
            labelDiv.className = `absolute bottom-0 left-0 right-0 ${isChildTheme ? 'avatar-thumbnail-label-child' : 'avatar-thumbnail-label'}`;
            labelDiv.innerHTML = `<span class="text-xs font-semibold truncate block px-1">${expr.label}</span>`;
            button.appendChild(labelDiv);
            
            // クリックイベント
            button.addEventListener('click', () => {
                console.log('[Expression Slider] Thumbnail clicked:', index, expr.label);
                goToExpression(index);
            });
            
            thumbnailList.appendChild(button);
        });
        
        console.log('[Expression Slider] Thumbnails rendered:', thumbnailList.children.length);
    }
    
    /**
     * 指定インデックスの表情へ移動
     */
    function goToExpression(index) {
        if (index >= 0 && index < expressions.length) {
            state.currentIndex = index;
            updateView();
        }
    }
    
    /**
     * ビューを更新
     */
    function updateView() {
        updateBackground();
        renderMainImage();
        updateThumbnailActive();
    }
    
    /**
     * アクティブなサムネイルを更新
     */
    function updateThumbnailActive() {
        if (!thumbnailList) return;
        
        const thumbnails = thumbnailList.querySelectorAll('button');
        thumbnails.forEach((btn, index) => {
            if (index === state.currentIndex) {
                btn.classList.add('avatar-thumbnail-active');
                btn.classList.remove('avatar-thumbnail-inactive');
            } else {
                btn.classList.remove('avatar-thumbnail-active');
                btn.classList.add('avatar-thumbnail-inactive');
            }
        });
    }
    
    /**
     * タッチイベントハンドラー
     */
    if (sliderImages) {
        sliderImages.addEventListener('touchstart', (e) => {
            state.touchStartX = e.touches[0].clientX;
        });
        
        sliderImages.addEventListener('touchmove', (e) => {
            state.touchEndX = e.touches[0].clientX;
        });
        
        sliderImages.addEventListener('touchend', () => {
            const swipeDistance = state.touchStartX - state.touchEndX;
            
            // 右スワイプ（前へ）
            if (swipeDistance < -state.minSwipeDistance) {
                if (state.currentIndex > 0) {
                    state.currentIndex--;
                    updateView();
                }
            }
            // 左スワイプ（次へ）
            else if (swipeDistance > state.minSwipeDistance) {
                if (state.currentIndex < expressions.length - 1) {
                    state.currentIndex++;
                    updateView();
                }
            }
            
            // リセット
            state.touchStartX = 0;
            state.touchEndX = 0;
        });
        
        // PCでのクリックイベント（画像の左半分/右半分）
        sliderImages.addEventListener('click', (e) => {
            const rect = sliderImages.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const halfWidth = rect.width / 2;
            
            // 左半分クリック → 前へ
            if (clickX < halfWidth) {
                if (state.currentIndex > 0) {
                    state.currentIndex--;
                    updateView();
                    console.log('[Expression Slider] PC click: previous', state.currentIndex);
                }
            }
            // 右半分クリック → 次へ
            else {
                if (state.currentIndex < expressions.length - 1) {
                    state.currentIndex++;
                    updateView();
                    console.log('[Expression Slider] PC click: next', state.currentIndex);
                }
            }
        });
        
        // カーソルスタイルを変更（PC用）
        sliderImages.style.cursor = 'pointer';
    }
    
    // 初期表示
    renderThumbnails(); // サムネイル生成
    updateView();
    
    console.log('[Expression Slider] Initialized successfully');
}

/**
 * アバターフォームの初期化
 */
function initAvatarForm() {
    const form = document.getElementById('avatar-form');
    if (!form) {
        console.log('[Avatar Form] Form not found, skipping initialization');
        return;
    }
    
    let isSubmitting = false;
    
    form.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return;
        }
        
        isSubmitting = true;
        
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = '更新中...';
        }
    });
    
    console.log('[Avatar Form] Initialized successfully');
}