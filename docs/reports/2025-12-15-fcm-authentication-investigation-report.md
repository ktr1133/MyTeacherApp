# Firebase Cloud Messaging 認証エラー調査レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-15 | GitHub Copilot | 初版作成: FCM認証エラーの徹底調査 |

## 概要

Firebase Cloud Messaging (FCM) のPush通知送信で **401 THIRD_PARTY_AUTH_ERROR** が発生。約3時間にわたる調査の結果、**サービスアカウント自体には権限が付与されているが、実際のFCM API呼び出しで認証エラーが発生する**という矛盾した状況を確認。

## 調査経緯

### 1. 初期仮説: IAM権限伝播遅延

**仮説**: 新しいサービスアカウントキー (3cd76903e3) の生成後、IAM権限の伝播に時間がかかっている。

**検証結果**:
- ✅ OAuth 2.0 トークン生成: 成功
- ✅ Google Token Info API: トークン有効性確認済み
- ✅ testIamPermissions API: `cloudmessaging.messages.create` 権限付与確認
- ❌ FCM API: 401 UNAUTHENTICATED エラー

**結論**: 60分以上経過しても状況変わらず。伝播遅延ではない可能性が高い。

### 2. WSL環境固有の問題

**別のAIからの提案**:
1. ファイルパス指定ミス (WSL vs Windows)
2. 環境変数の設定漏れ
3. JSONファイルの種類間違い
4. ファイル権限の問題

**検証結果**:
- ✅ ファイルパス: WSL形式、正しい
- ✅ ファイル権限: 0644、読み取り可能
- ✅ JSON形式: 有効なサービスアカウントキー (`type: service_account`)
- ✅ 環境変数: 未設定 (コード内で明示指定)
- ✅ Firebase SDK: Factory作成・Messaging作成成功

**結論**: WSL環境固有の問題ではない。

### 3. サービスアカウントキーの問題

**仮説**: 新しいサービスアカウントキーに問題がある。

**検証結果**:
- 新しいキー (3cd76903e3): ❌ 401エラー
- 古いキー (617e9130c4): ❌ 401エラー（同じエラー）

**結論**: キーの問題ではない。サービスアカウント自体の設定に問題がある。

### 4. Firebase Admin SDK 初期化方法

**仮説**: SDK初期化方法に問題がある。

**検証結果** (5パターンテスト):
1. 環境変数経由: ❌
2. JSON文字列直接: ❌
3. 配列渡し: ❌
4. プロジェクトID明示: ❌
5. デフォルト認証: ❌

**結論**: すべて失敗。初期化方法の問題ではない。

## 矛盾する検証結果

### ✅ 成功している項目

| 項目 | ステータス | 証拠 |
|-----|-----------|------|
| OAuth 2.0 トークン生成 | ✅ 成功 | Google Auth Library でトークン取得成功 |
| トークン有効性 | ✅ 有効 | Google Token Info API で検証済み |
| IAM権限 | ✅ 付与済み | testIamPermissions API で確認: `cloudmessaging.messages.create` |
| サービスアカウントキー | ✅ 正しい | `type: service_account`、すべての必須キー存在 |
| Firebase SDK初期化 | ✅ 成功 | Factory作成、Messaging作成成功 |

### ❌ 失敗している項目

| 項目 | ステータス | エラー |
|-----|-----------|--------|
| FCM API呼び出し | ❌ 失敗 | 401 UNAUTHENTICATED |
| エラーコード | - | THIRD_PARTY_AUTH_ERROR |
| エラーメッセージ | - | "Request is missing required authentication credential" |

## 現在の結論

### 問題の所在

**IAM権限（cloudmessaging.messages.create）は付与されているが、Firebase特有の設定が不足している可能性**

### 考えられる原因

1. **Firebase Admin SDKサービスアカウントの設定不足**
   - Firebase Console > プロジェクト設定 > サービスアカウント での特別な設定が必要
   - サービスアカウント `firebase-adminsdk-fbsvc@my-teacher-bcb8d.iam.gserviceaccount.com` がFirebaseプロジェクトに正しく関連付けられていない可能性

2. **Firebase Cloud Messaging APIの有効化不足**
   - APIは有効化されているが、サービスアカウントとの紐付けが不完全

3. **プロジェクトレベルとFirebaseレベルの権限の乖離**
   - GCP IAMでは権限付与されている
   - しかしFirebase固有の権限設定が別途必要

## 次のアクション（推奨順）

### 1. Firebase Console でのサービスアカウント確認（最優先）

```
https://console.firebase.google.com/project/my-teacher-bcb8d/settings/serviceaccounts/adminsdk
```

確認事項:
- [ ] サービスアカウント `firebase-adminsdk-fbsvc@...` がリストに表示されているか
- [ ] 「新しい秘密鍵の生成」ボタンの下に表示されるサービスアカウントメールアドレスが一致しているか
- [ ] Firebase Admin SDK のスニペットが表示されているか

