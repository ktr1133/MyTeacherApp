# Push通知機能 要件定義書（モバイル）

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: Firebase Cloud Messaging (FCM) 統合、Push通知機能実装 |

---

## 1. 概要

MyTeacher モバイルアプリにおけるPush通知機能（Firebase Cloud Messaging統合）の要件定義です。通知テンプレート（`notification_templates`テーブル）とユーザー通知（`user_notifications`テーブル）に登録されたイベントをリアルタイムでユーザーに配信します。

### 1.1 目的

- **リアルタイム通知**: アプリ起動なしでタスク承認依頼、グループ招待などの重要イベントをユーザーに即座に通知
- **エンゲージメント向上**: 未完了タスクのリマインダー、トークン残高警告などでアプリ利用率を向上
- **ユーザビリティ改善**: 通知タップで該当画面に直接遷移、通知設定のカスタマイズ対応

### 1.2 対象プラットフォーム

| プラットフォーム | 対応バージョン | 備考 |
|----------------|--------------|------|
| **iOS** | iOS 13.0+ | Firebase Cloud Messaging SDK使用 |
| **Android** | Android 6.0+ (API Level 23+) | Firebase Cloud Messaging SDK使用 |

### 1.3 前提条件（Phase 2.B-5 Step 2完了）

- ✅ アプリ内通知機能実装済み（`NotificationListScreen`, `NotificationDetailScreen`）
- ✅ 通知ポーリング実装済み（30秒間隔での未読件数取得）
- ✅ 通知データベーステーブル実装済み（`notification_templates`, `user_notifications`）
- ✅ 通知管理Service・Repository実装済み（`NotificationManagementService`, `NotificationRepository`）

---

## 2. Push通知の送信タイミング

### 2.1 Push通知が必要なイベント

**原則**: `notification_templates` と `user_notifications` テーブルに登録される全イベントでPush通知を送信する。

#### 2.1.1 タスク関連イベント

| イベント | notification_templates.type | 送信タイミング | 受信対象 |
|---------|----------------------------|--------------|---------|
| グループタスク完了申請 | `task_completion_request` | グループタスクの完了ボタン押下時 | タスク作成者 |
| グループタスク完了承認 | `task_approved` | 承認者がタスクを承認した時 | タスク実行者 |
| グループタスク却下 | `task_rejected` | 承認者がタスクを却下した時 | タスク実行者 |
| タスク割当 | `task_assigned` | 他ユーザーにタスクが割り当てられた時 | 割当先ユーザー |
| タスク期限リマインダー | `task_deadline_reminder` | タスク期限24時間前 | タスク所有者 |
| タスクコメント | `task_comment` | タスクにコメントが追加された時 | タスク関係者 |

#### 2.1.2 グループ関連イベント

| イベント | notification_templates.type | 送信タイミング | 受信対象 |
|---------|----------------------------|--------------|---------|
| グループ招待 | `group_invitation` | グループに招待された時 | 招待されたユーザー |
| グループ招待承認 | `group_join_approved` | グループ参加が承認された時 | 申請者 |
| グループ招待却下 | `group_join_rejected` | グループ参加が却下された時 | 申請者 |
| グループ脱退通知 | `group_member_left` | メンバーがグループを脱退した時 | グループマスター |
| グループマスター譲渡 | `group_master_transferred` | マスター権限が譲渡された時 | 新マスター |

#### 2.1.3 トークン関連イベント

| イベント | notification_templates.type | 送信タイミング | 受信対象 |
|---------|----------------------------|--------------|---------|
| トークン残高低下警告 | `token_low_balance` | 残高が閾値（20万トークン）以下になった時 | ユーザー本人 |
| トークン購入完了 | `token_purchased` | トークン購入が完了した時 | ユーザー本人 |
| 月次無料トークン付与 | `monthly_free_tokens_granted` | 毎月1日00:00に無料枠が付与された時 | 全ユーザー |

#### 2.1.4 システム関連イベント

| イベント | notification_templates.type | 送信タイミング | 受信対象 |
|---------|----------------------------|--------------|---------|
| システムメンテナンス通知 | `system_maintenance` | 管理者が作成した時 | 全ユーザーまたは特定グループ |
| 機能アップデート通知 | `system_update` | 管理者が作成した時 | 全ユーザーまたは特定グループ |
| 重要なお知らせ | `system_announcement` | 管理者が作成した時 | 全ユーザーまたは特定グループ |

