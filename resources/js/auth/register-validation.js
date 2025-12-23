/**
 * アカウント作成バリデーション
 * リファクタリング版（共通関数を使用）
 */

import {
    debounce,
    showSpinner,
    hideSpinner,
    showError,
    showSuccess,
    hideValidationMessage,
    validateField,
    isFormValid,
    initValidationState,
    updateSubmitButtonState,
} from '../common/validation-core.js';

import { PasswordStrengthChecker } from '../components/password-strength.js';

// バリデーション状態を初期化（タイムゾーンはデフォルト値があるため除外、同意チェックボックスは動的に検証）
initValidationState(['username', 'email', 'password', 'password_confirmation']);

// パスワード強度チェッカーをグローバルに保持
let strengthChecker = null;

/**
 * ユーザー名バリデーション
 */
async function validateUsername() {
    const usernameInput = document.getElementById('username');
    const username = usernameInput.value.trim();

    // 空の場合は非表示
    if (!username || username.length === 0) {
        hideValidationMessage('username');
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[RegisterValidation] Validating username:', username);
    }

    showSpinner('username');

    const result = await validateField('/validate/username', { username }, 'username');

    hideSpinner('username');

    if (result) {
        if (result.valid) {
            showSuccess('username', result.message || '✓ 利用可能なユーザー名です');
        } else {
            showError('username', result.message || '× このユーザー名は既に使用されています');
        }
    }

    // ボタン状態を更新（同意チェックボックスも検証）
    updateSubmitButtonStateWithConsent();
}

/**
 * メールアドレスバリデーション
 */
async function validateEmail() {
    const emailInput = document.getElementById('email');
    
    // メールアドレスフィールドが存在しない場合はスキップ
    if (!emailInput) {
        return;
    }
    
    const email = emailInput.value.trim();

    if (!email || email.length === 0) {
        hideValidationMessage('email');
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[RegisterValidation] Validating email:', email);
    }

    showSpinner('email');

    const result = await validateField('/validate/email', { email }, 'email');

    hideSpinner('email');

    if (result) {
        if (result.valid) {
            showSuccess('email', result.message || '✓ 利用可能なメールアドレスです');
        } else {
            showError('email', result.message || '× このメールアドレスは既に使用されています');
        }
    }

    // ボタン状態を更新（同意チェックボックスも検証）
    updateSubmitButtonStateWithConsent();
}

/**
 * 同意チェックボックスの検証を含むボタン状態更新
 */
function updateSubmitButtonStateWithConsent() {
    const privacyCheckbox = document.getElementById('privacy_policy_consent');
    const termsCheckbox = document.getElementById('terms_consent');
    
    // 必須フィールドのバリデーション状態
    const fieldsValid = isFormValid(['username', 'email', 'password', 'password_confirmation']);
    
    // 同意チェックボックスの状態
    const consentsChecked = privacyCheckbox && privacyCheckbox.checked && termsCheckbox && termsCheckbox.checked;
    
    // 両方がtrueの場合のみボタンを有効化
    const registerButton = document.getElementById('register-button');
    if (registerButton) {
        if (fieldsValid && consentsChecked) {
            registerButton.disabled = false;
            registerButton.classList.remove('opacity-50', 'cursor-not-allowed');
            registerButton.classList.add('hover:shadow-2xl', 'transform', 'hover:scale-105');
        } else {
            registerButton.disabled = true;
            registerButton.classList.add('opacity-50', 'cursor-not-allowed');
            registerButton.classList.remove('hover:shadow-2xl', 'transform', 'hover:scale-105');
        }
    }
}

/**
 * パスワードバリデーション
 */
async function validatePassword() {
    const passwordInput = document.getElementById('password');
    const password = passwordInput.value;

    if (!password || password.length === 0) {
        hideValidationMessage('password');
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[RegisterValidation] Validating password');
    }

    showSpinner('password');

    // クライアント側の厳格なバリデーション
    if (strengthChecker) {
        const validation = strengthChecker.getValidationResult();
        
        if (!validation.isValid) {
            hideSpinner('password');
            showError('password', '× ' + validation.errors.join(', '));
            updateSubmitButtonState('register-button', ['username', 'password', 'password_confirmation']);
            return;
        }
    }

    const result = await validateField('/validate/password', { password }, 'password');

    hideSpinner('password');

    if (result) {
        if (result.valid) {
            showSuccess('password', result.message || '✓ 十分に強力なパスワードです');
        } else {
            showError('password', result.message || '× パスワードが弱すぎます');
        }
    }

    // ボタン状態を更新
    updateSubmitButtonState('register-button', ['username', 'password', 'password_confirmation']);
}

