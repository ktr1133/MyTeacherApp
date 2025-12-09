# Firebase設定ファイルのプレースホルダー

このディレクトリには以下のFirebase設定ファイルを配置してください：

## iOS用: GoogleService-Info.plist

Firebase Consoleから以下の手順でダウンロードしてください：

1. Firebase Console (https://console.firebase.google.com/) にアクセス
2. プロジェクトを選択
3. プロジェクト設定 → iOSアプリを追加
4. バンドルID: `com.myteacher.app`
5. `GoogleService-Info.plist` をダウンロード
6. このディレクトリ（`/home/ktr/mtdev/mobile/`）に配置

## Android用: google-services.json

Firebase Consoleから以下の手順でダウンロードしてください：

1. Firebase Console (https://console.firebase.google.com/) にアクセス
2. プロジェクトを選択
3. プロジェクト設定 → Androidアプリを追加
4. パッケージ名: `com.myteacher.app`
5. `google-services.json` をダウンロード
6. このディレクトリ（`/home/ktr/mtdev/mobile/`）に配置

## セキュリティ注意事項

- これらのファイルは **Gitにコミットしないでください**
- `.gitignore` に以下を追加済み:
  - `GoogleService-Info.plist`
  - `google-services.json`

## 開発環境セットアップ

上記のファイルを配置後、以下のコマンドで開発サーバーを起動してください：

```bash
cd /home/ktr/mtdev/mobile
npx expo prebuild  # ネイティブプロジェクト生成
npm start
```

## Firebase Cloud Messaging (FCM) 設定

### 前提条件

- ✅ Firebase Consoleでプロジェクト作成済み
- ✅ iOS/Androidアプリ登録済み（GoogleService-Info.plist / google-services.json ダウンロード完了）

### iOS用 APNs認証キー設定（**Apple Developer Program登録必須**）

#### 1. Apple Developer Program登録

**重要**: iOS実機でのPush通知テストには、**Apple Developer Program（年額14,800円）への登録が必須**です。

1. https://developer.apple.com/programs/ にアクセス
2. 「Enroll」をクリック
3. Apple IDでサインイン
4. 支払い情報入力（年額14,800円、クレジットカード決済）
5. 承認待ち（通常24時間以内、最大48時間）

#### 2. APNs認証キー（.p8ファイル）作成

**登録完了後、以下の手順でAPNs認証キーを作成してください。**

1. **Apple Developer Centerにアクセス**: https://developer.apple.com/account/resources/authkeys/list
2. **Apple IDでサインイン**（開発者アカウント）
3. **新規キー作成**:
   - 「+」ボタン（Keys の横）をクリック
   - **Key Name**: 任意の名前を入力（例: `MyTeacher APNs Key`）
   - **Key Services**: 「Apple Push Notifications service (APNs)」にチェック
   - 「Continue」→「Register」をクリック
4. **キーファイルをダウンロード**:
   - **「Download」ボタンをクリック** → `AuthKey_XXXXXXXXXX.p8` ファイルがダウンロードされます
   - ⚠️ **注意**: `.p8`ファイルは**一度しかダウンロードできません**。紛失した場合は新規作成が必要です。
5. **重要な情報をメモ**:
   - **Key ID**: 例 `ABCD1234EF`（画面に表示されます）
   - **Team ID**: 例 `Z9XY8W7V6U`（画面上部またはAccount設定で確認）

#### 3. Firebase Consoleにアップロード

1. Firebase Console (https://console.firebase.google.com/) にアクセス
2. プロジェクトを選択
3. **プロジェクト設定**（⚙️アイコン）→ 「Cloud Messaging」タブを選択
4. **iOS app configuration** セクション
5. 「APNs Authentication Key」の「Upload」ボタンをクリック
6. 以下を入力:
   - **APNs auth key**: ダウンロードした `.p8` ファイルを選択
   - **Key ID**: メモした Key ID を入力（例: `ABCD1234EF`）
   - **Team ID**: メモした Team ID を入力（例: `Z9XY8W7V6U`）
7. 「Upload」をクリック

**補足**:
- ✅ **推奨: 認証キー方式（.p8）**
  - 有効期限なし（永続的に使用可能）
  - 1つのキーで複数アプリに使用可能
  - 設定が簡単
- ❌ 証明書方式（.p12）
  - 毎年更新が必要
  - アプリごとに証明書が必要
  - 設定が複雑

### Android用 FCM設定

Androidは `google-services.json` を配置するだけで**自動的にFCMが有効化**されます。追加設定は不要です。

- ✅ FCM Server Keyは不要（Firebase SDK v3以降）
- ✅ `google-services.json` に含まれる設定が自動適用されます

### トラブルシューティング

#### Q. 「Cloud Messaging」タブが見つからない

**A. 以下の場所を確認してください**:

**方法1: プロジェクト設定から**
```
Firebase Console
→ プロジェクトを選択
→ 歯車アイコン（⚙️）→ プロジェクトの設定
→ 上部タブから「Cloud Messaging」を選択
```

**方法2: Engageメニューから**
```
Firebase Console
→ 左サイドバー「Engage」セクション
→ 「Messaging」
→ 右上「...」メニュー → 「Settings」
```

## 参照
- Firebase公式ドキュメント: https://firebase.google.com/docs/cloud-messaging
- React Native Firebase: https://rnfirebase.io/