### 2.2 Push通知が不要なイベント

**該当なし** - 全イベントでPush通知を送信する。

---

## 3. 通知の優先度とPush通知の関係

### 3.1 優先度の定義

`notification_templates.priority` カラムの値:

| 値 | 表示名 | 用途 | Push通知への影響 |
|----|-------|------|-----------------|
| `1` | 高（重要） | 即座の対応が必要な通知 | **影響なし** - 優先度に関わらずPush通知送信 |
| `2` | 中（通常） | 通常の通知 | **影響なし** - 優先度に関わらずPush通知送信 |
| `3` | 低（情報） | 参考情報レベルの通知 | **影響なし** - 優先度に関わらずPush通知送信 |

**重要**: 優先度はアプリ内通知一覧での表示色（赤・黄・灰色）やソート順には影響するが、**Push通知の送信可否には影響しない**。

---

## 4. 通知設定画面の実装範囲

### 4.1 ユーザー制御可能な設定項目

**画面名**: `NotificationSettingsScreen.tsx`

#### 4.1.1 カテゴリ別ON/OFF設定

| カテゴリ | 設定キー | デフォルト | 説明 |
|---------|---------|-----------|------|
| タスク通知 | `push_task_enabled` | ON | タスク関連イベント（完了申請、承認、却下、割当等） |
| グループ通知 | `push_group_enabled` | ON | グループ関連イベント（招待、参加承認等） |
| トークン通知 | `push_token_enabled` | ON | トークン残高警告、購入完了通知 |
| システム通知 | `push_system_enabled` | ON | メンテナンス、アップデート、重要なお知らせ |

#### 4.1.2 詳細設定

| 設定項目 | 設定キー | デフォルト | 説明 |
|---------|---------|-----------|------|
| Push通知全体のON/OFF | `push_enabled` | ON | すべてのPush通知を一括でON/OFF |
| 通知音 | `push_sound_enabled` | ON | Push通知受信時の音ON/OFF |
| バイブレーション | `push_vibration_enabled` | ON | Push通知受信時の振動ON/OFF（Android） |

### 4.2 設定保存先

**テーブル**: `users` テーブルの `notification_settings` カラム（JSON型）

**スキーマ**:
```json
{
  "push_enabled": true,
  "push_task_enabled": true,
  "push_group_enabled": true,
  "push_token_enabled": true,
  "push_system_enabled": true,
  "push_sound_enabled": true,
  "push_vibration_enabled": true
}
```

**API**:
- **取得**: `GET /api/v1/profile/notification-settings` → 現在の設定を取得
- **更新**: `PUT /api/v1/profile/notification-settings` → 設定を更新

---

## 5. FCMトークンの管理方法

### 5.1 トークン登録フロー

```
1. アプリ起動時にFCMトークン取得
2. ローカルストレージの既存トークンと比較
3. トークンが新規 or 変更されている場合
   → バックエンドAPIに送信（POST /api/v1/profile/fcm-token）
4. バックエンド側でuser_device_tokensテーブルに保存
```

### 5.2 デバイストークンテーブル

**テーブル名**: `user_device_tokens`

| カラム | 型 | 制約 | 説明 |
|-------|-----|------|------|
| `id` | BIGINT | PK | ID |
| `user_id` | BIGINT | FK, NOT NULL | ユーザーID |
| `device_token` | VARCHAR(255) | UNIQUE, NOT NULL | FCMトークン |
| `device_type` | ENUM('ios', 'android') | NOT NULL | デバイス種別 |
| `device_name` | VARCHAR(100) | NULLABLE | デバイス名（例: "iPhone 15 Pro"） |
| `app_version` | VARCHAR(20) | NULLABLE | アプリバージョン（例: "1.0.0"） |
| `is_active` | BOOLEAN | DEFAULT TRUE | 有効フラグ |
| `last_used_at` | TIMESTAMP | NULLABLE | 最終使用日時 |
| `created_at` | TIMESTAMP | NOT NULL | 作成日時 |
| `updated_at` | TIMESTAMP | NOT NULL | 更新日時 |

**インデックス**:
- `user_id` + `is_active`（ユーザーごとのアクティブデバイス検索）
- `device_token`（UNIQUE制約、重複登録防止）

### 5.3 マルチデバイス対応

**方針**: 1ユーザーが複数デバイスでログインした場合、**すべてのアクティブデバイスにPush通知を送信**。

