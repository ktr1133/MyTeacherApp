/**
 * ユーザー登録フォームの非同期バリデーション
 */

// デバウンス関数（連続入力対策）
function debounce(func, wait) {
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

// バリデーション状態管理
const validationState = {
    username: { valid: false, checked: false },
    password: { valid: false, checked: false },
    passwordConfirmation: { valid: false, checked: false }
};

// ユーザー名バリデーション
async function validateUsername(username) {
    const errorElement = document.getElementById('username-error');
    const successElement = document.getElementById('username-success');
    const inputElement = document.getElementById('username');
    const spinner = document.getElementById('username-spinner');

    if (!username || username.length === 0) {
        hideValidationMessage('username');
        validationState.username = { valid: false, checked: false };
        return;
    }

    // スピナー表示
    if (spinner) spinner.classList.remove('hidden');

    try {
        const response = await fetch('/validate/username', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ username })
        });

        const data = await response.json();
        
        // スピナー非表示
        if (spinner) spinner.classList.add('hidden');

        validationState.username = { valid: data.valid, checked: true };

        if (data.valid) {
            showSuccess('username', data.message);
            inputElement?.classList.remove('input-validation-error');
            inputElement?.classList.add('input-validation-success');
        } else {
            showError('username', data.message);
            inputElement?.classList.remove('input-validation-success');
            inputElement?.classList.add('input-validation-error');
        }
    } catch (error) {
        console.error('Validation error:', error);
        if (spinner) spinner.classList.add('hidden');
        showError('username', 'バリデーションエラーが発生しました');
        validationState.username = { valid: false, checked: false };
    }
}

// パスワードバリデーション
async function validatePassword(password, passwordConfirmation = null) {
    const errorElement = document.getElementById('password-error');
    const successElement = document.getElementById('password-success');
    const inputElement = document.getElementById('password');
    const spinner = document.getElementById('password-spinner');

    if (!password || password.length === 0) {
        hideValidationMessage('password');
        validationState.password = { valid: false, checked: false };
        return;
    }

    // スピナー表示
    if (spinner) spinner.classList.remove('hidden');

    try {
        const response = await fetch('/validate/password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ 
                password,
                password_confirmation: passwordConfirmation 
            })
        });

        const data = await response.json();
        
        // スピナー非表示
        if (spinner) spinner.classList.add('hidden');

        validationState.password = { valid: data.valid, checked: true };

        if (data.valid) {
            showSuccess('password', data.message);
            inputElement?.classList.remove('input-validation-error');
            inputElement?.classList.add('input-validation-success');
        } else {
            showError('password', data.message);
            inputElement?.classList.remove('input-validation-success');
            inputElement?.classList.add('input-validation-error');
        }
    } catch (error) {
        console.error('Validation error:', error);
        if (spinner) spinner.classList.add('hidden');
        showError('password', 'バリデーションエラーが発生しました');
        validationState.password = { valid: false, checked: false };
    }
}

// パスワード確認バリデーション
function validatePasswordConfirmation() {
    const password = document.getElementById('password')?.value || '';
    const passwordConfirmation = document.getElementById('password_confirmation')?.value || '';
    const errorElement = document.getElementById('password-confirmation-error');
    const successElement = document.getElementById('password-confirmation-success');
    const inputElement = document.getElementById('password_confirmation');

    if (!passwordConfirmation || passwordConfirmation.length === 0) {
        hideValidationMessage('password-confirmation');
        validationState.passwordConfirmation = { valid: false, checked: false };
        return;
    }

    if (password !== passwordConfirmation) {
        showError('password-confirmation', 'パスワードが一致しません');
        inputElement?.classList.remove('input-validation-success');
        inputElement?.classList.add('input-validation-error');
        validationState.passwordConfirmation = { valid: false, checked: true };
    } else {
        showSuccess('password-confirmation', 'パスワードが一致しています');
        inputElement?.classList.remove('input-validation-error');
        inputElement?.classList.add('input-validation-success');
        validationState.passwordConfirmation = { valid: true, checked: true };
    }
}

// エラーメッセージ表示
function showError(field, message) {
    const errorElement = document.getElementById(`${field}-error`);
    const successElement = document.getElementById(`${field}-success`);

    if (successElement) {
        successElement.classList.add('hidden');
    }

    if (errorElement) {
        const messageSpan = errorElement.querySelector('span');
        if (messageSpan) {
            messageSpan.textContent = message;
        }
        errorElement.classList.remove('hidden', 'validation-fade-out');
        errorElement.classList.add('validation-error-slide-in');
    }
}

