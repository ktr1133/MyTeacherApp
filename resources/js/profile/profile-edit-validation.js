/**
 * プロフィール編集バリデーション
 * email, username の非同期バリデーション（自己除外付き）
 */

import {
    debounce,
    showSpinner,
    hideSpinner,
    showError,
    showSuccess,
    hideValidationMessage,
    validateField,
    initValidationState,
    updateSubmitButtonState,
} from '../common/validation-core.js';

// バリデーション状態を初期化
initValidationState(['username', 'email']);

/**
 * ユーザー名バリデーション（自己除外付き）
 */
async function validateUsername() {
    const usernameInput = document.getElementById('username');
    const username = usernameInput.value.trim();
    const userId = usernameInput.dataset.userId; // data-user-id属性から取得

    // 空の場合は非表示
    if (!username || username.length === 0) {
        hideValidationMessage('username');
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[ProfileEditValidation] Validating username:', username, 'userId:', userId);
    }

    showSpinner('username');

    const result = await validateField('/validate/username', { 
        username,
        exclude_user_id: userId 
    }, 'username');

    hideSpinner('username');

    if (result) {
        if (result.valid) {
            showSuccess('username', result.message || '✓ 利用可能なユーザー名です');
        } else {
            showError('username', result.message || '× このユーザー名は既に使用されています');
        }
    }

    // ボタン状態を更新
    updateSubmitButtonState('profile-update-btn', ['username', 'email']);
}

/**
 * メールアドレスバリデーション（自己除外付き）
 */
async function validateEmail() {
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();
    const userId = emailInput.dataset.userId; // data-user-id属性から取得

    if (!email || email.length === 0) {
        hideValidationMessage('email');
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[ProfileEditValidation] Validating email:', email, 'userId:', userId);
    }

    showSpinner('email');

    const result = await validateField('/validate/email', { 
        email,
        exclude_user_id: userId 
    }, 'email');

    hideSpinner('email');

    if (result) {
        if (result.valid) {
            showSuccess('email', result.message || '✓ 利用可能なメールアドレスです');
        } else {
            showError('email', result.message || '× このメールアドレスは既に使用されています');
        }
    }

    // ボタン状態を更新
    updateSubmitButtonState('profile-update-btn', ['username', 'email']);
}

// デバウンス付きバリデーション関数
const debouncedValidateUsername = debounce(validateUsername, 500);
const debouncedValidateEmail = debounce(validateEmail, 500);

// イベントリスナー設定
document.addEventListener('DOMContentLoaded', function() {
    if (import.meta.env.DEV) {
        console.log('[ProfileEditValidation] Initializing');
    }

    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const profileForm = document.querySelector('form[action*="profile.update"]');

    // ユーザーIDをdata属性として設定（Bladeから取得）
    const userId = document.querySelector('input[name="_token"]')?.form?.querySelector('input[name="user_id"]')?.value 
                || document.body.dataset.userId
                || null;

    if (userId && usernameInput) {
        usernameInput.dataset.userId = userId;
    }
    if (userId && emailInput) {
        emailInput.dataset.userId = userId;
    }

    if (import.meta.env.DEV) {
        console.log('[ProfileEditValidation] User ID:', userId);
    }

    // ユーザー名バリデーション
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            debouncedValidateUsername();
        });
    }

    // メールアドレスバリデーション
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            debouncedValidateEmail();
        });
    }

    // フォーム送信時のバリデーション
    if (profileForm) {
        profileForm.addEventListener('submit', function(event) {
            // すべてのフィールドが有効でない場合は送信をブロック
            if (!isFormValid(['username', 'email'])) {
                event.preventDefault();
                
                if (import.meta.env.DEV) {
                    console.log('[ProfileEditValidation] Form submission blocked: validation failed');
                }

                // エラーメッセージを表示
                alert('入力内容に誤りがあります。修正してから再度送信してください。');
            }
        });
    }

    // 保存成功メッセージの自動非表示
    const successMessage = document.getElementById('profile-success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 300);
        }, 2000);
    }
});
