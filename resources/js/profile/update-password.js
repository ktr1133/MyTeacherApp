/**
 * パスワード更新フォーム用JavaScript
 * 
 * 機能:
 * - パスワード表示/非表示切り替え
 * - 複数のパスワードフィールドに対応
 * - フォーム送信前のバリデーション
 * - リアルタイムパスワード強度表示
 */

import { PasswordStrengthChecker } from '../components/password-strength';

document.addEventListener('DOMContentLoaded', function() {
    console.log('[Update Password] DOM loaded, initializing...');
    
    // パスワード更新フォームを取得
    const passwordForm = document.getElementById('update-password-form');
    
    // パスワード強度チェッカーを初期化
    const strengthChecker = new PasswordStrengthChecker(
        '#update_password_password',
        '#password-strength-meter'
    );
    
    if (!strengthChecker.input || !strengthChecker.meter) {
        console.error('[Update Password] Failed to initialize password strength checker');
    }
    
    // すべてのパスワード表示切り替えボタンを取得
    const toggleButtons = document.querySelectorAll('[data-toggle-password]');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-toggle-password');
            const passwordInput = document.getElementById(targetId);
            
            if (!passwordInput) {
                console.warn(`[Password Toggle] Input field not found: ${targetId}`);
                return;
            }
            
            // パスワードの表示/非表示を切り替え
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // アイコンの切り替え
            const eyeIcon = this.querySelector('.eye-icon');
            const eyeOffIcon = this.querySelector('.eye-off-icon');
            
            if (eyeIcon && eyeOffIcon) {
                eyeIcon.classList.toggle('hidden');
                eyeOffIcon.classList.toggle('hidden');
            }
        });
    });
    
    // フォーム送信時のバリデーション
    if (passwordForm) {
        console.log('[Password Form] Form found and event listener attached');
        
        passwordForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // 常にデフォルト動作を防止
            e.stopPropagation();
            
            console.log('[Password Form] Submit event triggered');
            
            const currentPasswordInput = document.getElementById('update_password_current_password');
            const newPasswordInput = document.getElementById('update_password_password');
            const confirmPasswordInput = document.getElementById('update_password_password_confirmation');
            
            if (!currentPasswordInput || !newPasswordInput || !confirmPasswordInput) {
                console.warn('[Password Form] Input fields not found');
                return;
            }
            
            const currentPassword = currentPasswordInput.value.trim();
            const newPassword = newPasswordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();
            
            console.log('[Password Form] Values:', {
                current: currentPassword ? '***' : '(empty)',
                new: newPassword ? '***' : '(empty)',
                confirm: confirmPassword ? '***' : '(empty)'
            });
            
            // クライアント側バリデーション
            let errorMessage = '';
            
            if (currentPassword === '') {
                errorMessage = '現在のパスワードを入力してください。';
            }
            else if (newPassword === '') {
                errorMessage = '新しいパスワードを入力してください。';
            }
            else if (confirmPassword === '') {
                errorMessage = '確認用パスワードを入力してください。';
            }
            else if (newPassword !== confirmPassword) {
                errorMessage = '新しいパスワードと確認用パスワードが一致しません。\n\n入力内容を確認してください。';
            }
            else {
                // 厳格なパスワード強度チェック
                const validation = strengthChecker.getValidationResult();
                
                if (!validation.isValid) {
                    errorMessage = '新しいパスワードが以下の条件を満たしていません:\n\n' + 
                        validation.errors.join('\n');
                }
            }
            
            if (errorMessage !== '') {
                console.log('[Password Form] Client validation error:', errorMessage);
                if (typeof window.showAlertDialog === 'function') {
                    window.showAlertDialog(errorMessage, 'パスワード更新エラー');
                } else {
                    console.error('[Password Form] showAlertDialog is not available, using alert()');
                    alert(errorMessage);
                }
                return false;
            }
            
            // サーバー側バリデーション（非同期）
            console.log('[Password Form] Client validation passed, sending to server');
            
            // 送信ボタンを無効化
            const submitButton = passwordForm.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '更新中...';
            }
            
            try {
                // CSRFトークン取得
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                // Web版エンドポイント経由でパスワード更新
                const response = await fetch('/profile/password', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        current_password: currentPassword,
                        password: newPassword,
                        password_confirmation: confirmPassword
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    // 成功時
                    console.log('[Password Form] Password updated successfully');
                    
                    // フォームをクリア
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmPasswordInput.value = '';
                    
                    // 成功メッセージ表示
                    if (typeof window.showAlertDialog === 'function') {
                        window.showAlertDialog(
                            'パスワードが正常に更新されました。',
                            '更新完了',
                            () => {
                                // モーダルを閉じた後、ページをリロード（セッション更新のため）
                                window.location.reload();
                            }
                        );
                    } else {
                        alert('パスワードが正常に更新されました。');
                        window.location.reload();
                    }
                } else {
                    // サーバー側バリデーションエラー
                    console.log('[Password Form] Server validation error:', data);
                    
                    const errorMessages = [];
                    
                    if (data.errors) {
                        // エラーメッセージを人間が読める形式に変換
                        const formatErrorMessage = (message) => {
                            const messageMap = {
                                'validation.current_password': '入力された現在のパスワードが正しくありません。',
                                'The current password field is required.': '現在のパスワードを入力してください。',
                                'The password field is required.': '新しいパスワードを入力してください。',
                                'The password confirmation does not match.': '確認用パスワードが一致しません。',
                                'The password must be at least 8 characters.': 'パスワードは8文字以上で入力してください。',
                                '現在のパスワードが正しくありません': '入力された現在のパスワードが正しくありません。',
                            };
                            return messageMap[message] || message;
                        };
                        
                        if (data.errors.current_password) {
                            const messages = data.errors.current_password.map(msg => formatErrorMessage(msg));
                            errorMessages.push('【現在のパスワード】\n' + messages.join('\n'));
                        }
                        if (data.errors.password) {
                            const messages = data.errors.password.map(msg => formatErrorMessage(msg));
                            errorMessages.push('【新しいパスワード】\n' + messages.join('\n'));
                        }
                        if (data.errors.password_confirmation) {
                            const messages = data.errors.password_confirmation.map(msg => formatErrorMessage(msg));
                            errorMessages.push('【確認用パスワード】\n' + messages.join('\n'));
                        }
                    }
                    
                    const displayMessage = errorMessages.length > 0 
                        ? errorMessages.join('\n\n')
                        : (data.message || 'パスワードの更新に失敗しました。');
                    
                    if (typeof window.showAlertDialog === 'function') {
                        window.showAlertDialog(displayMessage, 'パスワード更新エラー');
                    } else {
                        alert(displayMessage);
                    }
                }
            } catch (error) {
                console.error('[Password Form] Network error:', error);
                
                const errorMsg = 'ネットワークエラーが発生しました。\nインターネット接続を確認してください。';
                if (typeof window.showAlertDialog === 'function') {
                    window.showAlertDialog(errorMsg, 'エラー');
                } else {
                    alert(errorMsg);
                }
            } finally {
                // 送信ボタンを再度有効化
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = '保存';
                }
            }
            
            return false;
        });
    } else {
        console.warn('[Password Form] Form not found');
    }
});
