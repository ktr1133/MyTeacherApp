/**
 * 共通バリデーション関数
 * アカウント作成とグループ管理で共通利用
 */

/**
 * デバウンス処理
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * スピナー表示
 */
export function showSpinner(fieldId) {
    const spinner = document.getElementById(`${fieldId}-spinner`);
    if (spinner) {
        spinner.style.display = 'flex';
    }
}

/**
 * スピナー非表示
 */
export function hideSpinner(fieldId) {
    const spinner = document.getElementById(`${fieldId}-spinner`);
    if (spinner) {
        spinner.style.display = 'none';
    }
}

/**
 * エラーメッセージ表示
 */
export function showError(fieldId, message) {
    const validationDiv = document.getElementById(`${fieldId}-validation`);
    const inputField = document.getElementById(fieldId);
    
    if (validationDiv) {
        validationDiv.textContent = message;
        validationDiv.className = 'validation-message validation-error';
        validationDiv.style.display = 'block';
        
        // アニメーション
        validationDiv.style.animation = 'none';
        setTimeout(() => {
            validationDiv.style.animation = 'slideDown 0.3s ease-out';
        }, 10);
    }
    
    if (inputField) {
        inputField.classList.remove('input-validation-success');
        inputField.classList.add('input-validation-error');
    }
    
    // バリデーション状態を更新
    if (window.validationState) {
        window.validationState[fieldId] = false;
    }
}

/**
 * 成功メッセージ表示
 */
export function showSuccess(fieldId, message) {
    const validationDiv = document.getElementById(`${fieldId}-validation`);
    const inputField = document.getElementById(fieldId);
    
    if (validationDiv) {
        validationDiv.textContent = message;
        validationDiv.className = 'validation-message validation-success';
        validationDiv.style.display = 'block';
        
        // アニメーション
        validationDiv.style.animation = 'none';
        setTimeout(() => {
            validationDiv.style.animation = 'slideDown 0.3s ease-out';
        }, 10);
    }
    
    if (inputField) {
        inputField.classList.remove('input-validation-error');
        inputField.classList.add('input-validation-success');
    }
    
    // バリデーション状態を更新
    if (window.validationState) {
        window.validationState[fieldId] = true;
    }
}

/**
 * バリデーションメッセージ非表示
 */
export function hideValidationMessage(fieldId) {
    const validationDiv = document.getElementById(`${fieldId}-validation`);
    const inputField = document.getElementById(fieldId);
    
    if (validationDiv) {
        validationDiv.style.display = 'none';
        validationDiv.textContent = '';
    }
    
    if (inputField) {
        inputField.classList.remove('input-validation-error', 'input-validation-success');
    }
    
    // バリデーション状態をリセット
    if (window.validationState) {
        window.validationState[fieldId] = null;
    }
}

/**
 * 非同期バリデーションリクエスト
 */
export async function validateField(endpoint, data, fieldId) {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        });
        
        if (!response.ok) {
            if (import.meta.env.DEV) {
                console.error('[Validation] Server error:', response.status);
            }
            showError(fieldId, 'サーバーエラーが発生しました。しばらく待ってから再度お試しください。');
            return null;
        }
        
        const result = await response.json();
        
        if (import.meta.env.DEV) {
            console.log('[Validation] Response:', result);
        }
        
        return result;
        
    } catch (error) {
        if (import.meta.env.DEV) {
            console.error('[Validation] Network error:', error);
        }
        showError(fieldId, 'ネットワークエラーが発生しました。再度お試しください。');
        return null;
    }
}

/**
 * フォーム全体のバリデーション状態を確認
 */
export function isFormValid(fieldIds) {
    if (!window.validationState) {
        if (import.meta.env.DEV) {
            console.warn('[Validation] validationState is not initialized');
        }
        return false;
    }
    
    for (const fieldId of fieldIds) {
        if (window.validationState[fieldId] !== true) {
            if (import.meta.env.DEV) {
                console.log('[Validation] Field is invalid:', fieldId, window.validationState[fieldId]);
            }
            return false;
        }
    }
    
    return true;
}

/**
 * バリデーション状態を初期化
 */
export function initValidationState(fieldIds) {
    window.validationState = {};
    for (const fieldId of fieldIds) {
        window.validationState[fieldId] = null;
    }
    
    if (import.meta.env.DEV) {
        console.log('[Validation] State initialized:', window.validationState);
    }
}

/**
 * 送信ボタンの活性/非活性を制御
 */
export function updateSubmitButtonState(buttonId, fieldIds) {
    const button = document.getElementById(buttonId);
    
    if (!button) {
        if (import.meta.env.DEV) {
            console.warn('[Validation] Submit button not found:', buttonId);
        }
        return;
    }
    
    const allValid = isFormValid(fieldIds);
    
    if (allValid) {
        button.disabled = false;
        button.classList.remove('opacity-50', 'cursor-not-allowed');
        button.classList.add('hover:shadow-lg', 'transform', 'hover:scale-105');
        
        if (import.meta.env.DEV) {
            console.log('[Validation] Submit button enabled:', buttonId);
        }
    } else {
        button.disabled = true;
        button.classList.add('opacity-50', 'cursor-not-allowed');
        button.classList.remove('hover:shadow-lg', 'transform', 'hover:scale-105');
        
        if (import.meta.env.DEV) {
            console.log('[Validation] Submit button disabled:', buttonId, window.validationState);
        }
    }
}