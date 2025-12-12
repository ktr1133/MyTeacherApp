# Push通知送信ジョブ実装完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: Push通知送信ジョブ実装完了 |

---

## 概要

MyTeacher モバイルアプリにおけるPush通知送信機能（Firebase Cloud Messaging統合）のバックエンド実装を完了しました。この実装により、以下の目標を達成しました：

- ✅ **Firebase Admin SDK統合**: Laravel環境でFCM経由のPush通知送信機能を実装
- ✅ **非同期ジョブ化**: キュー処理によるスケーラブルな通知送信基盤を構築
- ✅ **通知設定対応**: ユーザーのカテゴリ別ON/OFF設定を尊重した送信制御
- ✅ **エラーハンドリング**: FCM APIエラー時の自動リトライ、無効トークン検出機能を実装

---

## 実施内容詳細

### 1. Firebase Admin SDK統合

#### 1.1 Composerパッケージインストール

```bash
# kreait/firebase-php (バージョン7.0以降)
composer require kreait/firebase-php:'^7.0'
```

**パッケージ情報**:
- **kreait/firebase-php**: Firebase Admin SDK for PHP
- **バージョン**: ^7.0（Laravel 12対応）
- **機能**: FCM Messaging API、認証情報管理

#### 1.2 設定ファイル追加

**ファイル**: `config/services.php`

追加内容:
```php
'firebase' => [
    'credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase/credentials.json')),
],
```

**環境変数**:
```env
FIREBASE_CREDENTIALS_PATH=/home/ktr/mtdev/storage/app/firebase/credentials.json
```

#### 1.3 認証情報配置ディレクトリ作成

- **ディレクトリ**: `storage/app/firebase/`
- **ファイル**: `storage/app/firebase/README.md` - セットアップ手順書
- **セキュリティ**: `.gitignore` に `storage/app/firebase/credentials.json` を追加（機密情報保護）

---

### 2. FCMサービス実装

#### 2.1 FcmServiceInterface

**ファイル**: `app/Services/Fcm/FcmServiceInterface.php`

**メソッド**:
```php
// 単一デバイス送信
public function sendToDevice(string $deviceToken, array $payload): array;

// 複数デバイス送信（バッチ処理）
public function sendToMultipleDevices(array $deviceTokens, array $payload): array;

// ペイロード構築
public function buildPayload(string $title, string $body, array $data = []): array;

// エラー種別判定
public function getErrorType(string $errorCode): string;
```

#### 2.2 FcmService実装

**ファイル**: `app/Services/Fcm/FcmService.php`

**主要機能**:
1. **Firebase Messaging初期化**: Service Account JSONから認証情報を読み込み
2. **CloudMessage送信**: `CloudMessage`クラスによるFCM API呼び出し
3. **エラーハンドリング**: 
   - `MessagingException` → `invalid_token` / `unavailable` / `other` に分類
   - エラー種別に応じた処理分岐（トークン無効化、リトライ等）
4. **バッチ送信**: FCM Multicast API（最大500件/バッチ）を使用
5. **ログ記録**: 送信成功/失敗を詳細にログ出力

**実装例（抜粋）**:
```php
$notification = Notification::create($payload['notification']['title'], $payload['notification']['body']);
$message = CloudMessage::withTarget('token', $deviceToken)
    ->withNotification($notification)
    ->withData($payload['data']);
$this->messaging->send($message);
```

---

### 3. SendPushNotificationJob実装

**ファイル**: `app/Jobs/SendPushNotificationJob.php`

#### 3.1 ジョブ概要

**責務**: `user_notifications` テーブルに登録された通知をFCM経由でユーザーのデバイスに送信

**リトライ設定**:
- 最大3回リトライ
- バックオフ: 1分、5分後に再実行

#### 3.2 処理フロー

```
1. 通知データ取得（UserNotification + NotificationTemplate）
2. 通知設定確認（category別ON/OFF、push_enabled確認）
3. アクティブなデバイストークン取得（is_active=TRUE、30日以内使用）
4. ペイロード構築（notification + data）
5. FCM送信実行（sendToMultipleDevices）
6. エラーハンドリング（invalid_token → is_active=FALSE更新）
```

#### 3.3 実装詳細

**コンストラクタ**:
```php
public function __construct(int $userNotificationId, int $userId)
{
    $this->userNotificationId = $userNotificationId;
    $this->userId = $userId;
}
```

**handle メソッド（依存性注入）**:
```php
public function handle(
    FcmServiceInterface $fcmService,
    DeviceTokenManagementServiceInterface $deviceTokenService,
    NotificationSettingsServiceInterface $notificationSettingsService
): void
```

**通知設定確認**:
```php
$category = $this->getNotificationCategory($template->type); // 'task', 'group', 'token', 'system'
if (!$notificationSettingsService->isPushEnabled($this->userId, $category)) {
    return; // Push通知OFF → 送信スキップ
}
```

**ペイロード構築**:
```php
$payload = $fcmService->buildPayload(
    $template->title,
    $template->message,
    [
        'notification_id' => (string) $this->userNotificationId,
        'type' => $template->type,
        'category' => $category,
        'priority' => (string) $template->priority,
        'action_url' => $template->action_url ?? '',
        'created_at' => $userNotification->created_at->toIso8601String(),
    ]
);
```

**エラーハンドリング（無効トークン検出）**:
```php
foreach ($results['errors'] as $error) {
    $errorType = $fcmService->getErrorType($error['error_code']);
    
    if ($errorType === 'invalid_token') {
        $deviceTokenService->deactivateToken($fullToken); // is_active=FALSE
        Log::info('Device token deactivated due to FCM error', [...]);
    }
}
```