### 2. サービスアカウントの再生成

現在のサービスアカウントに問題がある可能性があるため、Firebaseコンソールから新しいサービスアカウントを生成:

1. Firebase Console → プロジェクト設定 → サービスアカウント
2. 「新しい秘密鍵の生成」をクリック
3. 生成されたJSONをダウンロード
4. credentials.jsonを更新
5. 即座にテスト実行

### 3. Firebase Admin SDK の初期化スニペット確認

Firebase Consoleの「サービスアカウント」タブに表示されるコードスニペットと、現在のコードを比較:

```javascript
// Firebase Console に表示されるスニペット
var admin = require("firebase-admin");

var serviceAccount = require("path/to/serviceAccountKey.json");

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});
```

PHPの場合、これと等価な処理をしているか確認。

### 4. Google Cloud Console でのサービスアカウント確認

```
https://console.cloud.google.com/iam-admin/serviceaccounts?project=my-teacher-bcb8d
```

確認事項:
- [ ] サービスアカウント `firebase-adminsdk-fbsvc@...` が存在するか
- [ ] 「有効」状態になっているか
- [ ] キーが2つ存在するか（617e... と 3cd76...）

### 5. Firebase Support への問い合わせ

上記をすべて試しても解決しない場合、Firebase Supportに以下の情報を提供:

- プロジェクトID: `my-teacher-bcb8d`
- サービスアカウント: `firebase-adminsdk-fbsvc@my-teacher-bcb8d.iam.gserviceaccount.com`
- エラーコード: `THIRD_PARTY_AUTH_ERROR`
- 検証済み事項: IAM権限付与済み、OAuth トークン生成成功、testIamPermissions 成功

## 作成したデバッグスクリプト

| ファイル名 | 目的 | 結果 |
|-----------|------|------|
| `test-google-auth.php` | OAuth 2.0 トークン生成確認 | ✅ 成功 |
| `test-fcm-direct-http.php` | 直接HTTP v1 API呼び出し | ❌ 401エラー |
| `test-fcm-verbose.php` | Firebase SDK詳細ログ | ❌ 401エラー |
| `test-wsl-file-validation.php` | WSL環境ファイル検証 | ✅ すべて正常 |
| `test-fcm-token-debug.php` | OAuth トークン検証 + FCM送信 | ❌ 401エラー |
| `test-iam-permissions.php` | IAM権限直接確認 | ✅ 権限付与確認 |
| `test-firebase-alternative-init.php` | SDK初期化5パターン | ❌ すべて失敗 |

## 技術詳細

### エラーレスポンス

```json
{
  "error": {
    "code": 401,
    "message": "Request is missing required authentication credential...",
    "status": "UNAUTHENTICATED",
    "details": [
      {
        "@type": "type.googleapis.com/google.firebase.fcm.v1.FcmError",
        "errorCode": "THIRD_PARTY_AUTH_ERROR"
      }
    ]
  }
}
```

### OAuth 2.0 トークン情報

```
Access Token: ya29.c.c0AYnqXl...（152文字）
Scope: https://www.googleapis.com/auth/firebase.messaging
Expires: 3599秒
Audience: 104628361926821783793
```

### IAM権限（testIamPermissions 結果）

```
✅ cloudmessaging.messages.create
✅ firebase.projects.update
✅ firebasenotifications.messages.create
```

### サービスアカウント情報

```
Email: firebase-adminsdk-fbsvc@my-teacher-bcb8d.iam.gserviceaccount.com
Project ID: my-teacher-bcb8d
Private Key ID (new): 3cd76903e39d0ed496a1f626740cd355c583a3e2
Private Key ID (old): 617e9130c4fbc1e79ea38eacf4ffb90f03c7d713
```

### 付与されているIAM役割

```
roles/firebase.admin
roles/firebase.growthAdmin
roles/firebase.sdkAdminServiceAgent
roles/firebasecloudmessaging.admin
roles/firebaseinappmessaging.admin
roles/firebasemessagingcampaigns.admin
roles/firebasenotifications.admin
roles/firebasenotifications.viewer
roles/iam.serviceAccountTokenCreator
```

## 参考資料

- [Firebase Admin SDK Setup](https://firebase.google.com/docs/admin/setup)
- [FCM Server Implementation](https://firebase.google.com/docs/cloud-messaging/server)
- [Service Account Best Practices](https://cloud.google.com/iam/docs/best-practices-for-managing-service-account-keys)
- [IAM Permissions Reference](https://cloud.google.com/iam/docs/permissions-reference)

## まとめ

3時間の調査により、技術的な設定はすべて正しいことを確認。しかし実際のFCM API呼び出しで認証エラーが発生する矛盾した状況。

**最も可能性が高い原因**: Firebase Console上でのサービスアカウント設定が不完全。

**次の手順**: Firebase Console > プロジェクト設定 > サービスアカウント で、現在のサービスアカウントの状態を確認し、必要であれば新しいサービスアカウントキーを再生成する。
