/**
 * サブスクリプション画面のJavaScript
 * Enterprise追加メンバーモーダル、価格計算機能
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===================================
    // Enterprise追加メンバーモーダル
    // ===================================
    const enterpriseBtn = document.getElementById('enterprise-plan-btn');
    const modal = document.getElementById('enterprise-modal');
    const modalOverlay = document.getElementById('modal-overlay');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelModalBtn = document.getElementById('cancel-modal');
    const additionalMembersInput = document.getElementById('additional_members');
    const totalPriceDisplay = document.getElementById('total-price');
    const confirmEnterpriseBtn = document.getElementById('confirm-enterprise');

    // 基本料金（Enterprise）
    const BASE_PRICE = 3000;
    // 追加メンバー単価
    const ADDITIONAL_MEMBER_PRICE = 150;

    /**
     * モーダルを開く
     */
    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        // 初期値で価格を計算
        calculateTotalPrice();
    }

    /**
     * モーダルを閉じる
     */
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        
        // 入力値をリセット
        additionalMembersInput.value = '0';
        calculateTotalPrice();
    }

    /**
     * 合計価格を計算して表示
     */
    function calculateTotalPrice() {
        const additionalMembers = parseInt(additionalMembersInput.value) || 0;
        
        // 0-100の範囲に制限
        const validMembers = Math.max(0, Math.min(100, additionalMembers));
        
        // 入力値が範囲外の場合は補正
        if (additionalMembers !== validMembers) {
            additionalMembersInput.value = validMembers;
        }
        
        // 合計金額 = 基本料金 + (追加メンバー数 × 単価)
        const totalPrice = BASE_PRICE + (validMembers * ADDITIONAL_MEMBER_PRICE);
        
        // カンマ区切りで表示
        totalPriceDisplay.textContent = totalPrice.toLocaleString('ja-JP');
        
        // 確定ボタンのラベル更新
        if (validMembers > 0) {
            confirmEnterpriseBtn.textContent = `¥${totalPrice.toLocaleString('ja-JP')}/月で申し込む`;
        } else {
            confirmEnterpriseBtn.textContent = '¥3,000/月で申し込む';
        }
    }

    /**
     * Enterpriseプラン申し込み確定
     */
    function confirmEnterprisePlan() {
        // フォームをサブミット
        const form = document.getElementById('enterprise-form');
        
        // 追加メンバー数をフォームに設定
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'additional_members';
        hiddenInput.value = additionalMembersInput.value || '0';
        form.appendChild(hiddenInput);
        
        // プラン種別を設定
        const planInput = document.createElement('input');
        planInput.type = 'hidden';
        planInput.name = 'plan';
        planInput.value = 'enterprise';
        form.appendChild(planInput);
        
        // サブミット
        form.submit();
    }

    // イベントリスナー登録
    if (enterpriseBtn) {
        enterpriseBtn.addEventListener('click', openModal);
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    if (cancelModalBtn) {
        cancelModalBtn.addEventListener('click', closeModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            // オーバーレイの直接クリック時のみ閉じる
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    // 追加メンバー数変更時に価格を再計算
    if (additionalMembersInput) {
        additionalMembersInput.addEventListener('input', calculateTotalPrice);
        
        // 数値以外の入力を防ぐ
        additionalMembersInput.addEventListener('keypress', function(e) {
            if (e.key < '0' || e.key > '9') {
                e.preventDefault();
            }
        });
    }

    // 確定ボタン
    if (confirmEnterpriseBtn) {
        confirmEnterpriseBtn.addEventListener('click', confirmEnterprisePlan);
    }

    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // ===================================
    // Family/Freeプラン申し込み
    // ===================================
    const familyPlanBtn = document.getElementById('family-plan-btn');

    if (familyPlanBtn) {
        familyPlanBtn.addEventListener('click', function() {
            // Familyプランフォームをサブミット
            const form = document.getElementById('family-form');
            
            const planInput = document.createElement('input');
            planInput.type = 'hidden';
            planInput.name = 'plan';
            planInput.value = 'family';
            form.appendChild(planInput);
            
            form.submit();
        });
    }

    // ===================================
    // アニメーション: プランカードホバー
    // ===================================
    const planCards = document.querySelectorAll('.plan-card');
    
    planCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // 他のカードを少し縮小
            planCards.forEach(otherCard => {
                if (otherCard !== card) {
                    otherCard.style.transform = 'scale(0.97)';
                    otherCard.style.opacity = '0.8';
                }
            });
        });
        
        card.addEventListener('mouseleave', function() {
            // 元に戻す
            planCards.forEach(otherCard => {
                otherCard.style.transform = '';
                otherCard.style.opacity = '';
            });
        });
    });

    // ===================================
    // スクロールアニメーション: プランカード表示時
    // ===================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // 順番に表示
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // 初期状態を設定
    planCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // ===================================
    // バリデーション: 追加メンバー数
    // ===================================
    if (additionalMembersInput) {
        additionalMembersInput.addEventListener('blur', function() {
            const value = parseInt(this.value) || 0;
            
            if (value < 0) {
                this.value = '0';
                showValidationMessage('追加メンバー数は0以上を指定してください。');
            } else if (value > 100) {
                this.value = '100';
                showValidationMessage('追加メンバー数は100名までです。');
            }
            
            calculateTotalPrice();
        });
    }

    /**
     * バリデーションメッセージを表示
     */
    function showValidationMessage(message) {
        // 既存のメッセージを削除
        const existingMessage = document.getElementById('validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // メッセージ要素を作成
        const messageDiv = document.createElement('div');
        messageDiv.id = 'validation-message';
        messageDiv.className = 'mt-2 text-sm text-red-600 dark:text-red-400';
        messageDiv.textContent = message;
        
        // 入力欄の後に挿入
        additionalMembersInput.parentNode.appendChild(messageDiv);
        
        // 3秒後に削除
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }
});