**実装**:
```sql
-- ユーザーIDでアクティブなデバイストークンを全取得
SELECT device_token, device_type
FROM user_device_tokens
WHERE user_id = :user_id
  AND is_active = TRUE
  AND last_used_at >= NOW() - INTERVAL '30 days'  -- 30日以上未使用は除外
ORDER BY last_used_at DESC;
```

**トークン無効化**:
- ログアウト時: `is_active = FALSE` に更新
- トークンエラー時（FCM APIから`InvalidRegistration`エラー）: `is_active = FALSE` に更新
- 30日以上未使用: 自動的にPush送信対象外

### 5.4 API仕様

#### 5.4.1 FCMトークン登録API

**エンドポイント**: `POST /api/v1/profile/fcm-token`

**リクエスト**:
```json
{
  "device_token": "eXwZ1234...",
  "device_type": "ios",
  "device_name": "iPhone 15 Pro",
  "app_version": "1.0.0"
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "FCMトークンを登録しました。"
}
```

**処理フロー**:
```
1. Sanctum認証確認
2. device_tokenの重複チェック
   - 既存の場合: last_used_at更新、is_active=TRUE
   - 新規の場合: レコード作成
3. 成功レスポンス返却
```

#### 5.4.2 FCMトークン削除API

**エンドポイント**: `DELETE /api/v1/profile/fcm-token`

**リクエスト**:
```json
{
  "device_token": "eXwZ1234..."
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "FCMトークンを削除しました。"
}
```

**処理フロー**:
```
1. Sanctum認証確認
2. user_id + device_tokenでレコード検索
3. is_active = FALSE に更新
4. 成功レスポンス返却
```

---

## 6. Push通知のペイロード設計

### 6.1 ペイロード構造

**FCM送信データ**:
```json
{
  "notification": {
    "title": "通知タイトル",
    "body": "通知メッセージ"
  },
  "data": {
    "notification_id": "123",
    "type": "task_completion_request",
    "category": "task",
    "priority": "2",
    "action_url": "/tasks/45",
    "created_at": "2025-12-09T10:00:00Z"
  },
  "token": "eXwZ1234..."
}
```

### 6.2 ペイロードフィールド定義

#### 6.2.1 notification部（FCM標準）

| フィールド | 型 | 必須 | 説明 | データソース |
|-----------|-----|------|------|------------|
| `title` | string | ✓ | 通知タイトル | `notification_templates.title` |
| `body` | string | ✓ | 通知メッセージ | `notification_templates.message` |

#### 6.2.2 data部（カスタムペイロード）

| フィールド | 型 | 必須 | 説明 | データソース |
|-----------|-----|------|------|------------|
| `notification_id` | string | ✓ | 通知ID（詳細画面で使用） | `user_notifications.id` |
| `type` | string | ✓ | 通知種別 | `notification_templates.type` |
| `category` | string | ✓ | カテゴリ（task, group, token, system） | 通知種別から判定 |
| `priority` | string | ✓ | 優先度（1, 2, 3） | `notification_templates.priority` |
| `action_url` | string | - | アクションURL（Web版で使用） | `notification_templates.action_url` |
| `created_at` | string | ✓ | 作成日時（ISO 8601形式） | `user_notifications.created_at` |

### 6.3 通知タップ時の画面遷移

**フロー**:
```
1. Push通知タップ
2. アプリ起動（バックグラウンド or 終了状態から復帰）
3. data.notification_id を使用して通知詳細APIを呼び出し
   → GET /api/notifications/{notification_id}
4. 通知詳細画面（NotificationDetailScreen）に遷移
   → 画面遷移と同時に自動既読化
5. 通知詳細画面内で action_url があれば「詳細を見る」ボタン表示
6. ボタンタップ時の処理:
   - モバイル対応画面の場合: ネイティブ画面に遷移（例: `/tasks/45` → TaskDetailScreen）
   - モバイル未対応の場合: WebViewで開く（例: 管理画面URLなど）
```