**失敗時処理**:
```php
public function failed(\Throwable $exception): void
{
    Log::error('SendPushNotificationJob failed permanently', [
        'user_notification_id' => $this->userNotificationId,
        'user_id' => $this->userId,
        'error' => $exception->getMessage(),
    ]);
}
```

---

### 4. DIコンテナ設定

**ファイル**: `app/Providers/AppServiceProvider.php`

**追加内容**:
```php
// --- FCM (Firebase Cloud Messaging) ---
$this->app->bind(\App\Services\Fcm\FcmServiceInterface::class, \App\Services\Fcm\FcmService::class);
```

**バインディング一覧**:
- `FcmServiceInterface` → `FcmService`
- `DeviceTokenManagementServiceInterface` → `DeviceTokenManagementService` (既存)
- `NotificationSettingsServiceInterface` → `NotificationSettingsService` (既存)

---

### 5. セキュリティ設定

#### 5.1 .gitignore更新

追加内容:
```ignore
# Firebase Admin SDK credentials (DO NOT COMMIT)
storage/app/firebase/credentials.json
storage/app/firebase/*.json
!storage/app/firebase/.gitkeep
```

**重要**: Firebase Service Account JSONは機密情報のため、Git管理外としました。

#### 5.2 権限設定推奨

```bash
# 認証情報ファイルを読み取り専用に設定
chmod 600 storage/app/firebase/credentials.json
```

---

## 成果と効果

### 定量的効果

| 項目 | 効果 |
|------|------|
| **実装ファイル数** | 4ファイル作成（Interface 1, Service 1, Job 1, README 1）、2ファイル更新（config, provider） |
| **コード行数** | 約550行（コメント含む） |
| **リトライ機能** | 最大3回リトライ（成功率向上） |
| **バッチ処理** | 最大500件/バッチ（FCM制限準拠） |

### 定性的効果

1. **スケーラビリティ**: 非同期ジョブ化により、大量通知送信時もレスポンスを妨げない
2. **保守性向上**: Interface-Implementationパターンによるテスト容易性
3. **エラー耐性**: FCM APIエラーの自動検出・リトライ・トークン無効化
4. **ログ可視性**: 送信成功/失敗を詳細にログ記録（トラブルシューティング容易）

---

## 使用方法

### ジョブディスパッチ例

**タスク完了申請時の通知送信**:
```php
use App\Jobs\SendPushNotificationJob;

// 通知レコード作成後
$notification = UserNotification::create([...]);

// Push通知ジョブをキューにディスパッチ
SendPushNotificationJob::dispatch($notification->id, $notification->user_id);
```

**キューワーカー起動**:
```bash
# 本番環境: Supervisorで常時起動
php artisan queue:work --tries=3 --timeout=60

# 開発環境: コマンド実行
php artisan queue:work
```

---

## 今後の拡張予定

### Phase 2.B-7.5 残作業

1. **モバイルFCMトークン登録処理実装** (React Native)
2. **モバイルPush通知受信処理実装** (フォアグラウンド/バックグラウンド/終了状態)
3. **通知設定画面実装** (NotificationSettingsScreen.tsx)
4. **Laravelテスト実装** (15テスト: API 6件、Job 7件、設定 2件)
5. **Mobileテスト実装** (20テスト: Service 8件、Hook 6件、UI 6件)
6. **実機テスト** (iOS/Android実機での動作確認)

### 機能拡張候補

- **通知スケジューリング**: 指定時刻に通知送信（例: 毎朝8時にタスクリマインダー）
- **通知グルーピング**: 同種の通知をまとめて表示（例: 「5件の未読タスク」）
- **リッチ通知**: 画像・ボタン付き通知（iOS Rich Notification, Android Custom Layout）
- **開封率分析**: Firebase Analyticsとの統合

---

## 参考資料

### 実装ファイル一覧

| ファイル | 説明 |
|---------|------|
| `/home/ktr/mtdev/app/Services/Fcm/FcmServiceInterface.php` | FCMサービスインターフェース（50行） |
| `/home/ktr/mtdev/app/Services/Fcm/FcmService.php` | FCMサービス実装（280行） |
| `/home/ktr/mtdev/app/Jobs/SendPushNotificationJob.php` | Push通知送信ジョブ（240行） |
| `/home/ktr/mtdev/storage/app/firebase/README.md` | Firebase認証情報セットアップ手順 |
| `/home/ktr/mtdev/config/services.php` | Firebase設定追加 |
| `/home/ktr/mtdev/app/Providers/AppServiceProvider.php` | DIバインディング追加 |
| `/home/ktr/mtdev/.gitignore` | Firebase認証情報除外設定 |

### 関連ドキュメント

| ドキュメント | パス |
|------------|------|
| Push通知機能要件定義書 | `/home/ktr/mtdev/definitions/mobile/PushNotification.md` |
| モバイル開発規則 | `/home/ktr/mtdev/docs/mobile/mobile-rules.md` |
| 実装計画 | `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md` |

### 外部リファレンス

- [Firebase Admin SDK for PHP](https://firebase-php.readthedocs.io/)
- [Firebase Cloud Messaging (FCM)](https://firebase.google.com/docs/cloud-messaging)
- [kreait/firebase-php GitHub](https://github.com/kreait/firebase-php)

---

**作成日**: 2025-12-09  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**バージョン**: 1.0
