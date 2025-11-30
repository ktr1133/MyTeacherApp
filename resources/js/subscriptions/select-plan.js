/**
 * サブスクリプション画面のJavaScript
 * Enterprise追加メンバーモーダル、価格計算機能
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===================================
    // モーダル共通処理
    // ===================================
    
    /**
     * モーダルを開く
     */
    function openModal(modalElement) {
        modalElement.classList.remove('hidden');
        modalElement.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * モーダルを閉じる
     */
    function closeModalByElement(modalElement) {
        modalElement.classList.add('hidden');
        modalElement.classList.remove('flex');
        document.body.style.overflow = '';
    }
    
    // data-modal-close属性を持つボタンでモーダルを閉じる
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModalByElement(modal);
            }
        });
    });
    
    // モーダルオーバーレイクリックで閉じる
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModalByElement(modal);
            }
        });
    });
    
    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Enterpriseモーダル
            document.querySelectorAll('.modal:not(.hidden)').forEach(modal => {
                closeModalByElement(modal);
            });
        }
    });

    // ===================================
    // 新規プラン加入確認（汎用ダイアログ使用）
    // ===================================
    document.querySelectorAll('[data-plan-subscribe]').forEach(btn => {
        btn.addEventListener('click', function() {
            const plan = this.getAttribute('data-plan-subscribe');
            const planName = this.getAttribute('data-plan-name');
            const planPrice = this.getAttribute('data-plan-price');
            
            if (typeof window.showConfirmDialog === 'function') {
                const message = `「${planName}」に加入しますか？\n\n月額料金: ¥${planPrice}\n\n特典: 14日間の無料トライアル期間があります。\nトライアル期間中はいつでもキャンセル可能です。`;
                
                window.showConfirmDialog(
                    message,
                    () => {
                        // 確認時: フォームを作成して送信
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/subscriptions/checkout';
                        
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (csrfToken) {
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                        }
                        
                        const planInput = document.createElement('input');
                        planInput.type = 'hidden';
                        planInput.name = 'plan';
                        planInput.value = plan;
                        form.appendChild(planInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                );
            }
        });
    });

    // ===================================
    // サブスクリプションキャンセル確認（汎用ダイアログ使用）
    // ===================================
    document.querySelectorAll('[data-cancel-subscription]').forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof window.showConfirmDialog === 'function') {
                const message = 'サブスクリプションをキャンセルしてもよろしいですか？\n\nご注意: キャンセル後も、現在の有効期限まで引き続きご利用いただけます。';
                
                window.showConfirmDialog(
                    message,
                    () => {
                        // 確認時: フォームを作成して送信
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/subscriptions/cancel-subscription';
                        
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (csrfToken) {
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                        }
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                );
            }
        });
    });

    // ===================================
    // Enterprise追加メンバーモーダル
    // ===================================
    const enterpriseModal = document.getElementById('enterprise-modal');
    const additionalMembersInput = document.getElementById('additional_members');
    const additionalPriceDisplay = document.getElementById('additional-price');
    const totalPriceDisplay = document.getElementById('total-price');

    // 基本料金（Enterprise）
    const BASE_PRICE = 3000;
    // 追加メンバー単価
    const ADDITIONAL_MEMBER_PRICE = 150;

    /**
     * 合計価格を計算して表示
     */
    function calculateTotalPrice() {
        if (!additionalMembersInput || !totalPriceDisplay || !additionalPriceDisplay) return;
        
        const additionalMembers = parseInt(additionalMembersInput.value) || 0;
        
        // 0-100の範囲に制限
        const validMembers = Math.max(0, Math.min(100, additionalMembers));
        
        // 入力値が範囲外の場合は補正
        if (additionalMembers !== validMembers) {
            additionalMembersInput.value = validMembers;
        }
        
        // 追加料金と合計金額を計算
        const additionalPrice = validMembers * ADDITIONAL_MEMBER_PRICE;
        const totalPrice = BASE_PRICE + additionalPrice;
        
        // 表示を更新
        additionalPriceDisplay.textContent = `¥${additionalPrice.toLocaleString('ja-JP')}`;
        totalPriceDisplay.textContent = `¥${totalPrice.toLocaleString('ja-JP')}`;
    }
    
    // data-plan="enterprise"属性を持つボタンでEnterpriseモーダルを開く
    document.querySelectorAll('[data-plan="enterprise"]').forEach(btn => {
        btn.addEventListener('click', function() {
            if (enterpriseModal) {
                if (additionalMembersInput) {
                    additionalMembersInput.value = '0';
                }
                calculateTotalPrice();
                openModal(enterpriseModal);
            }
        });
    });

    // ===================================
    // エンタープライズプラン新規加入確認
    // ===================================
    document.querySelectorAll('[data-enterprise-subscribe]').forEach(btn => {
        btn.addEventListener('click', function() {
            const planName = this.getAttribute('data-plan-name');
            
            if (typeof window.showConfirmDialog === 'function') {
                const message = `「${planName}」に加入しますか？\n\n基本料金: ¥3,000/月（10名まで）\n追加メンバー: ¥150/月/名\n\n特典: 14日間の無料トライアル期間があります。\nトライアル期間中はいつでもキャンセル可能です。\n\n次の画面で追加メンバー数を設定できます。`;
                
                window.showConfirmDialog(
                    message,
                    () => {
                        // 確認後: Enterpriseモーダル（メンバー数設定）を開く
                        if (enterpriseModal) {
                            if (additionalMembersInput) {
                                additionalMembersInput.value = '0';
                            }
                            calculateTotalPrice();
                            openModal(enterpriseModal);
                        }
                    }
                );
            } else {
                // フォールバック: 直接Enterpriseモーダルを開く
                if (enterpriseModal) {
                    if (additionalMembersInput) {
                        additionalMembersInput.value = '0';
                    }
                    calculateTotalPrice();
                    openModal(enterpriseModal);
                }
            }
        });
    });

    // 追加メンバー数変更時に価格を再計算
    if (additionalMembersInput) {
        additionalMembersInput.addEventListener('input', calculateTotalPrice);
        
        // 数値以外の入力を防ぐ
        additionalMembersInput.addEventListener('keypress', function(e) {
            if (e.key < '0' || e.key > '9') {
                e.preventDefault();
            }
        });
        
        // バリデーション
        additionalMembersInput.addEventListener('blur', function() {
            const value = parseInt(this.value) || 0;
            
            if (value < 0) {
                this.value = '0';
            } else if (value > 100) {
                this.value = '100';
            }
            
            calculateTotalPrice();
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
});
