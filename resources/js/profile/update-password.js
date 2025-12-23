/**
 * パスワード更新フォーム用JavaScript
 * 
 * 機能:
 * - パスワード表示/非表示切り替え
 * - 複数のパスワードフィールドに対応
 */

document.addEventListener('DOMContentLoaded', function() {
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
});
