/**
 * グループ管理バリデーション
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

/**
 * グループ名バリデーション
 */
async function validateGroupName() {
    const groupNameInput = document.getElementById('group_name');
    const groupName = groupNameInput.value.trim();

    // 空の場合は非表示
    if (!groupName || groupName.length === 0) {
        hideValidationMessage('group_name');
        updateSubmitButtonState('group-update-button', ['group_name']);
        return;
    }

    // 現在のグループID（更新時のみ）
    const currentGroupId = groupNameInput.dataset.currentGroupId || null;

    if (import.meta.env.DEV) {
        console.log('[ProfileValidation] Validating group name:', groupName, 'currentGroupId:', currentGroupId);
    }

    showSpinner('group_name');

    const result = await validateField(
        '/validate/group-name',
        { 
            group_name: groupName,
            current_group_id: currentGroupId 
        },
        'group_name'
    );

    hideSpinner('group_name');

    if (result) {
        if (result.valid) {
            showSuccess('group_name', result.message || '✓ 利用可能なグループ名です');
        } else {
            showError('group_name', result.message || '× このグループ名は既に使用されています');
        }
    }

    // ボタン状態を更新
    updateSubmitButtonState('group-update-button', ['group_name']);
}

/**
 * メンバー追加時のユーザー名バリデーション
 */
async function validateMemberUsername() {
    const usernameInput = document.getElementById('username');
    const username = usernameInput.value.trim();

    if (!username || username.length === 0) {
        hideValidationMessage('username');
        updateSubmitButtonState('add-member-button', ['username', 'password']); // ★ ボタン状態更新
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[ProfileValidation] Validating member username:', username);
    }

    showSpinner('username');

    const result = await validateField('/validate/member-username', { username }, 'username');

    hideSpinner('username');

    if (result) {
        if (result.valid) {
            showSuccess('username', result.message || '✓ 利用可能なユーザー名です');
        } else {
            showError('username', result.message || '× このユーザー名は既に使用されています');
        }
    }

    // ボタン状態を更新（同意チェック含む）
    updateAddMemberButtonStateWithConsent();
}

/**
 * メンバー追加時のメールアドレスバリデーション
 */
async function validateMemberEmail() {
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();

    if (!email || email.length === 0) {
        hideValidationMessage('email');
        updateAddMemberButtonStateWithConsent();
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[ProfileValidation] Validating member email:', email);
    }

    showSpinner('email');

    const result = await validateField('/validate/member-email', { email }, 'email');

    hideSpinner('email');

    if (result) {
        if (result.valid) {
            showSuccess('email', result.message || '✓ 利用可能なメールアドレスです');
        } else {
            showError('email', result.message || '× このメールアドレスは既に使用されています');
        }
    }

    // ボタン状態を更新（同意チェック含む）
    updateAddMemberButtonStateWithConsent();
}

/**
 * メンバー追加時のパスワードバリデーション
 */
async function validateMemberPassword() {
    const passwordInput = document.getElementById('password');
    const password = passwordInput.value;

    if (!password || password.length === 0) {
        hideValidationMessage('password');
        updateAddMemberButtonStateWithConsent();
        return;
    }

    if (import.meta.env.DEV) {
        console.log('[ProfileValidation] Validating member password');
    }

    showSpinner('password');

    const result = await validateField('/validate/member-password', { password }, 'password');

    hideSpinner('password');

    if (result) {
        if (result.valid) {
            showSuccess('password', result.message || '✓ 十分に強力なパスワードです');
        } else {
            showError('password', result.message || '× パスワードが弱すぎます');
        }
    }

    // ボタン状態を更新（同意チェック含む）
    updateAddMemberButtonStateWithConsent();
}

// デバウンス付きバリデーション関数
const debouncedValidateGroupName = debounce(validateGroupName, 500);
const debouncedValidateMemberUsername = debounce(validateMemberUsername, 500);
const debouncedValidateMemberEmail = debounce(validateMemberEmail, 500);
const debouncedValidateMemberPassword = debounce(validateMemberPassword, 500);

/**
 * メンバー追加時の送信ボタン状態更新（同意チェック付き）
 */
