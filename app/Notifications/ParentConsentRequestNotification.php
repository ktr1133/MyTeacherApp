<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 保護者同意依頼通知（13歳未満新規登録時）
 * 
 * 13歳未満のユーザーが新規登録した際に、保護者に同意を依頼するメールを送信します。
 * 
 * Phase 5-2: 13歳未満新規登録時の保護者メール同意実装
 */
class ParentConsentRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * コンストラクタ
     * 
     * @param User $childUser 子ユーザー（13歳未満の登録者）
     * @param string $token 保護者同意確認用トークン
     */
    public function __construct(
        private User $childUser,
        private string $token
    ) {}

    /**
     * 通知チャンネルを取得
     * 
     * @param mixed $notifiable 通知先（保護者のメールアドレス）
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * メール通知の内容を構築
     *
     * @param mixed $notifiable 通知先
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $consentUrl = url(route('legal.parent-consent', ['token' => $this->token]));
        $childUsername = $this->childUser->username;
        $childEmail = $this->childUser->email;
        $age = $this->childUser->birthdate?->age ?? '13歳未満';
        $expiresAt = $this->childUser->parent_consent_expires_at?->format('Y年m月d日 H:i') ?? '7日後';

        return (new MailMessage)
            ->subject('【MyTeacher】お子様のアカウント登録 - 保護者同意のお願い')
            ->greeting('保護者の皆様へ')
            ->line('お子様（' . $childUsername . ' さん）が MyTeacher に登録しようとしています。')
            ->line('')
            ->line('**お子様の情報**')
            ->line('• ユーザー名: ' . $childUsername)
            ->line('• メールアドレス: ' . $childEmail)
            ->line('• 年齢: ' . $age)
            ->line('')
            ->line('**保護者同意のお願い**')
            ->line('13歳未満のお子様がサービスを利用するには、')
            ->line('保護者の方の同意が必要です（児童オンラインプライバシー保護法 COPPA 対応）。')
            ->line('')
            ->line('以下のボタンをクリックして、プライバシーポリシーと利用規約を')
            ->line('ご確認の上、同意していただくようお願いいたします。')
            ->line('')
            ->action('同意する', $consentUrl)
            ->line('')
            ->line('**重要なお知らせ**')
            ->line('• 同意期限: ' . $expiresAt . ' まで（7日間）')
            ->line('• 期限内に同意されない場合、アカウントは自動削除されます')
            ->line('• このリンクは一度のみ有効です')
            ->line('• 本メールに心当たりがない場合は、お子様にご確認ください')
            ->line('')
            ->line('ご不明な点がございましたら、お気軽にお問い合わせください。')
            ->salutation('MyTeacherチーム');
    }

    /**
     * データベース通知の配列表現を取得
     * 
     * 注: このNotificationは保護者のメールアドレスに送信されるため、
     * データベース通知は作成されません（保護者はシステムのユーザーではないため）。
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'parent_consent_request',
            'child_user_id' => $this->childUser->id,
            'child_username' => $this->childUser->username,
            'token' => $this->token,
            'expires_at' => $this->childUser->parent_consent_expires_at?->toISOString(),
        ];
    }
}
