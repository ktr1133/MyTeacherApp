<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 再同意必要通知
 * 
 * プライバシーポリシー・利用規約が更新された際に、
 * ユーザーに再同意が必要であることを通知します。
 * 
 * Phase 6C: 再同意プロセス実装
 */
class ReconsentRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * コンストラクタ
     * 
     * @param string $privacyVersion プライバシーポリシーバージョン
     * @param string $termsVersion 利用規約バージョン
     */
    public function __construct(
        private string $privacyVersion,
        private string $termsVersion
    ) {}

    /**
     * 通知チャンネルを取得
     * 
     * @param mixed $notifiable 通知先のユーザー
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * メール通知の内容を構築
     *
     * @param mixed $notifiable 通知先のユーザー
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('legal.reconsent'));

        return (new MailMessage)
            ->subject('【MyTeacher】プライバシーポリシー・利用規約の更新について')
            ->greeting('こんにちは、' . $notifiable->username . ' さん')
            ->line('MyTeacherのプライバシーポリシーまたは利用規約が更新されました。')
            ->line('引き続きサービスをご利用いただくには、最新版への同意が必要です。')
            ->line('')
            ->line('**更新内容**')
            ->line('• プライバシーポリシー: v' . $this->privacyVersion)
            ->line('• 利用規約: v' . $this->termsVersion)
            ->line('')
            ->action('最新版に同意する', $url)
            ->line('')
            ->line('**重要なお知らせ**')
            ->line('• 次回ログイン時に再同意画面が表示されます。')
            ->line('• 同意いただけない場合、サービスの継続利用ができなくなります。')
            ->line('• 変更内容の詳細は、上記のリンクからご確認ください。')
            ->salutation('MyTeacherチーム');
    }

    /**
     * データベース通知の内容を構築
     *
     * @param mixed $notifiable 通知先のユーザー
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'reconsent_required',
            'title' => 'プライバシーポリシー・利用規約の更新',
            'message' => '最新版への同意が必要です。次回ログイン時に再同意画面が表示されます。',
            'privacy_policy_version' => $this->privacyVersion,
            'terms_version' => $this->termsVersion,
            'action_url' => route('legal.reconsent'),
        ];
    }
}
