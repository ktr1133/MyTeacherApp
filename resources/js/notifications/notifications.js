/**
 * お知らせページ（ユーザー向け）のクライアントサイドロジック
 * 
 * - アラートの自動フェードアウト
 * 
 * @module notifications
 */

/**
 * 初期化処理
 */
document.addEventListener('DOMContentLoaded', function() {
    initAlertAutoFade();
});

/**
 * アラートの自動フェードアウト
 * 
 * 成功メッセージを5秒後に自動で非表示にする。
 */
function initAlertAutoFade() {
    const alerts = document.querySelectorAll('.notification-alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
}