**実装例（モバイル）**:
```typescript
// NotificationDetailScreen.tsx
const NotificationDetailScreen = ({ route }: any) => {
  const { notification_id } = route.params;
  const [notification, setNotification] = useState<any>(null);

  // 通知詳細取得（自動既読化）
  useEffect(() => {
    const fetchNotification = async () => {
      const data = await notificationService.getNotificationDetail(notification_id);
      setNotification(data);
    };
    fetchNotification();
  }, [notification_id]);

  // アクションボタン押下時
  const handleAction = () => {
    if (!notification?.action_url) return;

    // モバイル対応画面への遷移判定
    if (notification.action_url.startsWith('/tasks/')) {
      const taskId = notification.action_url.split('/')[2];
      navigation.navigate('TaskDetail', { id: taskId });
    } else if (notification.action_url.startsWith('/groups/')) {
      const groupId = notification.action_url.split('/')[2];
      navigation.navigate('GroupDetail', { id: groupId });
    } else {
      // モバイル未対応の場合はWebView
      navigation.navigate('WebView', { url: notification.action_url });
    }
  };

  return (
    <View>
      <Text>{notification?.title}</Text>
      <Text>{notification?.message}</Text>
      {notification?.action_url && (
        <Button title="詳細を見る" onPress={handleAction} />
      )}
    </View>
  );
};
```

**重要な仕様**:
- **Web版との互換性**: `action_url` は Web版の遷移先URLと同一形式（例: `/tasks/45`）
- **モバイル優先**: モバイル対応画面がある場合はネイティブ画面に遷移
- **フォールバック**: モバイル未対応の場合はWebViewで開く
- **自動既読化**: 通知詳細画面表示時に `is_read = TRUE` に更新（既存仕様を継承）

---

## 7. オフライン時の通知処理

### 7.1 基本方針

**FCMの標準動作を活用**: Firebase Cloud Messagingは自動的にオフライン時の通知を保持し、オンライン復帰時に配信する。

### 7.2 処理フロー

#### 7.2.1 オフライン中の通知送信

```
1. バックエンドがFCM APIにPush通知を送信
2. FCMサーバーがデバイスの接続状態を確認
   - オンライン: 即座に配信
   - オフライン: FCMサーバーで最大4週間保持
3. ユーザーがオンライン復帰
4. FCMサーバーから自動的にPush通知を配信
```

**FCMの保持期間**: 最大4週間（デフォルト）

#### 7.2.2 アプリ起動時の未読通知同期

```
1. アプリ起動（オンライン復帰）
2. 未読通知件数APIを自動呼び出し（既存のポーリング処理）
   → GET /api/notifications/unread-count
3. 通知一覧画面で未読バッジを更新
4. ユーザーが通知一覧画面を開いた時点で最新の通知を表示
```

**重要**: Push通知とアプリ内通知は**別々に管理**される。
- Push通知: オフライン中に送信されたものは復帰時に受信
- アプリ内通知: アプリ起動時のAPIリクエストで最新状態を取得

### 7.3 重複通知の防止

**問題**: オフライン中にPush通知を受信し、オンライン復帰後にアプリ内通知でも同じ通知を表示すると重複する。

**対策**: 通知IDベースの重複チェック

```typescript
// useNotifications.ts
const handlePushNotificationReceived = (remoteMessage: any) => {
  const notificationId = remoteMessage.data.notification_id;
  
  // ローカルストレージで既読チェック
  const isAlreadyRead = await storage.getItem(`notification_read_${notificationId}`);
  
  if (!isAlreadyRead) {
    // 未読の場合のみ通知表示（フォアグラウンド時）
    Alert.alert(remoteMessage.notification.title, remoteMessage.notification.body);
  }
};
```

---

## 8. テスト方針

### 8.1 単体テスト（Laravel）

#### 8.1.1 FCMトークン管理API

**テストファイル**: `tests/Feature/Api/Profile/FcmTokenApiTest.php`

**テストケース**:
1. ✅ FCMトークンを登録できること
2. ✅ 同じトークンを再登録した場合は更新されること（last_used_at更新）
3. ✅ 異なるユーザーが同じトークンを登録しようとすると失敗すること
4. ✅ FCMトークンを削除できること（is_active = FALSE）
5. ✅ 未認証の場合は401エラー
6. ✅ device_type不正値は400エラー

#### 8.1.2 通知設定API

**テストファイル**: `tests/Feature/Api/Profile/NotificationSettingsApiTest.php`

**テストケース**:
1. ✅ 通知設定を取得できること
2. ✅ 通知設定を更新できること
3. ✅ カテゴリ別にPush通知ON/OFFを設定できること
4. ✅ 不正な設定キーは400エラー
5. ✅ 未認証の場合は401エラー