/**
 * パスワード確認バリデーション
 */
function validatePasswordConfirmation() {
    const passwordInput = document.getElementById('password');
    const confirmationInput = document.getElementById('password_confirmation');
    const password = passwordInput.value;
    const confirmation = confirmationInput.value;

    if (!confirmation || confirmation.length === 0) {
        hideValidationMessage('password_confirmation');
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[RegisterValidation] Validating password confirmation');
    }

    if (password === confirmation) {
        showSuccess('password_confirmation', '✓ パスワードが一致しています');
    } else {
        showError('password_confirmation', '× パスワードが一致していません');
    }

    // ボタン状態を更新
    updateSubmitButtonState('register-button', ['username', 'password', 'password_confirmation']);
}

/**
 * タイムゾーンバリデーション
 */
function validateTimezone() {
    const timezoneSelect = document.getElementById('timezone');
    const timezone = timezoneSelect.value;

    // タイムゾーンが選択されていない場合
    if (!timezone || timezone.length === 0) {
        showError('timezone', '× タイムゾーンを選択してください');
        return false;
    }

    // タイムゾーンが選択されている場合はエラーメッセージを非表示
    hideValidationMessage('timezone');

    if (import.meta.env.DEV) {
        console.log('[RegisterValidation] Timezone selected:', timezone);
    }

    return true;
}

// デバウンス付きバリデーション関数
const debouncedValidateUsername = debounce(validateUsername, 500);
const debouncedValidateEmail = debounce(validateEmail, 500);
const debouncedValidatePassword = debounce(validatePassword, 500);
const debouncedValidatePasswordConfirmation = debounce(validatePasswordConfirmation, 500);

// イベントリスナー設定
document.addEventListener('DOMContentLoaded', function() {
    if (import.meta.env.DEV) {
        console.log('[RegisterValidation] Initializing');
    }

    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email'); // 将来用（現在は使用しない）
    const passwordInput = document.getElementById('password');
    const confirmationInput = document.getElementById('password_confirmation');
    const timezoneSelect = document.getElementById('timezone');
    const privacyCheckbox = document.getElementById('privacy_policy_consent');
    const termsCheckbox = document.getElementById('terms_consent');
    const registerForm = document.getElementById('register-form');

    // パスワード強度チェッカーを初期化
    if (passwordInput) {
        strengthChecker = new PasswordStrengthChecker('#password', '#password-strength-meter');
    }

    if (usernameInput) {
        usernameInput.addEventListener('input', debouncedValidateUsername);
    }

    // メールアドレスフィールドが存在する場合のみイベントリスナーを設定
    if (emailInput) {
        emailInput.addEventListener('input', debouncedValidateEmail);
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', debouncedValidatePassword);
    }

    if (confirmationInput) {
        confirmationInput.addEventListener('input', debouncedValidatePasswordConfirmation);
    }

    if (timezoneSelect) {
        timezoneSelect.addEventListener('change', validateTimezone);
    }
    
    // 同意チェックボックスのイベントリスナー
    if (privacyCheckbox) {
        privacyCheckbox.addEventListener('change', updateSubmitButtonStateWithConsent);
    }
    
    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', updateSubmitButtonStateWithConsent);
    }

    // フォーム送信時のバリデーション
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const fieldsValid = isFormValid(['username', 'email', 'password', 'password_confirmation']);
            const consentsChecked = privacyCheckbox && privacyCheckbox.checked && termsCheckbox && termsCheckbox.checked;

            if (!fieldsValid || !consentsChecked) {
                e.preventDefault();
                
                if (!consentsChecked) {
                    alert('プライバシーポリシーと利用規約への同意が必要です。');
                } else {
                    alert('入力内容に誤りがあります。すべての項目を正しく入力してください。');
                }

                if (import.meta.env.DEV) {
                    console.warn('[RegisterValidation] Form submission blocked:', { fieldsValid, consentsChecked, validationState: window.validationState });
                }
            } else {
                if (import.meta.env.DEV) {
                    console.log('[RegisterValidation] Form is valid, submitting');
                }
            }
        });
    }
});