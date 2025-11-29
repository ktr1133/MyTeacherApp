# Phase 1 完了レポート: Amazon Cognito統合（JWT認証への移行）

**作成日**: 2025年11月25日  
**フェーズ**: Phase 1  
**ステータス**: ✅ 完了  
**実装期間**: 2025年11月25日（1日）

---

## 📋 概要

Phase 1では、Laravel Breeze（セッションベース認証）からAmazon Cognito（JWT認証）への移行を実施しました。AWS環境でのスケーラブルな認証基盤を構築し、全既存ユーザー（7名）をCognitoに移行しました。

### 主な成果

- ✅ Amazon Cognito User Pool & Identity Pool構築（Terraform）
- ✅ API Gateway with Cognito Authorizer構築（Terraform）
- ✅ Laravel JWT検証ミドルウェア実装
- ✅ フロントエンドCognito認証サービス実装
- ✅ ユーザー移行コマンド実装
- ✅ 全7ユーザーのCognito移行完了
- ✅ usersテーブルスキーマ拡張（email, name, cognito_sub, auth_provider追加）

---

## 🏗️ デプロイされたAWSリソース

### Cognito User Pool

**User Pool ID**: `ap-northeast-1_O2zUaaHEM`  
**Region**: `ap-northeast-1` (東京)

#### 設定詳細

| 項目 | 設定値 |
|------|--------|
| **ユーザー名属性** | Email（メールアドレスでログイン） |
| **パスワードポリシー** | 最小8文字、大文字・小文字・数字・記号必須 |
| **MFA** | OPTIONAL（ユーザーが選択可能） |
| **自動検証** | Email（メール確認必須） |
| **カスタム属性** | `custom:timezone` (string), `custom:is_admin` (string) |
| **削除保護** | ACTIVE（誤削除防止） |

#### User Pool Clients

| クライアント | Client ID | 用途 | Access Token TTL | Refresh Token TTL |
|--------------|-----------|------|-------------------|-------------------|
| **Web Client** | `69prfmvdrbq4p7adaql8j8af5b` | ユーザー認証 | 60分 | 30日 |
| **Admin Client** | `4ee0kqaonejoudqhfjeqjhthlb` | 管理機能 | 30分 | 7日 |

**認証フロー**: USER_PASSWORD_AUTH（ユーザー名/パスワード）

### Cognito Identity Pool

**Identity Pool ID**: `ap-northeast-1:54f12983-012f-4c84-9763-72a19cd023f2`

#### IAM Role

**認証済みユーザーロール**: `myteacher-cognito-authenticated-role`  
**権限**: S3へのユーザースコープアクセス（`myteacher-app-uploads/user-{cognito-identity.amazonaws.com:sub}/*`）

### API Gateway

**API ID**: `7go6joczpi`  
**Invoke URL**: `https://7go6joczpi.execute-api.ap-northeast-1.amazonaws.com/production`

#### Cognito Authorizer

| 項目 | 設定値 |
|------|--------|
| **Type** | COGNITO_USER_POOLS |
| **User Pool ARN** | `arn:aws:cognito-idp:ap-northeast-1:469751479977:userpool/ap-northeast-1_O2zUaaHEM` |
| **Token Source** | `Authorization` ヘッダー |
| **TTL** | 300秒（5分） |

#### エンドポイント

| パス | メソッド | 認証 | 用途 |
|------|----------|------|------|
| `/auth/login` | POST | なし | ログイン |
| `/auth/register` | POST | なし | ユーザー登録 |
| `/auth/logout` | POST | Cognito | ログアウト |
| `/api/{proxy+}` | ANY | Cognito | すべてのAPIリクエスト |

#### スロットリング設定

- **バーストリミット**: 5,000 リクエスト/秒
- **レートリミット**: 2,000 リクエスト/秒

#### CORS設定

- **許可ヘッダー**: `Content-Type,X-Amz-Date,Authorization,X-Api-Key,X-Amz-Security-Token`
- **許可メソッド**: `GET,POST,PUT,PATCH,DELETE,OPTIONS`
- **許可オリジン**: `*`（本番環境では制限推奨）

### JWKS URL

**JWKS Endpoint**: `https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_O2zUaaHEM/.well-known/jwks.json`

