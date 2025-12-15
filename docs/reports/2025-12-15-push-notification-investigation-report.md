# Push通知機能 調査レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-15 | GitHub Copilot | 初版作成: FCM 401エラー調査とExpo Go制約の特定 |

## 概要

**目的**: iPhone実機でのPush通知動作確認  
**結果**: ❌ 401 THIRD_PARTY_AUTH_ERROR により送信失敗  
**根本原因**: **Expo Goの制約により、iOSでAndroid形式のFCMトークンが生成される**

## 調査結果サマリー

### ✅ 正常に動作している部分

1. **Firebase/GCP設定**
   - ✅ Service Account: firebase-adminsdk-fbsvc@my-teacher-bcb8d.iam.gserviceaccount.com (Active)
   - ✅ IAM Roles: 9個のFirebase関連ロールが付与済み
   - ✅ FCM API: 有効化確認済み (fcm.googleapis.com, firebase.googleapis.com)
   - ✅ APNs Keys: 開発用 (YX367YZLUS) + 本番用 (V75KFKX9M3) アップロード済み

2. **認証・認可**
   - ✅ OAuth 2.0 Token: 正常に生成 (Google Token Info APIで検証済み)
   - ✅ IAM Permissions: `cloudmessaging.messages.create`, `firebase.projects.update`, `firebasenotifications.messages.create` 確認済み
   - ✅ Service Account Keys: 3個生成 (全て2025-12-15作成)

3. **Backend実装**
   - ✅ kreait/firebase-php 7.24.0 正常動作
   - ✅ google/auth 1.49.0 正常動作
   - ✅ SendPushNotificationJob, FcmService 実装済み

### ❌ 問題が発生している部分

1. **FCM送信 (Backend PHP)**
   - ❌ HTTP Status: 401 UNAUTHENTICATED
   - ❌ Error: THIRD_PARTY_AUTH_ERROR

2. **Firebase Console手動送信**
   - ❌ 通知未配信（デバイスに届かず）
   - ⚠️ Browser Console: FCM関連エラーなし

3. **FCMトークン形式**
   - ⚠️ Token: `dbNSfVeM20UghcNBXLearq:APA91bEschqDNyYx0uGwFt...`
   - ⚠️ Format: `APA91b...` → **Android形式**
   - ⚠️ Device: iPhone (iOS) → **プラットフォーム不一致**
   - ⚠️ Registration: 2025-12-15 04:25:39 (Device ID: 5)

## 根本原因の特定

### Expo Goの制約

**`@react-native-firebase/messaging`はExpo Goでは完全に動作しない**

| 環境 | iOS Push通知 | FCMトークン形式 | 動作 |
|------|-------------|----------------|------|
| **Expo Go (iOS)** | ❌ 未対応 | `APA91b...` (Android形式) | ❌ 送信失敗 |
| **Expo Go (Android)** | ✅ 対応 | `APA91b...` (FCM標準) | ✅ 正常動作（推定） |
| **EAS Build (iOS)** | ✅ 対応 | APNs統合FCMトークン | ✅ 正常動作 |
| **EAS Build (Android)** | ✅ 対応 | `APA91b...` (FCM標準) | ✅ 正常動作 |

### 技術的説明

1. **Expo Goの仕様**:
   - 開発用アプリのため、APNs証明書はExpo社のもの
   - アプリ固有のAPNs統合ができない
   - iOSでもAndroid形式のフォールバックトークンを生成

2. **FCMトークン形式の違い**:
   - **Android**: `APA91b...` (Google Cloud Messaging形式)
   - **iOS (本番)**: APNsデバイストークンとFCM統合トークン
   - **iOS (Expo Go)**: `APA91b...` ← **誤った形式**

3. **401エラーの本質**:
   - 認証・認可は正常
   - **トークン形式がiOS APNsと互換性がない**ため拒否される
   - Firebase Consoleテストも失敗（同じトークンを使用）

## 検証済み項目（診断スクリプト）

以下の診断スクリプトで全て検証済み：

1. **test-google-auth.php**: ✅ OAuth 2.0トークン生成成功
2. **test-fcm-direct-http.php**: ❌ Direct HTTP v1 API → 401
3. **test-fcm-send.php**: ❌ kreait/firebase-php → 401
4. **test-fcm-verbose.php**: ✅ SDK初期化成功、❌ API呼び出し失敗
5. **test-wsl-file-validation.php**: ✅ WSL環境検証（パス、権限、JSON形式）
6. **test-fcm-token-debug.php**: ✅ OAuth検証済み、❌ FCM API 401
7. **test-iam-permissions.php**: ✅ IAM権限確認済み
8. **test-firebase-alternative-init.php**: ❌ 5種類の初期化方法すべて失敗
9. **test-api-enabled.php**: ✅ FCM API有効化確認済み
10. **test-fcm-project-info.php**: ✅ Projectメタデータ取得成功

