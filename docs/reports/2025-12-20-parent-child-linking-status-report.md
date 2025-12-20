# 親子紐付け機能 実装状況レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-20 | GitHub Copilot | 初版作成: 親子紐付け機能の実装状況調査 |

---

## 1. 概要

13歳未満のユーザーアカウント作成プロセスにおいて、**子のメールアドレスに通知が届かない**という問題の調査結果を報告します。

**結論**: 親子紐付けプロセスで**メール通知機能が未実装**であることが判明しました。

---

## 2. 現在の実装状況

### ✅ 実装済み機能

#### 2.1 データベース構造
- [x] `users` テーブル拡張（マイグレーション実施済み）
  - `birthdate`: 生年月日
  - `is_minor`: 未成年フラグ
  - `parent_email`: 保護者のメールアドレス
  - `parent_consent_token`: 保護者同意確認用トークン
  - `parent_consented_at`: 保護者同意日時
  - `parent_consent_expires_at`: 保護者同意有効期限
  - `parent_user_id`: 親ユーザーID
  - `parent_invitation_token`: 親の招待トークン

#### 2.2 親子紐付け機能（プッシュ通知のみ）
- [x] **親から子への紐付けリクエスト送信**
  - Web: `SendChildLinkRequestAction`
  - API: `SendChildLinkRequestApiAction`
  - **プッシュ通知のみ** - メール通知なし

- [x] **子が紐付けリクエストを承認**
  - API: `ApproveParentLinkApiAction`

- [x] **子が紐付けリクエストを拒否**
  - API: `RejectParentLinkApiAction`

- [x] **未紐付け子アカウント検索**
  - Web: `SearchUnlinkedChildrenAction`
  - API: `SearchUnlinkedChildrenApiAction`
  - 検索条件: `parent_email`, `is_minor=true`, `parent_user_id=NULL`, `group_id=NULL`

### ❌ 未実装機能

#### 2.3 メール通知機能（重要）

**問題**: 以下のメール通知が**全て未実装**です。

1. **子アカウント登録時の保護者への通知**
   - 保護者の`parent_email`にメール送信
   - 内容: 「お子様がアカウントを作成しました。紐付けを実施してください。」
   - リンク: アプリログイン画面 or 検索画面

2. **親から子への紐付けリクエスト送信時の通知**
   - 子の`email`にメール送信（プッシュ通知のみで実装済み）
   - 内容: 「保護者から紐付けリクエストが届いています。」

3. **子が紐付けを承認/拒否した時の保護者への通知**
   - 保護者の`email`にメール送信
   - 内容: 「お子様が紐付けリクエストを承認/拒否しました。」

---

## 3. 問題の詳細

### 3.1 ユーザーが報告している問題

> 13歳未満のユーザーがアカウントを新規登録→親のメールアドレスに同意確認→親同意し、アカウント作成→親がログインして紐づけ検索を実行→子のユーザーで登録したメールアドレス宛にメール届かず

**現在のフロー**:
```
[子: アカウント登録]
↓
is_minor=true
parent_email=(親のメール)を入力
↓
DB登録成功
↓
❌ メール送信なし（実装されていない）
↓
[親: ログイン]
↓
[親: 子アカウント検索]（parent_emailで検索）
↓
検索成功（子アカウントが見つかる）
↓
[親: 紐付けリクエスト送信]
↓
✅ プッシュ通知のみ送信（子のアプリ内通知）
↓
❌ メール通知なし（実装されていない）
```

**期待されるフロー**:
```
[子: アカウント登録]
↓
✅ 保護者にメール送信: 「お子様がアカウントを作成しました」
↓
[親: メール受信 → ログイン]
↓
[親: 紐付けリクエスト送信]
↓
✅ 子にプッシュ通知 + メール送信
↓
[子: 承認]
↓
✅ 親にメール送信: 「お子様が紐付けを承認しました」
```

### 3.2 技術的詳細

**実装済みの通知方法**:
- プッシュ通知のみ（`SendPushNotificationJob` を使用）
- ファイル: `SendChildLinkRequestApiAction.php` Line 113-125

**未実装の通知方法**:
- メール送信（`Notification` or `Mail` クラス）

---

## 4. 必要な実装

### 4.1 メール通知の追加（優先度: 高）

#### 4.1.1 子アカウント登録時の保護者へのメール

**実装箇所**: `RegisterAction.php`, `RegisterApiAction.php`

**Notificationクラス作成**:
```php
// app/Notifications/ChildAccountCreatedNotification.php
class ChildAccountCreatedNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('【My Teacher】お子様がアカウントを作成しました')
            ->line('お子様がMy Teacherにアカウントを作成しました。')
            ->line('アプリにログインして、紐付けを実施してください。')
            ->action('アプリを開く', url('/login'))
            ->line('ご不明な点がございましたら、サポートまでお問い合わせください。');
    }
}
```

**送信処理追加**:
```php
// RegisterApiAction.php (Line 100付近)
if ($is_minor && $parentEmail) {
    // 保護者にメール送信
    Notification::route('mail', $parentEmail)->notify(
        new ChildAccountCreatedNotification($user)
    );
}
```