#### 8.1.3 Push通知送信処理

**テストファイル**: `tests/Feature/Jobs/SendPushNotificationJobTest.php`

**テストケース**:
1. ✅ アクティブなデバイスにPush通知が送信されること
2. ✅ 非アクティブなデバイスには送信されないこと
3. ✅ 30日以上未使用のデバイスには送信されないこと
4. ✅ 通知設定でカテゴリがOFFの場合は送信されないこと
5. ✅ push_enabled=FALSEの場合は送信されないこと
6. ✅ FCM APIエラー時にリトライ処理が実行されること
7. ✅ InvalidRegistrationエラー時にis_active=FALSEに更新されること

### 8.2 統合テスト（モバイル）

#### 8.2.1 FCMトークン登録

**テストファイル**: `mobile/__tests__/services/notification.service.test.ts`

**テストケース**:
1. ✅ アプリ起動時にFCMトークンを取得してAPIに送信すること
2. ✅ トークンが変更された場合に再送信すること
3. ✅ トークンが同じ場合は送信しないこと
4. ✅ 権限拒否時にエラーハンドリングが動作すること

#### 8.2.2 Push通知受信

**テストファイル**: `mobile/__tests__/hooks/usePushNotifications.test.ts`

**テストケース**:
1. ✅ フォアグラウンド時にPush通知を受信して表示すること
2. ✅ バックグラウンド時にPush通知を受信すること
3. ✅ アプリ終了時にPush通知を受信すること
4. ✅ 通知タップ時に通知詳細画面に遷移すること
5. ✅ action_urlがある場合に適切な画面に遷移すること

#### 8.2.3 通知設定画面

**テストファイル**: `mobile/__tests__/screens/NotificationSettingsScreen.test.tsx`

**テストケース**:
1. ✅ 通知設定を取得して表示すること
2. ✅ カテゴリ別ON/OFFスイッチが動作すること
3. ✅ 通知設定を更新できること
4. ✅ 全体のON/OFFスイッチが各カテゴリに連動すること

### 8.3 手動テスト（実機確認）

#### 8.3.1 iOS実機テスト

**環境**: iPhone実機（TestFlight経由）

**確認項目**:
1. ✅ Push通知権限リクエストが表示されること
2. ✅ フォアグラウンド時にバナー通知が表示されること
3. ✅ バックグラウンド時に通知センターに表示されること
4. ✅ ロック画面で通知が表示されること
5. ✅ 通知タップで該当画面に遷移すること
6. ✅ サウンド・バイブレーション設定が反映されること

#### 8.3.2 Android実機テスト

**環境**: Android実機（内部テスト）

**確認項目**:
1. ✅ Push通知権限リクエストが表示されること（Android 13+）
2. ✅ フォアグラウンド時にトースト通知が表示されること
3. ✅ バックグラウンド時に通知バーに表示されること
4. ✅ ロック画面で通知が表示されること
5. ✅ 通知タップで該当画面に遷移すること
6. ✅ サウンド・バイブレーション設定が反映されること

### 8.4 テストカバレッジ目標

| レイヤー | 目標カバレッジ | 備考 |
|---------|--------------|------|
| Laravel API | 90%以上 | FCMトークン管理・通知設定・Push送信ジョブ |
| モバイル Service | 90%以上 | notification.service.ts, fcmToken.service.ts |
| モバイル Hook | 80%以上 | usePushNotifications.ts, useNotifications.ts |
| モバイル UI | 70%以上 | NotificationSettingsScreen.tsx |

---

## 9. セキュリティ要件

### 9.1 FCMトークンの保護

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **トークン暗号化** | 不要（FCMトークン自体は公開情報） | - |
| **トークン所有者確認** | 必須 | Sanctum認証で`user_id`検証 |
| **トークン削除** | ログアウト時に必須 | `DELETE /api/v1/profile/fcm-token` |
| **不正トークン検出** | FCM APIエラー時に自動無効化 | `is_active = FALSE` 更新 |

### 9.2 通知データの保護

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **個人情報保護** | Push通知に機密情報を含めない | titleとbodyはジェネリックなメッセージ |
| **通知権限チェック** | 送信前にユーザーの通知設定を確認 | `notification_settings`カラムをチェック |
| **データ整合性** | 通知データは`notification_templates`と同期 | データソースを統一 |