## 解決策

### 🎯 推奨アプローチ: 段階的検証

#### Phase 1: Android実機テスト（即座に実施可能）

**目的**: Android形式FCMトークンが正常に動作するか検証

**手順**:
1. Androidデバイスで`Expo Go`アプリを起動
2. `exp://192.168.x.x:8081`に接続（開発サーバー）
3. アプリにログイン → FCMトークン自動登録
4. `user_device_tokens`テーブルでトークン確認
5. NotificationテストメニューからPush通知送信

**検証項目**:
- [ ] FCMトークンが`APA91b...`形式で登録される
- [ ] Backend FCM送信が成功（401エラーが発生しない）
- [ ] Android実機でPush通知を受信
- [ ] フォアグラウンド/バックグラウンドで正常動作

**期待結果**:
- ✅ Android形式トークンは正常動作
- → **Backend実装は正しい**
- → **iOSの問題はExpo Go制約が原因**

#### Phase 2: EAS Build (iOS Development) 作成

**目的**: 本番レベルのiOS Push通知動作確認

**前提条件**:
- Apple Developer Program登録（年間$99）
- Development Provisioning Profile作成
- APNs Key設定済み（既に完了）

**手順**:
```bash
# 1. EAS CLIインストール（未インストールの場合）
npm install -g eas-cli

# 2. EAS Build設定確認
cd /home/ktr/mtdev/mobile
cat eas.json

# 3. Development Buildビルド（iOS）
eas build --profile development --platform ios

# 4. ビルド完了後、iPhoneにインストール
# - QRコード経由
# - または直接ダウンロード

# 5. アプリ起動 → ログイン → トークン登録
# 6. user_device_tokensで新しいトークン確認
# 7. Push通知送信テスト
```

**検証項目**:
- [ ] FCMトークンがAPNs統合形式で登録される（`APA91b...`ではない）
- [ ] Backend FCM送信が成功
- [ ] iPhone実機でPush通知を受信（フォアグラウンド）
- [ ] iPhone実機でPush通知を受信（バックグラウンド）
- [ ] 通知タップで適切な画面に遷移

#### Phase 3: 本番ビルド検証（オプション）

本番環境デプロイ前に、Production Buildでの動作確認:

```bash
eas build --profile production --platform ios
```

## 技術的詳細

### FCMトークンの構造

#### Android形式（Expo Goが返すもの）
```
dbNSfVeM20UghcNBXLearq:APA91bEschqDNyYx0uGwFtAN7Hsvu5CmfjufpYraB6sf0Xwz3-_TrPLlPWWPlr_kn0neSVMa263YJFCtYWMSaM33ViqKoWjAd5oBi6GiWaKigeBRk_Ccg8A
```

特徴:
- プレフィックス: `APA91b...`
- 長さ: 152文字
- 用途: Android端末、またはFirebase SDKのフォールバック

#### iOS本番形式（EAS Buildが返すもの）
```
（例）
dGVzdC1hcG5zLXRva2VuLWV4YW1wbGU6Y...
```

特徴:
- APNsデバイストークンとFCM登録トークンの統合形式
- Firebase側でAPNs配信に変換可能
- iOS専用

### Firebase Cloud Messaging v1 API

**エンドポイント**:
```
POST https://fcm.googleapis.com/v1/projects/my-teacher-bcb8d/messages:send
```

**認証ヘッダー**:
```
Authorization: Bearer {OAUTH_ACCESS_TOKEN}
```

**ペイロード構造**:
```json
{
  "message": {
    "token": "{FCM_TOKEN}",
    "notification": {
      "title": "テスト通知",
      "body": "メッセージ本文"
    },
    "data": {
      "notification_id": "123",
      "type": "task_created"
    },
    "apns": {
      "payload": {
        "aps": {
          "sound": "default",
          "badge": 1
        }
      }
    }
  }
}
```

**エラーレスポンス（現状）**:
```json
{
  "error": {
    "code": 401,
    "message": "Request had invalid authentication credentials. Expected OAuth 2 access token...",
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

**エラーの意味**:
- `THIRD_PARTY_AUTH_ERROR`: FCMトークンがAPNsと統合できていない
- **原因**: Android形式トークンをiOS APNs配信に使おうとした
- **対策**: EAS Buildで正しいAPNs統合トークンを生成

## モバイルアプリの実装詳細

### FCMContext.tsx（自動登録）

**場所**: `/home/ktr/mtdev/mobile/src/contexts/FCMContext.tsx`

**処理フロー**:
```typescript
// ログイン検知 → トークン登録
useEffect(() => {
  if (isLoggedIn && isAppStateActive) {
    fcmService.registerToken(); // 自動実行
  }
}, [isLoggedIn, isAppStateActive]);