#### 4.1.2 紐付けリクエスト送信時の子へのメール

**実装箇所**: `SendChildLinkRequestApiAction.php`

**Notificationクラス作成**:
```php
// app/Notifications/ParentLinkRequestNotification.php
class ParentLinkRequestNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('【My Teacher】保護者アカウントとの紐付けリクエスト')
            ->line(sprintf('%s さんから親子アカウントの紐付けリクエストが届いています。', $this->parentName))
            ->line('アプリにログインして、リクエストを承認または拒否してください。')
            ->action('アプリを開く', url('/notifications'))
            ->line('承認すると、保護者があなたのタスクを管理できるようになります。');
    }
}
```

**送信処理追加**:
```php
// SendChildLinkRequestApiAction.php (Line 125付近)
try {
    // プッシュ通知
    SendPushNotificationJob::dispatch($userNotificationId, $childUser->id);
    
    // メール通知（追加）
    $childUser->notify(new ParentLinkRequestNotification($parentUser));
} catch (\Exception $e) {
    Log::error('Failed to send notifications', [
        'error' => $e->getMessage(),
    ]);
}
```

#### 4.1.3 紐付け承認/拒否時の保護者へのメール

**実装箇所**: `ApproveParentLinkApiAction.php`, `RejectParentLinkApiAction.php`

**Notificationクラス作成**:
```php
// app/Notifications/ParentLinkResponseNotification.php
class ParentLinkResponseNotification extends Notification
{
    public function __construct(
        private User $childUser,
        private bool $approved
    ) {}
    
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->approved 
            ? '【My Teacher】紐付けリクエストが承認されました'
            : '【My Teacher】紐付けリクエストが拒否されました';
            
        $message = (new MailMessage)
            ->subject($subject)
            ->line(sprintf(
                '%s さんが紐付けリクエストを%sしました。',
                $this->childUser->name ?? $this->childUser->username,
                $this->approved ? '承認' : '拒否'
            ));
            
        if ($this->approved) {
            $message->line('お子様のタスクを管理できるようになりました。');
        } else {
            $message->line('お子様は紐付けを希望していません。直接ご確認ください。');
        }
        
        return $message;
    }
}
```

### 4.2 メール設定の確認

`.env` ファイルでメール設定が正しいか確認:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@myteacher.com
MAIL_FROM_NAME="${APP_NAME}"
```

**テスト送信**:
```bash
php artisan tinker
>>> Notification::route('mail', 'test@example.com')->notify(new \App\Notifications\ChildAccountCreatedNotification(\App\Models\User::first()));
```

---

## 5. 実装優先順位

| 優先度 | タスク | 工数 | 備考 |
|--------|--------|------|------|
| 🔴 最高 | 子アカウント登録時の保護者へのメール通知 | 2h | ユーザーが困っている主要問題 |
| 🟠 高 | 紐付けリクエスト送信時の子へのメール通知 | 2h | プッシュ通知だけでは不十分 |
| 🟡 中 | 紐付け承認/拒否時の保護者へのメール通知 | 2h | フィードバック重要 |
| 🔵 低 | メール送信エラーのリトライ機能 | 3h | 信頼性向上 |

**合計**: 約9時間

---

## 6. テスト要件

### 6.1 単体テスト

```php
// tests/Unit/Notifications/ChildAccountCreatedNotificationTest.php
test('保護者へのメール通知が生成される', function () {
    $user = User::factory()->create(['is_minor' => true]);
    $notification = new ChildAccountCreatedNotification($user);
    
    $mail = $notification->toMail(null);
    
    expect($mail->subject)->toContain('お子様がアカウントを作成しました');
});
```

### 6.2 統合テスト

```php
// tests/Feature/Auth/RegisterMinorWithEmailTest.php
test('子アカウント登録時に保護者にメールが送信される', function () {
    Mail::fake();
    
    $this->post('/api/v1/auth/register', [
        'username' => 'child_user',
        'email' => 'child@example.com',
        'password' => 'password123',
        'birthdate' => now()->subYears(10)->format('Y-m-d'),
        'parent_email' => 'parent@example.com',
    ]);
    
    Mail::assertSent(ChildAccountCreatedNotification::class, function ($mail) {
        return $mail->hasTo('parent@example.com');
    });
});
```

---

## 7. リスク管理

| リスク | 影響度 | 対策 |
|--------|--------|------|
| メール送信失敗（Gmail制限等） | 高 | リトライ機能、ログ記録、キュー使用 |
| 迷惑メールフォルダに振り分け | 中 | SPF/DKIM/DMARC設定、送信元ドメイン認証 |
| 保護者がメールを見ない | 低 | アプリ内通知も併用 |

---

## 8. 次のアクション

1. **Phase 1**: 子アカウント登録時の保護者へのメール通知実装（2h）
2. **Phase 2**: 紐付けリクエスト送信時の子へのメール通知実装（2h）
3. **Phase 3**: 紐付け承認/拒否時の保護者へのメール通知実装（2h）
4. **Phase 4**: テスト実装（3h）

**合計**: 約9時間

---

以上