// 成功メッセージ表示
function showSuccess(field, message) {
    const errorElement = document.getElementById(`${field}-error`);
    const successElement = document.getElementById(`${field}-success`);

    if (errorElement) {
        errorElement.classList.add('hidden');
    }

    if (successElement) {
        const messageSpan = successElement.querySelector('span');
        if (messageSpan) {
            messageSpan.textContent = message;
        }
        successElement.classList.remove('hidden', 'validation-fade-out');
        successElement.classList.add('validation-success-slide-in');
    }
}

// バリデーションメッセージ非表示
function hideValidationMessage(field) {
    const errorElement = document.getElementById(`${field}-error`);
    const successElement = document.getElementById(`${field}-success`);
    const inputElement = document.getElementById(field.replace(/-/g, '_'));

    if (errorElement) {
        errorElement.classList.add('validation-fade-out');
        setTimeout(() => {
            errorElement.classList.add('hidden');
            errorElement.classList.remove('validation-fade-out');
        }, 200);
    }
    
    if (successElement) {
        successElement.classList.add('validation-fade-out');
        setTimeout(() => {
            successElement.classList.add('hidden');
            successElement.classList.remove('validation-fade-out');
        }, 200);
    }
    
    if (inputElement) {
        inputElement.classList.remove('input-validation-error', 'input-validation-success');
    }
}

// フォーム送信前のバリデーション
function validateFormBeforeSubmit(event) {
    const submitButton = event.target.querySelector('button[type="submit"]');
    
    // すべてのバリデーションが完了しているか確認
    const allValid = validationState.username.valid && 
                     validationState.password.valid && 
                     validationState.passwordConfirmation.valid;

    const allChecked = validationState.username.checked && 
                       validationState.password.checked && 
                       validationState.passwordConfirmation.checked;

    if (!allChecked) {
        event.preventDefault();
        alert('すべての項目を入力してください');
        return false;
    }

    if (!allValid) {
        event.preventDefault();
        alert('入力内容に誤りがあります。修正してください。');
        return false;
    }

    // 送信ボタンを無効化（二重送信防止）
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="animate-spin h-5 w-5 mr-2 inline-block" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            登録中...
        `;
    }

    return true;
}

// DOMContentLoaded時の初期化
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const registerForm = document.querySelector('form[action*="register"]');

    // 初期状態ですべてのバリデーションメッセージを非表示
    hideValidationMessage('username');
    hideValidationMessage('password');
    hideValidationMessage('password-confirmation');

    // デバウンス適用
    const debouncedValidateUsername = debounce(function(e) {
        validateUsername(e.target.value);
    }, 500);

    const debouncedValidatePassword = debounce(function() {
        const password = passwordInput?.value || '';
        const passwordConfirmation = passwordConfirmationInput?.value || '';
        validatePassword(password, passwordConfirmation);
    }, 500);

    const debouncedValidatePasswordConfirmation = debounce(function() {
        validatePasswordConfirmation();
    }, 300);

    // イベントリスナー設定
    if (usernameInput) {
        // 入力開始時にメッセージを非表示
        usernameInput.addEventListener('input', function(e) {
            if (validationState.username.checked) {
                hideValidationMessage('username');
                validationState.username.checked = false;
            }
            debouncedValidateUsername(e);
        });

        usernameInput.addEventListener('blur', function(e) {
            if (e.target.value.length > 0) {
                validateUsername(e.target.value);
            }
        });
    }

    if (passwordInput) {
        // 入力開始時にメッセージを非表示
        passwordInput.addEventListener('input', function() {
            if (validationState.password.checked) {
                hideValidationMessage('password');
                validationState.password.checked = false;
            }
            debouncedValidatePassword();
        });

        passwordInput.addEventListener('blur', function() {
            const password = passwordInput?.value || '';
            if (password.length > 0) {
                const passwordConfirmation = passwordConfirmationInput?.value || '';
                validatePassword(password, passwordConfirmation);
            }
        });
    }

    if (passwordConfirmationInput) {
        // 入力開始時にメッセージを非表示
        passwordConfirmationInput.addEventListener('input', function() {
            if (validationState.passwordConfirmation.checked) {
                hideValidationMessage('password-confirmation');
                validationState.passwordConfirmation.checked = false;
            }
            debouncedValidatePasswordConfirmation();
        });

        passwordConfirmationInput.addEventListener('blur', function() {
            const passwordConfirmation = passwordConfirmationInput?.value || '';
            if (passwordConfirmation.length > 0) {
                validatePasswordConfirmation();
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', validateFormBeforeSubmit);
    }
});