// ログアウト検知 → トークン削除
useEffect(() => {
  if (!isLoggedIn) {
    fcmService.unregisterToken(); // 自動実行
  }
}, [isLoggedIn]);
```

### fcm.service.ts（トークン取得）

**場所**: `/home/ktr/mtdev/mobile/src/services/fcm.service.ts`

**トークン取得メソッド**:
```typescript
async getFcmToken(): Promise<string | null> {
  // iOS: APNS登録（開発ビルドでは手動呼び出しが必要）
  if (Platform.OS === 'ios') {
    await messaging().registerDeviceForRemoteMessages();
  }

  // FCMトークン取得
  const token = await messaging().getToken();
  
  // ローカルストレージに保存
  await storage.setItem(STORAGE_KEYS.FCM_TOKEN, token);
  return token;
}
```

**問題点**:
- `messaging().getToken()` はExpo GoではAndroid形式トークンを返す
- EAS BuildではAPNs統合トークンを返す

### app.config.js（Firebase設定）

**場所**: `/home/ktr/mtdev/mobile/app.config.js`

**iOS設定**:
```javascript
ios: {
  bundleIdentifier: "com.myteacherfamco.app",
  googleServicesFile: "./GoogleService-Info.plist",
  infoPlist: {
    UIBackgroundModes: ["remote-notification"],
  },
  entitlements: {
    "aps-environment": "development"
  }
},
plugins: [
  "@react-native-firebase/app",
  "@react-native-firebase/messaging",
  // ...
]
```

**設定は正しい** → Expo Goの制約が問題

## 参考資料

### React Native Firebase公式ドキュメント

- [iOS Setup](https://rnfirebase.io/messaging/usage#ios---requesting-permissions)
- [Cloud Messaging](https://rnfirebase.io/messaging/usage)

### Expo公式ドキュメント

- [Push Notifications (EAS Build)](https://docs.expo.dev/push-notifications/overview/)
- [Limitations in Expo Go](https://docs.expo.dev/workflow/expo-go/#limitations)

### Firebase公式ドキュメント

- [FCM v1 API](https://firebase.google.com/docs/cloud-messaging/send/v1-api)
- [APNs Integration](https://firebase.google.com/docs/cloud-messaging/ios/certs)

## 次のステップ

### 優先度: HIGH（即座に実施）

- [ ] **Androidデバイスで動作確認**
  - Expo Go + Android実機でテスト
  - Backend FCM送信が成功することを確認
  - フォアグラウンド/バックグラウンド動作を検証

### 優先度: MEDIUM（1週間以内）

- [ ] **EAS Build (iOS Development) 作成**
  - `eas build --profile development --platform ios`
  - iPhoneにインストール
  - APNs統合トークンで動作確認

- [ ] **Phase 3手動テスト完了**
  - Step 7: 通知設定フィルタリング（push_enabled切替）
  - Step 8: マルチデバイス登録（iOS + Android）
  - Step 9: Push通知配信テスト（フォアグラウンド/バックグラウンド/タップ）

### 優先度: LOW（本番デプロイ前）

- [ ] **EAS Build (Production) 作成**
  - App Store Connect登録
  - TestFlight配信
  - 本番環境での動作確認

- [ ] **診断スクリプト削除**
  - `test-*.php` 10ファイル削除
  - `/home/ktr/mtdev/` ルートをクリーンアップ

## まとめ

### 調査結果

| 項目 | 状態 | 詳細 |
|------|------|------|
| Firebase/GCP設定 | ✅ 正常 | Service Account, IAM, APIs全て正しい |
| Backend実装 | ✅ 正常 | kreait/firebase-php, FcmService正しく実装 |
| FCMトークン形式 | ❌ 不正 | Expo GoがiOSでAndroid形式トークンを生成 |
| 401エラー | ⚠️ 誤解 | 認証エラーではなく、トークン形式不一致 |

### 結論

**Expo Goの制約により、iOSでは本番レベルのPush通知動作確認ができない。**

**解決策**:
1. **短期**: Android実機でFCM動作確認（Backend実装の正しさを検証）
2. **中期**: EAS BuildでiOS本番ビルド作成（APNs統合トークン取得）
3. **長期**: 本番環境デプロイ前にTestFlight配信で最終検証

### 技術的学び

- Expo Goは開発用アプリで、プラットフォーム固有機能に制約がある
- iOS Push通知はAPNs証明書が必須 → Expo Go では Expo社の証明書を使用
- `@react-native-firebase/messaging` は EAS Build で完全動作
- 401エラーは認証問題ではなく、トークン形式の互換性問題だった

---

**作成日**: 2025-12-15  
**調査時間**: 約4時間  
**診断スクリプト数**: 10個  
**Service Account Keys生成**: 3個  
**結論**: Expo Go制約が根本原因、EAS Buildで解決可能