### 9.3 FCM APIキーの管理

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **キー保護** | 環境変数で管理 | `.env` の `FIREBASE_SERVER_KEY` |
| **キー暗号化** | Git管理外 | `.gitignore` に追加 |
| **キーローテーション** | 年1回推奨 | Firebase Console経由で実施 |

---

## 10. パフォーマンス要件

### 10.1 応答時間目標

| エンドポイント | 目標 | 最大 | 備考 |
|--------------|------|------|------|
| FCMトークン登録 | < 100ms | < 300ms | 単純なINSERT/UPDATE |
| 通知設定取得 | < 50ms | < 100ms | JSON取得のみ |
| 通知設定更新 | < 100ms | < 300ms | JSON更新のみ |
| Push通知送信（バックグラウンド） | - | - | 非同期ジョブで実行 |

### 10.2 最適化施策

#### 10.2.1 非同期ジョブによるPush送信

**方針**: タスク完了申請などのトリガーイベント発生時、Push通知送信は非同期ジョブ（Queue）で実行する。

**実装**:
```php
// app/Jobs/SendPushNotificationJob.php
class SendPushNotificationJob implements ShouldQueue
{
    public function __construct(
        private int $notificationId
    ) {}
    
    public function handle(FcmServiceInterface $fcmService): void
    {
        // 通知データ取得
        $notification = UserNotification::find($this->notificationId);
        
        // 通知設定チェック
        if (!$this->shouldSendPushNotification($notification)) {
            return;
        }
        
        // デバイストークン取得（アクティブなもののみ）
        $deviceTokens = $this->getActiveDeviceTokens($notification->user_id);
        
        // FCM送信
        foreach ($deviceTokens as $token) {
            $fcmService->send($token, $this->buildPayload($notification));
        }
    }
}
```

**トリガー例**:
```php
// app/Services/Task/TaskApprovalService.php
public function requestApproval(Task $task): void
{
    // 通知レコード作成
    $notification = $this->notificationRepository->create([...]);
    
    // Push通知ジョブをディスパッチ（非同期）
    SendPushNotificationJob::dispatch($notification->id);
}
```

#### 10.2.2 バッチ送信の実装

**方針**: 複数ユーザーへの同時通知（例: システムメンテナンス通知）はFCM Multicast APIを使用してバッチ送信。

**実装**:
```php
// app/Services/Notification/FcmService.php
public function sendMulticast(array $deviceTokens, array $payload): void
{
    // 最大500件ずつに分割（FCM制限）
    $chunks = array_chunk($deviceTokens, 500);
    
    foreach ($chunks as $chunk) {
        $response = Http::post('https://fcm.googleapis.com/fcm/send', [
            'registration_ids' => $chunk,
            'notification' => $payload['notification'],
            'data' => $payload['data'],
        ]);
        
        // エラーハンドリング
        $this->handleFcmResponse($response, $chunk);
    }
}
```

#### 10.2.3 デバイストークンのキャッシュ

**方針**: ユーザーごとのアクティブデバイスリストを5分間キャッシュ。

**実装**:
```php
// app/Repositories/DeviceToken/DeviceTokenRepository.php
public function getActiveTokens(int $userId): array
{
    return Cache::remember("user_devices:{$userId}", 300, function () use ($userId) {
        return UserDeviceToken::where('user_id', $userId)
            ->where('is_active', true)
            ->where('last_used_at', '>=', now()->subDays(30))
            ->pluck('device_token')
            ->toArray();
    });
}
```

---

## 11. エラーハンドリング

### 11.1 FCM APIエラー対応

| エラー種別 | FCMエラーコード | 対処方法 |
|-----------|----------------|---------|
| **トークン無効** | `InvalidRegistration` | `is_active = FALSE` に更新 |
| **トークン未登録** | `NotRegistered` | `is_active = FALSE` に更新 |
| **送信失敗（一時的）** | `Unavailable` | 3回までリトライ（指数バックオフ） |
| **認証エラー** | `Authentication Error` | ログ記録、管理者通知 |
| **ペイロード過大** | `MessageTooBig` | ログ記録、通知内容を短縮 |

### 11.2 リトライ戦略

**Laravelのキューリトライ機能を使用**:

```php
// app/Jobs/SendPushNotificationJob.php
class SendPushNotificationJob implements ShouldQueue
{
    public $tries = 3;             // 最大3回リトライ
    public $backoff = [60, 300];   // 1分、5分後にリトライ
    
    public function failed(\Throwable $exception): void
    {
        // 失敗時のログ記録
        Log::error('Push notification failed', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### 11.3 権限エラーハンドリング（モバイル）

**iOS/Android共通**:

```typescript
// usePushNotifications.ts
const requestPermission = async () => {
  try {
    const authStatus = await messaging().requestPermission();
    
    if (authStatus === messaging.AuthorizationStatus.DENIED) {
      // 権限拒否時の処理
      Alert.alert(
        '通知権限が必要です',
        'タスクの更新や承認依頼をリアルタイムで受け取るには、通知権限を有効にしてください。',
        [
          { text: 'キャンセル', style: 'cancel' },
          { text: '設定を開く', onPress: () => Linking.openSettings() },
        ]
      );
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Permission request failed:', error);
    return false;
  }
};
```

---

## 12. 実装スケジュール

### 12.1 開発フェーズ（Phase 2.B-7.5、1週間）

| ステップ | タスク | 工数 | 担当 |
|---------|-------|------|------|
| **Step 1: 環境構築** | Firebase Console設定、iOS/Android FCM SDK導入 | 0.5日 | モバイル開発 |
| **Step 2: バックエンドAPI** | FCMトークン管理API、通知設定API、Push送信ジョブ実装 | 1.5日 | バックエンド開発 |
| **Step 3: モバイル実装** | FCMトークン登録、Push通知受信、通知設定画面 | 2日 | モバイル開発 |
| **Step 4: テスト** | 単体テスト、統合テスト、実機テスト | 1日 | 全員 |
| **Step 5: デバッグ・調整** | 不具合修正、パフォーマンス調整 | 0.5日 | 全員 |

**合計**: 5日（1週間）

### 12.2 実装優先順位

1. **最優先**: FCMトークン登録・管理（デバイス認識の基盤）
2. **高優先**: Push通知受信・画面遷移（コア機能）
3. **中優先**: 通知設定画面（ユーザーコントロール）
4. **低優先**: バッチ送信・キャッシュ最適化（パフォーマンス改善）

---

## 13. 参考資料

### 13.1 関連ドキュメント

| ドキュメント | パス | 説明 |
|------------|------|------|
| 通知機能要件定義（Web/モバイル基本） | `/home/ktr/mtdev/definitions/Notification.md` | アプリ内通知の基本仕様 |
| モバイル開発規則 | `/home/ktr/mtdev/docs/mobile/mobile-rules.md` | コーディング規約・総則 |
| プロジェクト全体規則 | `/home/ktr/mtdev/.github/copilot-instructions.md` | 不具合対応・コミット規則 |
| 実装計画 | `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md` | Phase 2.B-7.5実装計画 |

### 13.2 外部リファレンス

| リソース | URL | 説明 |
|---------|-----|------|
| Firebase Cloud Messaging（FCM） | https://firebase.google.com/docs/cloud-messaging | FCM公式ドキュメント |
| React Native Firebase | https://rnfirebase.io/ | React Native用Firebase SDK |
| FCM HTTP v1 API | https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages | FCM REST API仕様 |

### 13.3 データベーススキーマ

**既存テーブル**:
- `notification_templates`: 通知マスターデータ（`/home/ktr/mtdev/database/migrations/2025_01_16_000001_create_notification_templates_table.php`）
- `user_notifications`: ユーザー通知中間テーブル（`/home/ktr/mtdev/database/migrations/2025_01_16_000002_create_user_notifications_table.php`）

**新規テーブル**:
- `user_device_tokens`: FCMトークン管理（マイグレーション作成必要）

---

## 14. 今後の拡張予定

### 14.1 Phase 2.B-8以降

- **通知スケジューリング**: 特定の時刻に通知を送信（例: 毎朝8時にタスクリマインダー）
- **通知グルーピング**: 同種の通知をまとめて表示（例: 「5件の未読タスク」）
- **通知優先度の活用**: 高優先度通知は音付き、低優先度は無音
- **リッチ通知**: 画像・ボタン付き通知（iOS Rich Notification, Android Custom Layout）

### 14.2 分析・改善

- **開封率分析**: Push通知の開封率をFirebase Analyticsで追跡
- **A/Bテスト**: 通知メッセージの文面をテストして最適化
- **ユーザーフィードバック**: 通知頻度・内容の満足度調査

---

**作成日**: 2025-12-09  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**バージョン**: 1.0