function updateAddMemberButtonStateWithConsent() {
    const addMemberButton = document.getElementById('add-member-button');
    const privacyPolicyConsentCheckbox = document.getElementById('privacy_policy_consent');
    const termsConsentCheckbox = document.getElementById('terms_consent');

    if (!addMemberButton) {
        return;
    }

    // バリデーション状態を確認
    const fieldsValid = isFormValid(['username', 'email', 'password']);
    
    // 同意チェックボックスの状態を確認
    const consentGiven = privacyPolicyConsentCheckbox && termsConsentCheckbox &&
                         privacyPolicyConsentCheckbox.checked && termsConsentCheckbox.checked;

    // 全ての条件を満たしている場合のみボタンを有効化
    if (fieldsValid && consentGiven) {
        addMemberButton.disabled = false;
        addMemberButton.classList.remove('opacity-50', 'cursor-not-allowed');
        
        if (import.meta.env.DEV) {
            console.log('[ProfileValidation] Add member button enabled');
        }
    } else {
        addMemberButton.disabled = true;
        addMemberButton.classList.add('opacity-50', 'cursor-not-allowed');
        
        if (import.meta.env.DEV) {
            console.log('[ProfileValidation] Add member button disabled:', {
                fieldsValid,
                consentGiven
            });
        }
    }
}

// イベントリスナー設定
document.addEventListener('DOMContentLoaded', function() {
    if (import.meta.env.DEV) {
        console.log('[ProfileValidation] Initializing');
    }

    // ===================================
    // グループ情報更新フォーム
    // ===================================
    const groupNameInput = document.getElementById('group_name');
    const groupUpdateForm = document.getElementById('group-update-form');
    const groupUpdateButton = document.getElementById('group-update-button');

    if (groupNameInput) {
        // バリデーション状態を初期化
        initValidationState(['group_name']);

        // 初期状態でボタンを非活性化
        if (groupUpdateButton) {
            groupUpdateButton.disabled = true;
            groupUpdateButton.classList.add('opacity-50', 'cursor-not-allowed');
        }

        groupNameInput.addEventListener('input', debouncedValidateGroupName);

        if (import.meta.env.DEV) {
            console.log('[ProfileValidation] Group name validation enabled');
        }
    }

    if (groupUpdateForm) {
        groupUpdateForm.addEventListener('submit', function(e) {
            const valid = isFormValid(['group_name']);

            if (!valid) {
                e.preventDefault();
                alert('グループ名に誤りがあります。修正してください。');

                if (import.meta.env.DEV) {
                    console.warn('[ProfileValidation] Group update blocked:', window.validationState);
                }
            } else {
                if (import.meta.env.DEV) {
                    console.log('[ProfileValidation] Group update form is valid, submitting');
                }
            }
        });
    }

    // ===================================
    // メンバー追加フォーム
    // ===================================
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const privacyPolicyConsentCheckbox = document.getElementById('privacy_policy_consent');
    const termsConsentCheckbox = document.getElementById('terms_consent');
    const addMemberForm = document.getElementById('add-member-form');
    const addMemberButton = document.getElementById('add-member-button');

    if (usernameInput && emailInput && passwordInput) {
        // バリデーション状態を初期化
        initValidationState(['username', 'email', 'password']);

        // 初期状態でボタンを非活性化
        if (addMemberButton) {
            addMemberButton.disabled = true;
            addMemberButton.classList.add('opacity-50', 'cursor-not-allowed');
        }

        usernameInput.addEventListener('input', debouncedValidateMemberUsername);
        emailInput.addEventListener('input', debouncedValidateMemberEmail);
        passwordInput.addEventListener('input', debouncedValidateMemberPassword);

        // 同意チェックボックスのイベントリスナー追加
        if (privacyPolicyConsentCheckbox && termsConsentCheckbox) {
            privacyPolicyConsentCheckbox.addEventListener('change', () => {
                updateAddMemberButtonStateWithConsent();
            });
            termsConsentCheckbox.addEventListener('change', () => {
                updateAddMemberButtonStateWithConsent();
            });
        }

        if (import.meta.env.DEV) {
            console.log('[ProfileValidation] Member validation enabled (username, email, password, consent)');
        }
    }

    if (addMemberForm) {
        addMemberForm.addEventListener('submit', function(e) {
            const valid = isFormValid(['username', 'email', 'password']);

            if (!valid) {
                e.preventDefault();
                alert('入力内容に誤りがあります。すべての項目を正しく入力してください。');

                if (import.meta.env.DEV) {
                    console.warn('[ProfileValidation] Add member blocked:', window.validationState);
                }
            } else {
                if (import.meta.env.DEV) {
                    console.log('[ProfileValidation] Add member form is valid, submitting');
                }
            }
        });
    }

    // 追加成功メッセージの自動非表示
    const memberAddedMessage = document.getElementById('member-added-message');
    if (memberAddedMessage) {
        setTimeout(() => {
            memberAddedMessage.style.opacity = '0';
            setTimeout(() => {
                memberAddedMessage.style.display = 'none';
            }, 300);
        }, 2000);
    }
});