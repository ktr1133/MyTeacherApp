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
 * 
 * 2つのHTMLパターンに対応:
 * 1. 分離パターン: <div id="fieldId-error"> + <div id="fieldId-success">
 * 2. 単一パターン: <div id="fieldId-validation">
 */
export function showError(fieldId, message) {
    const errorDiv = document.getElementById(`${fieldId}-error`);
    const successDiv = document.getElementById(`${fieldId}-success`);
    const validationDiv = document.getElementById(`${fieldId}-validation`);
    const inputField = document.getElementById(fieldId);
    
    // パターン1: 分離パターン（register.blade.php等）
    if (errorDiv && successDiv) {
        // 成功メッセージを非表示
        successDiv.classList.add('hidden');
        successDiv.classList.remove('validation-success-slide-in');
        
        // エラーメッセージを表示
        const messageSpan = errorDiv.querySelector('span');
        if (messageSpan) {
            messageSpan.textContent = message;
        } else {
            errorDiv.textContent = message;
        }
        
        errorDiv.classList.remove('hidden');
        errorDiv.classList.add('validation-error-slide-in');
        
        // アニメーションをリセット
        errorDiv.style.animation = 'none';
        setTimeout(() => {
            errorDiv.style.animation = '';
        }, 10);
    }
    // パターン2: 単一パターン（グループ編集画面等）
    else if (validationDiv) {
        validationDiv.textContent = message;
        validationDiv.style.display = 'block';
        validationDiv.className = 'validation-message validation-error';
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
 * 
 * 2つのHTMLパターンに対応:
 * 1. 分離パターン: <div id="fieldId-error"> + <div id="fieldId-success">
 * 2. 単一パターン: <div id="fieldId-validation">
 */
export function showSuccess(fieldId, message) {
    const successDiv = document.getElementById(`${fieldId}-success`);
    const errorDiv = document.getElementById(`${fieldId}-error`);
    const validationDiv = document.getElementById(`${fieldId}-validation`);
    const inputField = document.getElementById(fieldId);
    
    // パターン1: 分離パターン（register.blade.php等）
    if (successDiv && errorDiv) {
        // エラーメッセージを非表示
        errorDiv.classList.add('hidden');
        errorDiv.classList.remove('validation-error-slide-in');
        
        // 成功メッセージを表示
        const messageSpan = successDiv.querySelector('span');
        if (messageSpan) {
            messageSpan.textContent = message;
        } else {
            successDiv.textContent = message;
        }
        
        successDiv.classList.remove('hidden');
        successDiv.classList.add('validation-success-slide-in');
        
        // アニメーションをリセット
        successDiv.style.animation = 'none';
        setTimeout(() => {
            successDiv.style.animation = '';
        }, 10);
    }
    // パターン2: 単一パターン（グループ編集画面等）
    else if (validationDiv) {
        validationDiv.textContent = message;
        validationDiv.style.display = 'block';
        validationDiv.className = 'validation-message validation-success';
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
 * 
 * 2つのHTMLパターンに対応:
 * 1. 分離パターン: <div id="fieldId-error"> + <div id="fieldId-success">
 * 2. 単一パターン: <div id="fieldId-validation">
 */
export function hideValidationMessage(fieldId) {
    const errorDiv = document.getElementById(`${fieldId}-error`);
    const successDiv = document.getElementById(`${fieldId}-success`);
    const validationDiv = document.getElementById(`${fieldId}-validation`);
    const inputField = document.getElementById(fieldId);
    
    // パターン1: 分離パターン（register.blade.php等）
    if (errorDiv && successDiv) {
        errorDiv.classList.add('hidden');
        errorDiv.classList.remove('validation-error-slide-in');
        const errorSpan = errorDiv.querySelector('span');
        if (errorSpan) {
            errorSpan.textContent = '';
        }
        
        successDiv.classList.add('hidden');
        successDiv.classList.remove('validation-success-slide-in');
        const successSpan = successDiv.querySelector('span');
        if (successSpan) {
            successSpan.textContent = '';
        }
    }
    // パターン2: 単一パターン（グループ編集画面等）
    else if (validationDiv) {
        validationDiv.style.display = 'none';
        validationDiv.textContent = '';
        validationDiv.className = 'validation-message';
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