<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 本人同意必要通知（13歳到達時）
 * 
 * 13歳に到達したユーザーに本人同意が必要であることを通知します。
 * 保護者にも通知を送信します。
 * 
 * Phase 6D: 13歳到達時の本人再同意実装
 */
class SelfConsentRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * コンストラクタ
     * 
     * @param User|null $childUser 子ユーザー（保護者への通知時に使用）
     */
    public function __construct(
        private ?User $childUser = null
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
        // 保護者への通知か、本人への通知かで内容を変える
        if ($this->childUser) {
            return $this->toParentMail($notifiable);
        }

        return $this->toChildMail($notifiable);
    }

    /**
     * 本人へのメール通知
     * 
     * @param mixed $notifiable 通知先のユーザー（子本人）
     * @return MailMessage
     */
    private function toChildMail($notifiable): MailMessage
    {
        $url = url(route('legal.self-consent'));
        $age = $notifiable->birthdate?->age ?? 13;

        return (new MailMessage)
            ->subject('【MyTeacher】おめでとうございます！13歳になりました 🎉')
            ->greeting('こんにちは、' . $notifiable->username . ' さん')
            ->line('おめでとうございます！あなたは' . $age . '歳になりました。')
            ->line('')
            ->line('**これからのこと**')
            ->line('これまでは保護者の方が代わりに同意していましたが、')
            ->line('これからは、あなた自身で同意を行う必要があります。')
            ->line('')
            ->line('プライバシーポリシーと利用規約をよく読んで、')
            ->line('わからないところがあれば保護者の方に聞いてから、')
            ->line('あなた自身で同意してください。')
            ->line('')
            ->action('本人同意をする', $url)
            ->line('')
            ->line('**重要なお知らせ**')
            ->line('• 次回ログイン時に本人同意画面が表示されます。')
            ->line('• 同意しないと、サービスが使えなくなります。')
            ->line('• 保護者の方と一緒に内容を確認してください。')
            ->salutation('MyTeacherチーム');
    }

    /**
     * 保護者へのメール通知
     * 
     * @param mixed $notifiable 通知先のユーザー（保護者）
     * @return MailMessage
     */
    private function toParentMail($notifiable): MailMessage
    {
        $url = url(route('legal.self-consent'));
        $childName = $this->childUser->username ?? 'お子様';
        $age = $this->childUser->birthdate?->age ?? 13;

        return (new MailMessage)
            ->subject('【MyTeacher】お子様が13歳になりました - 本人同意が必要です')
            ->greeting('こんにちは、' . $notifiable->username . ' さん')
            ->line($childName . ' さんが' . $age . '歳になりました。')
            ->line('')
            ->line('**本人同意のお願い**')
            ->line('これまでは保護者の方が代わりに同意していただいていましたが、')
            ->line('13歳に到達したため、ご本人による同意が必要となります。')
            ->line('')
            ->line('お子様と一緒に以下の内容をご確認の上、')
            ->line('ご本人に同意していただくようお願いいたします：')
            ->line('• プライバシーポリシー（個人情報の取り扱い）')
            ->line('• 利用規約（サービスの使い方とルール）')
            ->line('')
            ->action('本人同意ページを開く', $url)
            ->line('')
            ->line('**重要なお知らせ**')
            ->line('• お子様が次回ログインする際に本人同意画面が表示されます。')
            ->line('• 本人同意が完了するまで、サービスをご利用いただけません。')
            ->line('• お子様だけで判断が難しい場合は、ご一緒にご確認ください。')
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
        if ($this->childUser) {
            // 保護者への通知
            return [
                'type' => 'self_consent_required_parent',
                'title' => 'お子様が13歳になりました',
                'message' => $this->childUser->username . ' さんが13歳に到達したため、本人同意が必要です。',
                'child_user_id' => $this->childUser->id,
                'child_username' => $this->childUser->username,
                'child_age' => $this->childUser->birthdate?->age,
                'action_url' => route('legal.self-consent'),
            ];
        }

        // 本人への通知
        return [
            'type' => 'self_consent_required',
            'title' => 'おめでとうございます！13歳になりました',
            'message' => 'あなた自身で同意を行う必要があります。次回ログイン時に本人同意画面が表示されます。',
            'age' => $notifiable->birthdate?->age,
            'action_url' => route('legal.self-consent'),
        ];
    }
}