---

## 💻 技術的な変更内容

### 1. データベーススキーマ変更

#### usersテーブル - 新規カラム追加

| カラム名 | データ型 | 制約 | 説明 |
|----------|----------|------|------|
| `email` | varchar | UNIQUE, NULL | メールアドレス（Cognito必須、既存ユーザーには`{username}@myteacher.local`を自動設定） |
| `name` | varchar | NULL | 表示名（Cognito用、既存ユーザーには`username`をコピー） |
| `cognito_sub` | varchar(100) | UNIQUE, NULL | CognitoユーザーのSub（UUID、一意識別子） |
| `auth_provider` | enum('breeze', 'cognito') | NOT NULL, DEFAULT 'breeze' | 認証プロバイダー |

**マイグレーションファイル**: `database/migrations/2025_11_25_000001_add_cognito_fields_to_users_table.php`

**既存データ対応**:
```sql
UPDATE users SET 
  email = username || '@myteacher.local',
  name = username
WHERE email IS NULL;
```

#### インデックス追加

- `users.cognito_sub` - UNIQUE INDEX
- `users.auth_provider` - INDEX

### 2. バックエンド実装

#### JWT検証ミドルウェア

**ファイル**: `app/Http/Middleware/VerifyCognitoToken.php`  
**ミドルウェアエイリアス**: `cognito`

**機能**:
- Authorizationヘッダーから Bearer トークン抽出
- JWKS（JSON Web Key Set）を使用したトークン検証
  - JWKSキャッシュ: 3600秒（1時間）
  - 署名検証: RS256アルゴリズム
- クレーム検証:
  - `token_use`: `access`
  - `iss`: Cognito Issuer URL
  - `client_id`: Web Client ID
  - `exp`: トークン有効期限
  - `sub`: Cognito Sub必須
- リクエスト属性への追加:
  - `cognito_sub`: Cognito UUID
  - `cognito_email`: メールアドレス
- オプション: Userモデルルックアップ（`cognito_sub`でDB検索）

**依存ライブラリ**: `firebase/php-jwt` v6.11.1

#### ユーザー移行コマンド

**コマンド**: `php artisan cognito:migrate-users`  
**ファイル**: `app/Console/Commands/MigrateUsersToCognito.php`

**機能**:
- 既存usersテーブル（`auth_provider='breeze'` かつ `cognito_sub IS NULL`）からCognitoへ一括移行
- 一時パスワード自動生成（12文字、大文字・小文字・数字・記号含む）
- Cognito AdminCreateUser API呼び出し
- DB更新（`cognito_sub`, `auth_provider='cognito'`）
- プログレスバー表示
- エラーハンドリング（UsernameExistsException対応）
- ドライランモード（`--dry-run`）
- 特定ユーザー指定（`--user={id}`）

**AWS認証情報**:
- 環境変数: `COGNITO_ACCESS_KEY_ID`, `COGNITO_SECRET_ACCESS_KEY`
- MinIO用（`AWS_ACCESS_KEY_ID`）と明示的に分離

### 3. フロントエンド実装

#### Cognito認証サービス

**ファイル**: `resources/js/auth/cognito.js`  
**クラス**: `CognitoAuthService`

**依存ライブラリ**: `amazon-cognito-identity-js` v6.3.13

**機能**:
- **ログイン**: `login(email, password)`
  - MFA対応（`NEW_PASSWORD_REQUIRED`チャレンジ）
  - トークン自動保存（localStorage）
- **ユーザー登録**: `register(email, password, attributes)`
  - カスタム属性設定（timezone, is_admin）
- **メール確認**: `confirmRegistration(email, code)`
- **ログアウト**: `logout()` - グローバルサインアウト
- **現在ユーザー取得**: `getCurrentUser()`
- **トークンリフレッシュ**: `refreshToken()`
- **パスワードリセット**: `forgotPassword(email)`, `confirmPassword(email, code, newPassword)`
- **トークン管理**: LocalStorage（`idToken`, `accessToken`, `refreshToken`）

### 4. 環境変数設定

#### .env ファイル - Cognito設定

