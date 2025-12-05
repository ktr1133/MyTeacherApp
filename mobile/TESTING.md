# MyTeacher モバイルアプリ テストガイド

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-05 | GitHub Copilot | 初版作成: Phase 2.B-2認証機能のテストガイド |

---

## 概要

このドキュメントは、MyTeacherモバイルアプリ（React Native + Expo）のテスト方法を説明します。Phase 2.B-2で実装した認証機能を中心に、手動テスト、自動テスト、デバッグ手法を網羅します。

---

## 目次

1. [環境準備](#1-環境準備)
2. [TypeScript型チェック](#2-typescript型チェック静的検証)
3. [Webプレビューテスト](#3-webプレビューテスト)
4. [Expo Goによる実機テスト](#4-expo-goによる実機テスト)
5. [AsyncStorage検証](#5-asyncstorage検証)
6. [デバッグ方法](#6-デバッグ方法)
7. [バックエンド統合テスト](#7-バックエンド統合テスト今後)
8. [トラブルシューティング](#8-トラブルシューティング)

---

## 1. 環境準備

### 必須環境

```bash
# Node.js 18以上
node --version
# v20.19.5 (推奨)

# プロジェクトディレクトリ
cd /home/ktr/mtdev/mobile

# 依存関係インストール確認
npm install
```

### 開発ツール

- **VSCode**: TypeScript補完、ESLint統合
- **Chrome DevTools**: React Native Debuggerとして使用
- **Expo Go**: iOS/Android実機テスト用アプリ

---

## 2. TypeScript型チェック（静的検証）

### 実行コマンド

```bash
cd /home/ktr/mtdev/mobile
npx tsc --noEmit
```

### 期待結果

```
✅ No errors found.
```

### エラー例と対処

#### エラー: `Property 'XXX' does not exist on type 'YYY'`

**原因**: 型定義不足

**対処**:
```typescript
// 型定義を追加
interface User {
  id: number;
  name: string;
  email: string;
}

// または型アサーション（最終手段）
const user = response.data as User;
```

#### エラー: `Cannot find module '../../hooks/useAuth'`

**原因**: インポートパス誤り

**対処**:
```typescript
// 相対パスを確認
// src/screens/auth/LoginScreen.tsx から src/hooks/useAuth.ts へ
import { useAuth } from '../../hooks/useAuth'; // ✅ 正しい
import { useAuth } from '../hooks/useAuth';   // ❌ 誤り
```

---

## 3. Webプレビューテスト

### 起動手順

```bash
cd /home/ktr/mtdev/mobile
npm run web
```

**アクセス**: `http://localhost:19006`

### ブラウザ推奨設定

- **Chrome/Edge**: React DevTools拡張機能インストール推奨
- **モバイルビュー**: デベロッパーツールでスマートフォンエミュレート（F12 → デバイスツールバー）

### テストチェックリスト

#### 🔐 ログイン画面（LoginScreen）

| No | テスト項目 | 操作手順 | 期待結果 | 状態 |
|----|----------|---------|---------|------|
| 1 | 初期表示 | ページアクセス | Email/パスワード入力欄、ログインボタン、登録リンク表示 | ⬜ |
| 2 | 空値バリデーション | 未入力でログインボタンクリック | 「全ての項目を入力してください」Alert表示 | ⬜ |
| 3 | Email形式バリデーション | Email欄に「test」入力してログイン | 「有効なメールアドレスを入力してください」Alert表示 | ⬜ |
| 4 | ローディング状態 | 正しいEmail/Pass入力してログイン | ボタン無効化、「ログイン中...」表示 | ⬜ |
| 5 | APIエラー表示 | ログインボタンクリック（API未接続） | ネットワークエラーAlert表示 | ⬜ |
| 6 | 登録画面遷移 | 「アカウントをお持ちでない方」リンククリック | 登録画面に遷移 | ⬜ |

#### 📝 登録画面（RegisterScreen）

| No | テスト項目 | 操作手順 | 期待結果 | 状態 |
|----|----------|---------|---------|------|
| 7 | 初期表示 | ログイン画面から遷移 | 名前/Email/パスワード/確認入力欄表示 | ⬜ |
| 8 | 空値バリデーション | 未入力で登録ボタンクリック | 「全ての項目を入力してください」Alert表示 | ⬜ |
| 9 | パスワード長バリデーション | パスワードに「1234」入力して登録 | 「パスワードは8文字以上で入力してください」Alert表示 | ⬜ |
| 10 | パスワード不一致 | パスワード「12345678」、確認「87654321」で登録 | 「パスワードが一致しません」Alert表示 | ⬜ |
| 11 | Email形式バリデーション | Email欄に「invalid」入力して登録 | 「有効なメールアドレスを入力してください」Alert表示 | ⬜ |
| 12 | ローディング状態 | 正しいフォーム入力して登録 | ボタン無効化、「登録中...」表示 | ⬜ |
| 13 | ログイン画面遷移 | 「既にアカウントをお持ちの方」リンククリック | ログイン画面に遷移 | ⬜ |

#### 🏠 ホーム画面（HomeScreen）

| No | テスト項目 | 操作手順 | 期待結果 | 状態 |
|----|----------|---------|---------|------|
| 14 | 表示内容（未実装） | ログイン成功後 | 「ようこそ、{ユーザー名}さん！」表示 | ⏳ API未接続 |
| 15 | ログアウト | ログアウトボタンクリック | ログイン画面に遷移 | ⏳ API未接続 |
| 16 | 認証永続性（未実装） | ブラウザ再読み込み | ログイン状態維持 | ⏳ API未接続 |

### スクリーンショット取得

```bash
# ブラウザでF12 → デバイスツールバー → iPhone 14 Pro選択
# 各画面をスクリーンショット保存
```

**保存場所**: `/home/ktr/mtdev/docs/screenshots/mobile/phase2-b2/`

---

## 4. Expo Goによる実機テスト

### iOS（iPhoneでテスト）

#### 前提条件
- [ ] App Store から **Expo Go** インストール
- [ ] iPhone と開発PCが同一Wi-Fi接続

#### 実行手順

```bash
cd /home/ktr/mtdev/mobile
npm start
```

#### QRコードスキャン

1. ターミナルに表示されるQRコードをスキャン
2. iOSの場合: **カメラアプリ** でQRコードをスキャン → 「Expo Goで開く」タップ
3. アプリが自動的にExpo Goで起動

### Android（Androidでテスト）

#### 前提条件
- [ ] Google Play から **Expo Go** インストール
- [ ] Android と開発PCが同一Wi-Fi接続

#### 実行手順

```bash
cd /home/ktr/mtdev/mobile
npm start
```

#### QRコードスキャン

1. Expo Goアプリを起動
2. **Scan QR Code** タップ
3. ターミナルのQRコードをスキャン
4. アプリが自動的に読み込まれる

### 実機テストチェックリスト

| No | テスト項目 | 操作手順 | 期待結果 | iOS | Android |
|----|----------|---------|---------|-----|---------|
| 17 | アプリ起動 | QRコードスキャン | ログイン画面表示 | ⬜ | ⬜ |
| 18 | キーボード表示 | Email入力欄タップ | キーボード表示、ScrollView調整 | ⬜ | ⬜ |
| 19 | タッチ操作 | 各ボタンタップ | タップフィードバック動作 | ⬜ | ⬜ |
| 20 | 画面遷移アニメーション | ログイン↔登録画面遷移 | スムーズなスライド遷移 | ⬜ | ⬜ |
| 21 | Alert表示 | バリデーションエラー発生 | ネイティブAlert表示 | ⬜ | ⬜ |
| 22 | ローディング状態 | ログインボタンタップ | ボタン無効化、テキスト変更 | ⬜ | ⬜ |

### デバイス固有の確認事項

#### iOS
- [ ] SafeAreaView が正しく動作（ノッチ回避）
- [ ] キーボード表示時のレイアウト崩れなし
- [ ] ステータスバーの色（dark-content）

#### Android
- [ ] バックボタンでアプリ終了（Navigationスタックが空の場合）
- [ ] キーボード表示時のレイアウト調整
- [ ] 端末の戻るボタン動作

---

## 5. AsyncStorage検証

### Chrome DevTools Console（Web版）

#### AsyncStorage操作コマンド

```javascript
// ストレージ内容確認
import('react-native').then(({ default: RN }) => {
  RN.AsyncStorage.getAllKeys().then(console.log);
});

// 特定キー取得
import('@react-native-async-storage/async-storage').then(({ default: AsyncStorage }) => {
  AsyncStorage.getItem('auth_token').then(token => {
    console.log('JWT Token:', token);
  });
  
  AsyncStorage.getItem('user').then(userJson => {
    console.log('User Data:', JSON.parse(userJson));
  });
});

// クリア
import('@react-native-async-storage/async-storage').then(({ default: AsyncStorage }) => {
  AsyncStorage.clear().then(() => console.log('Storage cleared'));
});
```

### Expo Go実機での確認

#### React Native Debuggerを使用

```bash
# Expo Goアプリでメニュー表示（デバイスをシェイク）
# → "Debug Remote JS" 選択
# → Chrome DevToolsが自動で開く
```

#### AsyncStorage検証項目

| No | テスト項目 | 操作手順 | 期待結果 | 状態 |
|----|----------|---------|---------|------|
| 23 | トークン保存 | ログイン成功後、Console確認 | `auth_token`キーにJWT保存 | ⏳ API未接続 |
| 24 | ユーザー情報保存 | ログイン成功後、Console確認 | `user`キーにJSON形式で保存 | ⏳ API未接続 |
| 25 | ログアウト時削除 | ログアウト後、Console確認 | 両キーが削除されている | ⏳ API未接続 |
| 26 | 再起動後復元 | アプリ再起動（ホットリロード） | ログイン状態維持 | ⏳ API未接続 |

---

## 6. デバッグ方法

### Console.log デバッグ

#### サービス層デバッグ

```typescript
// src/services/auth.service.ts
export const login = async (email: string, password: string): Promise<User> => {
  console.log('[Auth] Login attempt:', { email });
  
  try {
    const response = await api.post<ApiResponse<AuthResponse>>('/login', {
      email,
      password,
    });
    
    console.log('[Auth] Login response:', response.data);
    
    // ... 処理 ...
    
  } catch (error) {
    console.error('[Auth] Login error:', error);
    throw error;
  }
};
```

#### Hook デバッグ

```typescript
// src/hooks/useAuth.ts
export const useAuth = () => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    console.log('[useAuth] Checking authentication...');
    checkAuth();
  }, []);
  
  const checkAuth = async () => {
    console.log('[useAuth] checkAuth called');
    // ... 処理 ...
    console.log('[useAuth] Authentication result:', isAuth);
  };
  
  // ...
};
```

### React Native Debugger

#### 起動手順

```bash
# Chrome DevToolsを使用する場合
# Expo Goアプリでメニュー → "Debug Remote JS"

# または React Native Debuggerアプリをインストール
brew install --cask react-native-debugger  # macOS
```

#### ブレークポイント設定

1. Chrome DevTools → Sources タブ
2. ファイル検索（Ctrl+P）で `auth.service.ts` を開く
3. 行番号クリックでブレークポイント設定
4. アプリでログインボタンをクリック
5. ブレークポイントで停止 → 変数検査

### Network通信の確認

#### Axios Interceptor にログ追加

```typescript
// src/services/api.ts
api.interceptors.request.use(
  async (config) => {
    console.log('[API] Request:', {
      method: config.method,
      url: config.url,
      headers: config.headers,
      data: config.data,
    });
    
    const token = await storage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error('[API] Request error:', error);
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response) => {
    console.log('[API] Response:', {
      status: response.status,
      data: response.data,
    });
    return response;
  },
  async (error) => {
    console.error('[API] Response error:', {
      status: error.response?.status,
      data: error.response?.data,
    });
    
    if (error.response?.status === 401) {
      console.log('[API] Unauthorized - clearing auth data');
      await storage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
      await storage.removeItem(STORAGE_KEYS.USER);
    }
    
    return Promise.reject(error);
  }
);
```

### エラーハンドリングのテスト

#### 意図的にエラーを発生させる

```typescript
// 一時的にAPI URLを無効化
// src/utils/constants.ts
export const API_CONFIG = {
  BASE_URL: 'http://invalid-url-for-testing.local', // ネットワークエラーをテスト
  TIMEOUT: 30000,
};
```

#### 期待する動作
1. ログインボタンタップ
2. ローディング表示
3. ネットワークエラーAlert表示
4. ボタンが再度有効化

---

## 7. バックエンド統合テスト（今後）

### 前提条件

1. **Laravel API起動**

```bash
cd /home/ktr/mtdev
DB_HOST=localhost DB_PORT=5432 php artisan serve --host=0.0.0.0 --port=8080
```

2. **API URL変更**

```typescript
// src/utils/constants.ts
export const API_CONFIG = {
  BASE_URL: 'http://localhost:8080/api', // ローカルLaravel
  TIMEOUT: 30000,
};
```

### 統合テストチェックリスト

| No | テスト項目 | エンドポイント | 期待結果 | 状態 |
|----|----------|--------------|---------|------|
| 27 | ユーザー登録 | POST `/api/register` | JWT取得、ユーザー情報返却 | ⏳ 未実施 |
| 28 | ログイン | POST `/api/login` | JWT取得、ユーザー情報返却 | ⏳ 未実施 |
| 29 | ユーザー情報取得 | GET `/api/user` | 認証済みユーザー情報返却 | ⏳ 未実施 |
| 30 | JWT自動付与 | GET `/api/user` | Authorizationヘッダーに自動付与 | ⏳ 未実施 |
| 31 | 401エラーハンドリング | トークン期限切れでAPI呼び出し | 自動ログアウト、ログイン画面遷移 | ⏳ 未実施 |

### テストデータ作成

```bash
cd /home/ktr/mtdev
DB_HOST=localhost DB_PORT=5432 php artisan tinker

# テストユーザー作成
User::factory()->create([
    'email' => 'mobile-test@example.com',
    'password' => bcrypt('password123')
]);
```

### API動作確認（curl）

```bash
# 1. 登録
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# 2. ログイン
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# レスポンス例
# {
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
#   "user": {
#     "id": 1,
#     "name": "Test User",
#     "email": "test@example.com"
#   }
# }

# 3. 認証済みAPIアクセス
curl -X GET http://localhost:8080/api/user \
  -H "Authorization: Bearer {上記のtoken}"
```

---

## 8. トラブルシューティング

### よくある問題と解決策

#### 問題1: Metro Bundler起動エラー

**エラー**: `Unable to start Metro server`

**原因**: ポート8081が既に使用中

**解決**:
```bash
# ポート使用状況確認
lsof -i :8081

# プロセス終了
kill -9 {PID}

# または別ポート使用
npm start -- --port 8082
```

#### 問題2: Expo Goでアプリが表示されない

**エラー**: `Unable to connect to development server`

**原因**: Wi-Fi接続の問題、ファイアウォール

**解決**:
```bash
# 1. デバイスとPCが同一Wi-Fi確認
# 2. トンネルモード使用
npm start -- --tunnel

# 3. ファイアウォール設定確認
sudo ufw allow 8081
```

#### 問題3: TypeScriptエラーが消えない

**エラー**: `Cannot find module 'XXX'`

**原因**: node_modules破損、キャッシュ問題

**解決**:
```bash
# キャッシュクリア
npm cache clean --force

# node_modules削除・再インストール
rm -rf node_modules package-lock.json
npm install

# Metro Bundlerキャッシュクリア
npx expo start --clear
```

#### 問題4: AsyncStorageにデータが保存されない

**エラー**: `getItem` が常に `null` を返す

**原因**: 非同期処理の待機不足

**解決**:
```typescript
// ❌ NG: awaitなし
const token = storage.getItem('auth_token');
console.log(token); // Promise{<pending>}

// ✅ OK: await使用
const token = await storage.getItem('auth_token');
console.log(token); // "eyJ0eXAiOiJKV1QiLCJh..."
```

#### 問題5: APIリクエストが401エラー

**エラー**: `Unauthorized`

**原因**: JWT未付与、トークン期限切れ

**デバッグ**:
```typescript
// Axios Interceptorログ確認
// src/services/api.ts
api.interceptors.request.use(
  async (config) => {
    const token = await storage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    console.log('[API] JWT Token:', token); // トークン確認
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      console.log('[API] Authorization Header:', config.headers.Authorization);
    } else {
      console.warn('[API] No JWT token found in storage');
    }
    
    return config;
  }
);
```

### ログ確認手順

#### Metro Bundlerログ

```bash
# ターミナルでログ確認
cd /home/ktr/mtdev/mobile
npm start
# ログがリアルタイム表示される
```

#### Chrome DevTools Console

```bash
# Expo Goアプリでメニュー → "Debug Remote JS"
# Chrome DevToolsが自動で開く
# Console タブで全ログ確認
```

#### Expo Goアプリログ

- **iOS**: デバイスをシェイク → メニュー → "Show Performance Monitor"
- **Android**: デバイスをシェイク → メニュー → "Dev Settings" → "JS Dev Mode"

---

## まとめ

このテストガイドにより、以下を実現：

- ✅ **静的検証**: TypeScriptコンパイルチェック
- ✅ **UI動作確認**: Webプレビュー + 実機テスト
- ✅ **状態管理検証**: AsyncStorage動作確認
- ✅ **デバッグ手法**: Console.log、React Native Debugger、Network監視
- ✅ **統合テスト準備**: Laravel API連携の手順

Phase 2.B-3以降のタスク管理機能実装時も、このガイドを参考にテストを実施してください。

---

**作成者**: GitHub Copilot  
**最終更新**: 2025年12月5日  
**対象フェーズ**: Phase 2.B-2（認証機能）
