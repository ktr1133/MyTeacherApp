<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * パスワードリセット通知
 * 
 * カスタマイズ内容:
 * - 日本語メッセージ
 * - MyTeacherブランディング
 * - セキュリティ注意事項の追加
 */
class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * メール通知の内容を構築
     *
     * @param mixed $notifiable 通知先のユーザー
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('【MyTeacher】パスワードリセットのご案内')
            ->greeting('こんにちは、' . $notifiable->username . ' さん')
            ->line('パスワードリセットのリクエストを受け付けました。')
            ->line('下記のボタンをクリックして、新しいパスワードを設定してください。')
            ->action('パスワードをリセット', $url)
            ->line('このリンクは **' . config('auth.passwords.'.config('auth.defaults.passwords').'.expire') . '分間** 有効です。')
            ->line('')
            ->line('**セキュリティのご注意**')
            ->line('• パスワードリセットを依頼していない場合は、このメールを無視してください。')
            ->line('• リンクは他人と共有しないでください。')
            ->line('• ボタンが機能しない場合は、以下のURLをブラウザにコピー&ペーストしてください:')
            ->line($url)
            ->salutation('MyTeacherチーム');
    }
}