```env
# ========================================
# Amazon Cognito設定
# ========================================
COGNITO_REGION=ap-northeast-1
COGNITO_USER_POOL_ID=ap-northeast-1_O2zUaaHEM
COGNITO_WEB_CLIENT_ID=69prfmvdrbq4p7adaql8j8af5b
COGNITO_ADMIN_CLIENT_ID=4ee0kqaonejoudqhfjeqjhthlb
COGNITO_JWKS_URL=https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_O2zUaaHEM/.well-known/jwks.json
COGNITO_ISSUER_URL=https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_O2zUaaHEM

# Cognito Identity Pool（S3アクセス用）
COGNITO_IDENTITY_POOL_ID=ap-northeast-1:54f12983-012f-4c84-9763-72a19cd023f2

# API Gateway
API_GATEWAY_INVOKE_URL=https://7go6joczpi.execute-api.ap-northeast-1.amazonaws.com/production
API_GATEWAY_AUTH_ENDPOINT=https://7go6joczpi.execute-api.ap-northeast-1.amazonaws.com/production/auth
API_GATEWAY_API_ENDPOINT=https://7go6joczpi.execute-api.ap-northeast-1.amazonaws.com/production/api

# AWS Cognito用認証情報（infrauser）
COGNITO_ACCESS_KEY_ID=***REDACTED***
COGNITO_SECRET_ACCESS_KEY=***REDACTED***

# AWS S3/MinIO用認証情報（MinIO専用）
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123

# Viteフロントエンド用
VITE_COGNITO_REGION=ap-northeast-1
VITE_COGNITO_USER_POOL_ID=ap-northeast-1_O2zUaaHEM
VITE_COGNITO_CLIENT_ID=69prfmvdrbq4p7adaql8j8af5b
VITE_API_GATEWAY_URL=https://7go6joczpi.execute-api.ap-northeast-1.amazonaws.com/production
```

#### config/services.php - Cognito設定

```php
'cognito' => [
    'region' => env('COGNITO_REGION', 'ap-northeast-1'),
    'user_pool_id' => env('COGNITO_USER_POOL_ID'),
    'web_client_id' => env('COGNITO_WEB_CLIENT_ID'),
    'admin_client_id' => env('COGNITO_ADMIN_CLIENT_ID'),
    'jwks_url' => env('COGNITO_JWKS_URL'),
    'issuer_url' => env('COGNITO_ISSUER_URL'),
    'identity_pool_id' => env('COGNITO_IDENTITY_POOL_ID'),
],
```

### 5. Terraform構成

#### モジュール構造

```
infrastructure/terraform/
├── main.tf                          # ルートモジュール
├── variables.tf                     # 変数定義
├── terraform.tfvars                 # 変数値
└── modules/
    ├── cognito/
    │   ├── main.tf                  # Cognito User Pool, Identity Pool
    │   ├── variables.tf
    │   └── outputs.tf
    └── api-gateway/
        ├── main.tf                  # API Gateway, Cognito Authorizer
        ├── variables.tf
        └── outputs.tf
```

#### デプロイ統計

- **作成リソース数**: 37個
  - Cognito関連: 8個（User Pool, Clients, Identity Pool, IAM Roles等）
  - API Gateway関連: 29個（REST API, Resources, Methods, Integrations, Authorizer, Stage, Deployment, Usage Plan等）

---

## 🔧 トラブルシューティング履歴

### 問題1: Terraform - throttle_settings設定エラー

**エラー内容**:
```
Error: Blocks of type "throttle_settings" are not expected here.
```

**原因**: `aws_api_gateway_stage`リソース内で`throttle_settings`ブロックを直接記述

**解決方法**: `aws_api_gateway_method_settings`リソースに分離
```hcl
resource "aws_api_gateway_method_settings" "all" {
  rest_api_id = aws_api_gateway_rest_api.main.id
  stage_name  = aws_api_gateway_stage.production.stage_name
  method_path = "*/*"
  
  settings {
    throttling_burst_limit = var.throttle_burst_limit
    throttling_rate_limit  = var.throttle_rate_limit
  }
}
```

### 問題2: Terraform - cloudfront_domain_name参照エラー

**エラー内容**:
```
This object does not have an attribute named "cloudfront_domain_name".
```

**原因**: `module.myteacher`の条件分岐で存在しない属性を参照

**解決方法**: backend_urlをALB DNSのみに修正
```hcl
backend_url = "http://${module.myteacher.alb_dns_name}"
```

### 問題3: IAM - cognito-idp:CreateUserPool権限不足

**エラー内容**:
```
User infrauser is not authorized to perform: cognito-idp:CreateUserPool
```

**解決方法**: AWSコンソールでマネージドポリシー追加
- `AmazonCognitoPowerUser`
- `AmazonAPIGatewayAdministrator`

### 問題4: IAM - iam:PassRole権限不足（繰り返し発生）

**エラー内容**:
```
User infrauser is not authorized to perform: iam:PassRole on resource: 
arn:aws:iam::469751479977:role/myteacher-cognito-authenticated-role
```

**原因**: 既存IAMポリシーの`Condition`ブロックが原因
```json
{
  "Condition": {
    "StringEquals": {
      "iam:PassedToService": ["ecs-tasks.amazonaws.com"]
    }
  }
}
```

**解決方法**: Conditionを削除し、`iam:GetRole`権限を追加した新ポリシーを作成
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["iam:PassRole", "iam:GetRole"],
      "Resource": [
        "arn:aws:iam::469751479977:role/myteacher-cognito-*",
        "arn:aws:iam::469751479977:role/myteacher-api-gateway-*"
      ]
    }
  ]
}
```

**ドキュメント**: `infrastructure/terraform/IAM_PERMISSION_UPDATE_REQUEST_COGNITO.md`

### 問題5: データベース - last_login_at重複カラムエラー

**エラー内容**:
```
SQLSTATE[42701]: Duplicate column: column "last_login_at" of relation "users" already exists
```

**原因**: usersテーブルに既に`last_login_at`カラムが存在

**解決方法**: マイグレーションに`Schema::hasColumn()`チェックを追加
```php
if (!Schema::hasColumn('users', 'last_login_at')) {
    $table->timestamp('last_login_at')->nullable()->after('updated_at');
}
```

### 問題6: AWS認証 - UnrecognizedClientException

**エラー内容**:
```
The security token included in the request is invalid.
```

**原因**: `.env`の`AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`がMinIO用（`minio`/`minio123`）

**解決方法**: Cognito用認証情報を分離
```env
COGNITO_ACCESS_KEY_ID=***REDACTED***
COGNITO_SECRET_ACCESS_KEY=***REDACTED***
```

コマンド内でCognitoクライアント初期化時に明示的に指定:
```php
$config = [
    'version' => 'latest',
    'region' => config('services.cognito.region'),
    'credentials' => [
        'key' => env('COGNITO_ACCESS_KEY_ID'),
        'secret' => env('COGNITO_SECRET_ACCESS_KEY'),
    ],
];
```

---

## 📊 ユーザー移行結果

### 移行統計

| 項目 | 件数 |
|------|------|
| **移行対象ユーザー** | 7名 |
| **移行成功** | 7名 (100%) |
| **移行失敗** | 0名 |
| **スキップ** | 0名 |

### 移行されたユーザー一覧

| ID | Username | Email | Name | Cognito Sub | Auth Provider |
|----|----------|-------|------|-------------|---------------|
| 1 | admin | admin@myteacher.local | admin | c7f46af8-7011-7099-f324-55760bb08cdc | cognito |
| 2 | testuser | testuser@myteacher.local | testuser | d7f46a88-6021-70c6-0664-59111e1d2e0c | cognito |
| 3 | testuser2 | testuser2@myteacher.local | testuser2 | 47b4bac8-b011-7084-6000-5abc4bb76e4c | cognito |
| 4 | testuser3 | testuser3@myteacher.local | testuser3 | 37b43a08-3051-701f-b79d-f3b46b8e8ff5 | cognito |
| 5 | testuser4 | testuser4@myteacher.local | testuser4 | 57d4da28-2031-70db-03cc-c5dc1d2ba55d | cognito |
| 6 | testuser5 | testuser5@myteacher.local | testuser5 | 67447ae8-a0a1-708d-e7ed-c77bcedc0e8b | cognito |
| 7 | testuser6 | testuser6@myteacher.local | testuser6 | d7141ab8-40b1-70fc-a192-5ad9b6ece8a8 | cognito |

### 移行プロセス

1. **事前準備**:
   - usersテーブルに`email`, `name`, `cognito_sub`, `auth_provider`カラム追加
   - 既存ユーザーに疑似メールアドレス（`{username}@myteacher.local`）設定

2. **移行実行**:
   ```bash
   php artisan cognito:migrate-users --force
   ```

3. **Cognito操作**:
   - AdminCreateUser APIでユーザー作成
   - 一時パスワード生成（12文字）
   - メール送信抑制（`MessageAction=SUPPRESS`）
   - カスタム属性設定（timezone, is_admin）

4. **DB更新**:
   - `cognito_sub`: CognitoのSub（UUID）を保存
   - `auth_provider`: `'cognito'`に更新

---

## 🔒 セキュリティ考慮事項

### 実装済み

1. **トークン検証**:
   - JWKS署名検証（RS256）
   - クレーム検証（iss, client_id, exp, token_use）
   - トークンリプレイ攻撃対策（exp検証）

2. **パスワードポリシー**:
   - 最小8文字
   - 大文字・小文字・数字・記号必須
   - Cognitoによる強制適用

3. **MFA**:
   - OPTIONAL設定（ユーザーが選択可能）
   - TOTP対応

4. **削除保護**:
   - User Pool削除保護ACTIVE
   - 誤削除防止

5. **IAM権限最小化**:
   - Cognito Authenticated RoleはS3のユーザースコープパスのみアクセス可能
   - `myteacher-app-uploads/user-{cognito-identity.amazonaws.com:sub}/*`

6. **API Gateway スロットリング**:
   - DDoS攻撃対策
   - バースト: 5,000 req/s
   - レート: 2,000 req/s

### 今後の改善事項

1. **本番環境CORS設定**:
   - 現在: `*`（全オリジン許可）
   - 推奨: 特定ドメインのみ許可

2. **メール送信**:
   - 現在: 一時パスワード手動通知
   - TODO: SES統合で自動メール送信

3. **パスワード変更フロー**:
   - 初回ログイン時の強制パスワード変更UI実装

4. **監査ログ**:
   - Cognito CloudWatch Logsの分析・アラート設定

5. **並行運用終了後**:
   - Breeze関連コード削除
   - セッションテーブル削除

---

## 📈 パフォーマンス・スケーラビリティ

### JWT検証パフォーマンス

- **JWKSキャッシュ**: 3600秒（1時間）
  - 毎リクエストでのCognito JWKS取得を回避
  - Redisキャッシュ利用
- **予想スループット**: 1,000+ req/s（JWT検証のみ）

### API Gateway スケーラビリティ

- **自動スケーリング**: AWSマネージド（無制限）
- **スロットリング**: 設定済み（5,000 burst / 2,000 rate）
- **リージョン**: ap-northeast-1（東京）

### Cognito スケーラビリティ

- **ユーザー数制限**: なし（AWSマネージド）
- **リクエスト制限**: 
  - AdminCreateUser: 5 req/s（移行時のみ）
  - Authentication: 無制限

---

## 💰 コスト分析

### Cognito料金（東京リージョン）

| 項目 | 月間料金 |
|------|----------|
| **User Pool** | 無料（50,000 MAUまで） |
| **Identity Pool** | 無料（50,000同期操作まで） |
| **MFA SMS** | 使用量課金（$0.00645/SMS） |

**現状**: 7ユーザー → **$0/月**

**スケール想定**:
- 1,000 MAU: $0/月
- 10,000 MAU: $0/月
- 50,000 MAU: $0/月
- 100,000 MAU: $2,750/月（超過分: $0.0055/MAU）

### API Gateway料金

| 項目 | 月間料金 |
|------|----------|
| **REST API リクエスト** | $3.50/百万リクエスト |
| **データ転送** | $0.114/GB（アウトバウンド） |

**現状想定** (1,000リクエスト/日):
- リクエスト: 30,000/月 → **$0.11/月**
- データ転送: 1GB/月 → **$0.11/月**
- **合計**: **$0.22/月**

**スケール想定** (100,000リクエスト/日):
- リクエスト: 3,000,000/月 → **$10.50/月**
- データ転送: 100GB/月 → **$11.40/月**
- **合計**: **$21.90/月**

### 合計コスト（Phase 1追加分のみ）

- **現状**: **$0.22/月**（≒ ¥33/月）
- **1万ユーザー規模**: **$21.90/月**（≒ ¥3,285/月）

---

## 🧪 テスト結果

### 実施したテスト

#### 1. ユーザー移行テスト
- ✅ ドライラン（`--dry-run`）実行成功
- ✅ 単一ユーザー移行（`--user=1`）成功
- ✅ 全ユーザー一括移行（7名）成功
- ✅ DB更新確認（cognito_sub, auth_provider）
- ✅ 重複移行防止（`cognito_sub`既存チェック）

#### 2. Terraform デプロイテスト
- ✅ `terraform plan`検証（37リソース）
- ✅ `terraform apply`実行成功
- ✅ リソース作成確認（Cognitoコンソール、API Gatewayコンソール）
- ✅ 出力値取得（User Pool ID, Client IDs等）

#### 3. 環境変数テスト
- ✅ `.env`設定確認
- ✅ `config:clear`実行
- ✅ 環境変数読み込み確認

### 未実施のテスト（今後実施）

- ⏳ フロントエンドログインフロー（CognitoAuthService.login()）
- ⏳ JWTトークン検証（VerifyCognitoTokenミドルウェア）
- ⏳ API Gateway経由のリクエスト（Cognito Authorizer動作確認）
- ⏳ トークンリフレッシュフロー
- ⏳ MFA設定・認証フロー
- ⏳ パスワードリセットフロー

---

## 📝 運用手順

### 日常運用

#### 新規ユーザー登録

**フロントエンド（推奨）**:
```javascript
import { CognitoAuthService } from '@/auth/cognito';

const authService = new CognitoAuthService();
await authService.register(email, password, {
  name: displayName,
  'custom:timezone': 'Asia/Tokyo',
  'custom:is_admin': 'false',
});
```

**バックエンド（管理者用）**:
```bash
# Cognitoに作成（一時パスワード生成）
aws cognito-idp admin-create-user \
  --user-pool-id ap-northeast-1_O2zUaaHEM \
  --username user@example.com \
  --user-attributes Name=email,Value=user@example.com Name=name,Value="ユーザー名"

# DBに手動登録（cognito_sub取得後）
php artisan tinker
>>> User::create(['email' => 'user@example.com', 'cognito_sub' => 'xxx', 'auth_provider' => 'cognito']);
```

#### ユーザー削除

```bash
# Cognitoから削除
aws cognito-idp admin-delete-user \
  --user-pool-id ap-northeast-1_O2zUaaHEM \
  --username user@example.com

# DBから削除（ソフトデリート）
php artisan tinker
>>> User::where('email', 'user@example.com')->delete();
```

#### パスワードリセット（管理者操作）

```bash
aws cognito-idp admin-set-user-password \
  --user-pool-id ap-northeast-1_O2zUaaHEM \
  --username user@example.com \
  --password "NewPassword123!" \
  --permanent
```

### 監視・ログ

#### Cognito CloudWatch Logs

- **ログストリーム**: `aws/cognito/userpools/ap-northeast-1_O2zUaaHEM`
- **ログイベント**: ログイン、登録、MFA、パスワード変更

#### API Gateway CloudWatch Logs

- **ログストリーム**: `API-Gateway-Execution-Logs_7go6joczpi/production`
- **ログイベント**: リクエスト/レスポンス、認証失敗、スロットリング

#### Laravelログ

```bash
# 今日のログ（日次ログファイル）
docker compose exec app tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Cognito関連ログのみ
docker compose exec app grep "Cognito" storage/logs/laravel-$(date +%Y-%m-%d).log
```

### バックアップ・リカバリ

#### Cognito User Pool バックアップ

**注意**: CognitoにはネイティブバックアップなしAPI経由で全ユーザーエクスポート必要

```bash
# 全ユーザーリスト取得（管理用）
aws cognito-idp list-users \
  --user-pool-id ap-northeast-1_O2zUaaHEM \
  --output json > cognito_users_backup_$(date +%Y%m%d).json
```

#### データベースバックアップ

usersテーブルの`cognito_sub`カラムがCognitoとの紐付けキー

```bash
# PostgreSQLバックアップ（usersテーブルのみ）
docker compose exec db pg_dump -U laravel_user -d laravel_db -t users > users_backup_$(date +%Y%m%d).sql
```

---

## 🚀 次のステップ（Phase 1.5 - 並行運用期間）

### 未完了タスク

#### Task 7: Portal CMS Cognito統合

**ファイル**: `lambda/portal-cms/index.js`

**実装内容**:
- 管理エンドポイント（`/admin/portal/*`）へのJWT検証追加
- JWKS検証ロジック実装（Laravelミドルウェアと同様）
- `custom:is_admin`属性チェック

**優先度**: 中

#### Task 8: 並行運用セットアップ

**実装内容**:
1. **ミドルウェア拡張**:
   - Breeze セッション認証 OR Cognito JWT認証の両対応
   - `auth`ミドルウェアで両方チェック
   
2. **ルート設定**:
   - 既存ルート: Breezeセッション認証維持
   - 新規APIルート: `cognito`ミドルウェア使用

3. **移行計画書作成**:
   - 2週間の並行運用期間設定
   - 段階的ロールアウト戦略
   - ロールバック手順
   - 監視メトリクス（認証成功率、エラー率）

**優先度**: 高

### Phase 2以降の計画

#### Phase 2: フロントエンドUI統合（Week 1-2）

- ログイン画面のCognito統合
- ユーザー登録フローの実装
- パスワードリセット画面
- MFA設定画面
- プロフィール画面（Cognito属性編集）

#### Phase 3: 並行運用・検証（Week 3-4）

- 全ユーザーへの告知
- Breeze/Cognito両認証の並行運用
- エラー監視・対応
- ユーザーフィードバック収集

#### Phase 4: Breeze削除（Week 5-6）

- Breezeルート削除
- セッション認証コード削除
- `auth_provider='breeze'`データクリーンアップ
- パフォーマンステスト

---

## 📚 参考資料

### ドキュメント

- [IAM Permission Update Request (Cognito)](../terraform/IAM_PERMISSION_UPDATE_REQUEST_COGNITO.md)
- [MyTeacher - copilot-instructions.md](../../.github/copilot-instructions.md)
- [Microservices Migration Plan](../../definitions/microservices-migration-plan.md)

### AWS公式ドキュメント

- [Amazon Cognito User Pools](https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-user-identity-pools.html)
- [Amazon Cognito Identity Pools](https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-identity.html)
- [API Gateway Cognito Authorizers](https://docs.aws.amazon.com/apigateway/latest/developerguide/apigateway-integrate-with-cognito.html)
- [JWT Tokens in Cognito](https://docs.aws.amazon.com/cognito/latest/developerguide/amazon-cognito-user-pools-using-tokens-with-identity-providers.html)

### ライブラリ

- [firebase/php-jwt](https://github.com/firebase/php-jwt) - PHP JWT検証ライブラリ
- [amazon-cognito-identity-js](https://github.com/aws-amplify/amplify-js/tree/main/packages/amazon-cognito-identity-js) - フロントエンドCognito SDK

---

## ✅ 完了確認

- [x] Cognito User Pool & Identity Pool作成
- [x] API Gateway with Cognito Authorizer作成
- [x] JWT検証ミドルウェア実装
- [x] フロントエンドCognito認証サービス実装
- [x] ユーザー移行コマンド実装
- [x] 全ユーザー（7名）移行完了
- [x] usersテーブルスキーマ拡張
- [x] 環境変数設定
- [x] Terraform構成整備
- [x] IAM権限設定
- [x] トラブルシューティングドキュメント作成

**Phase 1完了日**: 2025年11月25日

---

## 👥 実装担当

- **インフラ構築**: GitHub Copilot + infrauser (AWS)
- **バックエンド実装**: GitHub Copilot
- **フロントエンド実装**: GitHub Copilot
- **ユーザー移行**: GitHub Copilot
- **ドキュメント作成**: GitHub Copilot

---

**次回レポート**: Phase 1.5完了後（並行運用期間終了時）
