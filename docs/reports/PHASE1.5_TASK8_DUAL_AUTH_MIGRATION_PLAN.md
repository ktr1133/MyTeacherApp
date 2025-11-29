# Phase 1.5 - Task 8: Breeze + Cognito 並行運用移行計画書

**作成日**: 2025年11月26日  
**フェーズ**: Phase 1.5（並行運用期間）  
**期間**: 2週間（2025年12月1日〜12月14日予定）  
**ステータス**: 🟡 実装完了・運用開始待ち

---

## 📋 目次

1. [概要](#概要)
2. [並行運用アーキテクチャ](#並行運用アーキテクチャ)
3. [実装内容](#実装内容)
4. [段階的ロールアウト戦略](#段階的ロールアウト戦略)
5. [監視メトリクス](#監視メトリクス)
6. [ロールバック手順](#ロールバック手順)
7. [トラブルシューティング](#トラブルシューティング)
8. [成功基準](#成功基準)

---

## 概要

### 目的

Phase 1で構築したAmazon Cognito認証基盤を本格運用に移行するため、既存のLaravel Breeze（セッションベース認証）と新しいCognito JWT認証を**2週間並行運用**します。この期間中に:

1. ✅ 既存ユーザー（7名）がBreezeで引き続きアクセス可能
2. ✅ 新規ユーザーはCognito認証でアクセス可能
3. ✅ 両認証方式の動作を監視し、問題を検出
4. ✅ Phase 2（フロントエンドUI統合）の準備完了

### 現状と目標

| 項目 | Phase 1完了時 | Phase 1.5並行運用後 | Phase 2以降 |
|------|--------------|-------------------|------------|
| **認証方式** | Breeze（セッション）のみ | Breeze + Cognito両対応 | Cognito（JWT）のみ |
| **既存ユーザー** | Breezeでログイン | Breezeでログイン継続 | Cognitoに完全移行 |
| **新規ユーザー** | Breezeで登録 | Cognitoで登録 | Cognitoで登録 |
| **API認証** | Sanctum（トークン） | Cognito JWT | Cognito JWT |
| **移行状況** | 7名Cognito登録済み | 並行運用検証 | Breeze削除 |

---

## 並行運用アーキテクチャ

### 認証フロー図

```
┌─────────────────────────────────────────────────────────────────┐
│                        クライアント                               │
│  ┌────────────┐              ┌────────────┐                    │
│  │ 既存ユーザー │              │ 新規ユーザー │                    │
│  │ (Breeze)  │              │ (Cognito) │                    │
│  └─────┬──────┘              └─────┬──────┘                    │
└────────┼─────────────────────────┼────────────────────────────┘
         │                         │
         │ セッションCookie        │ Bearer JWT Token
         │                         │
         ▼                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                  DualAuthMiddleware                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 1. Breezeセッション認証チェック                             │  │
│  │    Auth::guard('web')->check()                          │  │
│  │    ✅ 成功 → リクエスト通過                               │  │
│  │    ❌ 失敗 → 次へ                                         │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │ 2. Cognito JWT認証チェック                                │  │
│  │    Bearer Token取得 → JWKS検証 → クレーム検証              │  │
│  │    ✅ 成功 → リクエスト通過                               │  │
│  │    ❌ 失敗 → 次へ                                         │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │ 3. 両方失敗                                               │  │
│  │    401 Unauthorized または ログインページリダイレクト        │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Laravelアプリケーション                        │
│  - ダッシュボード                                                │
│  - タスク管理                                                    │
│  - アバター機能                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### ルート構成

| ルート種別 | ミドルウェア | 用途 | 対象ユーザー |
|-----------|------------|------|------------|
| **既存Webルート** | `auth` (Breeze) | 既存画面 | 既存ユーザー（Breeze） |
| **新規APIルート** | `cognito` | 新規API（v1） | 新規ユーザー（Cognito） |
| **並行運用ルート** | `dual.auth` | テスト・検証用（v1/dual） | 両方 |
| **レガシーAPI** | `auth:sanctum` | 既存API（Phase 2削除予定） | 既存ユーザー |

---

## 実装内容

### 1. DualAuthMiddleware（実装済み）

**ファイル**: `app/Http/Middleware/DualAuthMiddleware.php`

**機能**:
- ✅ Breezeセッション認証チェック（優先）
- ✅ Cognito JWT認証チェック（次点）
- ✅ 両方失敗時は401または/loginリダイレクト
- ✅ 認証成功/失敗ログ記録（監視用）

**主要メソッド**:
```php
public function handle(Request $request, Closure $next, ?string $guard = null): Response
{
    // 1. Breezeセッション認証
    if (Auth::guard($guard)->check()) {
        Log::info('DualAuth: Breeze session authenticated', [...]);
        return $next($request);
    }

    // 2. Cognito JWT認証
    if ($token = $request->bearerToken()) {
        $decoded = $this->verifyToken($token);
        // ユーザーマッピング
        $user = User::where('cognito_sub', $decoded['sub'])->first();
        if ($user) {
            Log::info('DualAuth: Cognito JWT authenticated', [...]);
            return $next($request);
        }
    }

    // 3. 認証失敗
    Log::warning('DualAuth: Authentication failed', [...]);
    return response()->json(['error' => 'Unauthenticated'], 401);
}
```

### 2. ルート設定（実装済み）

**ファイル**: `routes/api.php`

```php
// Cognito JWT専用（新規API）
Route::prefix('v1')->middleware(['cognito'])->group(function () {
    Route::get('/user', ...)->name('api.v1.user');
});

// Breeze + Cognito並行運用（Phase 1.5期間限定）
Route::prefix('v1/dual')->middleware(['dual.auth'])->group(function () {
    Route::get('/user', ...)->name('api.v1.dual.user');
});

// レガシーAPI（Phase 2削除予定）
Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', ...);
    Route::post('/tasks/propose', ...);
});
```

**ファイル**: `bootstrap/app.php`

```php
$middleware->alias([
    'check.tokens' => \App\Http\Middleware\CheckTokenBalance::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'cognito' => \App\Http\Middleware\VerifyCognitoToken::class,
    'dual.auth' => \App\Http\Middleware\DualAuthMiddleware::class, // ★ 追加
]);
```

### 3. 既存ルートの保持

**ファイル**: `routes/web.php`

```php
// 既存ルートはすべてBreezeセッション認証を継続
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', IndexTaskAction::class)->name('dashboard');
    // ... その他すべての既存ルート
});
```

**重要**: 既存のWebルートは**一切変更しない**。ユーザーへの影響ゼロを保証。

---

## 段階的ロールアウト戦略

### Week 1（12月1日〜12月7日）: 内部検証期間

#### Day 1-2: 管理者テスト
- **対象**: 管理者アカウント（admin@my-teacher-app.com）のみ
- **テスト内容**:
  1. Breezeセッションでログイン → ダッシュボードアクセス
  2. Cognito JWTでAPI呼び出し（`/api/v1/user`）
  3. 並行運用API呼び出し（`/api/v1/dual/user`）
  4. ログ確認（CloudWatch Logs）
- **成功基準**: すべてのテストケースが成功

#### Day 3-5: 既存ユーザーテスト（段階的）
- **対象**: 既存7ユーザーのうち2-3名
- **テスト内容**:
  1. 通常操作（Breezeセッション維持）
  2. Cognito再ログインテスト（任意協力）
  3. API動作確認
- **成功基準**: エラーレート < 1%、認証成功率 > 99%

#### Day 6-7: 全ユーザー展開準備
- **対象**: 全7ユーザー
- **実施内容**:
  1. 監視ダッシュボード設定
  2. アラート閾値調整
  3. ドキュメント整備
- **成功基準**: 問題検出ゼロ

### Week 2（12月8日〜12月14日）: 本格運用監視期間

#### Day 8-10: 通常運用監視
- **監視項目**:
  - 認証成功率: 目標 > 99.5%
  - エラーレート: 目標 < 0.5%
  - レスポンスタイム: 目標 < 200ms（認証処理）
  - ログボリューム: 異常検知

#### Day 11-12: 新規ユーザー受け入れテスト
- **テスト内容**:
  1. Cognitoで新規ユーザー登録
  2. JWT認証で全機能アクセス
  3. BreezeユーザーとCognitoユーザーの混在動作確認

#### Day 13-14: Phase 2移行準備
- **実施内容**:
  1. 並行運用期間の総括レポート作成
  2. 検出された問題の修正確認
  3. Phase 2（フロントエンドUI統合）の計画最終確認
  4. Breeze削除のタイムライン策定

---

## 監視メトリクス

### 1. 認証メトリクス（CloudWatch Logs Insights）

#### 認証成功率
```sql
fields @timestamp, @message
| filter @message like /DualAuth: (Breeze session|Cognito JWT) authenticated/
| stats count() as success_count by bin(5m)
```

**目標**: > 99.5%

#### 認証失敗率
```sql
fields @timestamp, @message
| filter @message like /DualAuth: Authentication failed/
| stats count() as failure_count by bin(5m)
```

**アラート閾値**: > 5%（5分間）

#### 認証方式別内訳
```sql
fields @timestamp, @message
| filter @message like /DualAuth:/
| parse @message "DualAuth: * authenticated" as auth_type
| stats count() by auth_type
```

**期待値**:
- Week 1: Breeze 80-90%, Cognito 10-20%
- Week 2: Breeze 70-80%, Cognito 20-30%

### 2. パフォーマンスメトリクス

| メトリクス | 測定方法 | 目標値 | アラート閾値 |
|-----------|---------|--------|-------------|
| **認証処理時間** | ミドルウェア実行時間 | < 200ms | > 500ms |
| **JWKS取得時間** | HTTP::get() duration | < 100ms | > 300ms |
| **DB照会時間** | User::where() duration | < 50ms | > 150ms |
| **全体レスポンス** | ALB TargetResponseTime | < 500ms | > 1000ms |

### 3. エラー監視

#### Cognito JWT検証エラー
```sql
fields @timestamp, @message
| filter @message like /JWT decode failed|JWKS retrieval failed|Invalid token_use/
| stats count() as error_count by bin(5m)
```

**アラート**: > 10件/5分

#### ユーザーマッピングエラー
```sql
fields @timestamp, @message
| filter @message like /Cognito user not found in database/
| fields cognito_sub, cognito_email
```

**対応**: 移行漏れの可能性 → 手動マイグレーション実施

### 4. セキュリティ監視

#### 不正アクセス試行
```sql
fields @timestamp, ip, url
| filter @message like /Authentication failed/
| stats count() as attempts by ip
| filter attempts > 10
```

**アラート**: 同一IP から 10回以上の失敗

#### 無効トークン使用
```sql
fields @timestamp, @message
| filter @message like /Invalid or expired token/
| stats count() as invalid_tokens by bin(1h)
```

**アラート**: > 50件/時間

---

## ロールバック手順

### ロールバック条件（いずれか該当時）

1. ❌ 認証成功率 < 95%が30分以上継続
2. ❌ 致命的なセキュリティ脆弱性の発見
3. ❌ サービス全体のダウンタイム発生
4. ❌ データ整合性の問題発生

### ロールバック手順

#### Step 1: 緊急対応（5分以内）

```bash
# 1. DualAuthMiddlewareを無効化（bootstrap/app.php）
cd /home/ktr/mtdev/laravel

# コメントアウト
# 'dual.auth' => \App\Http\Middleware\DualAuthMiddleware::class,

# 2. 設定キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 3. アプリケーション再起動（ECS Fargate）
aws ecs update-service \
  --cluster myteacher-production \
  --service myteacher-production-app \
  --force-new-deployment \
  --region ap-northeast-1
```

#### Step 2: 影響範囲確認（10分以内）

```bash
# 1. ログ確認
aws logs tail /aws/ecs/myteacher-production-app \
  --follow \
  --filter-pattern "ERROR|CRITICAL|Authentication failed"

# 2. メトリクス確認
aws cloudwatch get-metric-statistics \
  --namespace AWS/ECS \
  --metric-name TargetResponseTime \
  --dimensions Name=ServiceName,Value=myteacher-production-app \
  --start-time 2025-12-01T00:00:00Z \
  --end-time 2025-12-01T12:00:00Z \
  --period 300 \
  --statistics Average
```

#### Step 3: ユーザー通知（15分以内）

```bash
# 管理ポータルから全ユーザーに通知
php artisan notification:create \
  --type all_users \
  --title "メンテナンス通知" \
  --message "認証システムの調整のため、一時的に旧システムに戻しました。"
```

#### Step 4: 根本原因分析（24時間以内）

1. エラーログの詳細分析
2. 再現テスト環境構築
3. 修正パッチ作成
4. 再展開計画策定

### ロールバック後の再開条件

- ✅ 根本原因の特定と修正完了
- ✅ テスト環境での100%成功
- ✅ 影響を受けたユーザーへの説明
- ✅ 監視体制の強化

---

## トラブルシューティング

### 問題1: Breezeセッションが切れる

**症状**: 既存ユーザーが突然ログアウトされる

**原因候補**:
- セッションストア（Redis）の問題
- Cookie設定の不備
- タイムアウト設定

**対応**:
```bash
# 1. Redisセッション確認
redis-cli -h redis.myteacher.internal KEYS "laravel_session:*"

# 2. セッション設定確認
php artisan config:show session

# 3. Cookieドメイン確認
# .env
SESSION_DOMAIN=.my-teacher-app.com
```

### 問題2: Cognito JWT検証が失敗する

**症状**: API呼び出しが401エラーを返す

**原因候補**:
- トークン有効期限切れ（60分）
- JWKS取得失敗
- クレーム検証エラー

**対応**:
```bash
# 1. トークンデコードテスト
php artisan tinker
>>> $token = "eyJraWQ...";
>>> \Firebase\JWT\JWT::decode($token, ...);

# 2. JWKS取得確認
curl https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_O2zUaaHEM/.well-known/jwks.json

# 3. ログ確認
tail -f storage/logs/laravel.log | grep "JWT decode failed"
```

### 問題3: ユーザーマッピングエラー

**症状**: `Cognito user not found in database`

**原因**: Phase 1移行漏れ

**対応**:
```bash
# 1. 該当ユーザーを手動移行
php artisan cognito:migrate-users --user={user_id} --force

# 2. 移行確認
php artisan tinker
>>> User::where('cognito_sub', 'xxx')->first();
```

### 問題4: パフォーマンス劣化

**症状**: レスポンスタイムが500ms超過

**原因候補**:
- JWKS取得のタイムアウト
- DB照会のN+1問題
- キャッシュ無効化

**対応**:
```bash
# 1. クエリログ有効化
DB::enableQueryLog();

# 2. キャッシュ確認
php artisan cache:table
php artisan cache:check

# 3. Redisキャッシュクリア
php artisan cache:flush
```

---

## 成功基準

### 並行運用期間終了時の必達条件

| 項目 | 目標値 | 実績値（記入欄） |
|------|--------|----------------|
| **認証成功率** | > 99.5% | _________% |
| **エラーレート** | < 0.5% | _________% |
| **認証処理時間** | < 200ms | _________ms |
| **ユーザー満足度** | クレーム0件 | _________件 |
| **セキュリティインシデント** | 0件 | _________件 |
| **ダウンタイム** | 0分 | _________分 |

### Phase 2移行可否判定

#### ✅ 移行可能条件（すべて満たす必要あり）

1. ✅ 認証成功率 > 99.5%
2. ✅ エラーレート < 0.5%
3. ✅ セキュリティインシデント 0件
4. ✅ 既存ユーザーから重大な問題報告なし
5. ✅ 監視体制の確立
6. ✅ ロールバック手順の検証完了

#### ❌ 移行延期条件（いずれか該当）

1. ❌ 認証成功率 < 99%
2. ❌ 致命的なバグの未修正
3. ❌ セキュリティ脆弱性の発見
4. ❌ ロールバックの実施（1回でも）

---

## Phase 2への移行計画

### Phase 2概要

**期間**: Week 3-4（12月15日〜12月28日）

**主要タスク**:
1. ログイン画面のCognito統合（amazon-cognito-identity-js使用）
2. ユーザー登録フローの実装
3. パスワードリセット画面
4. MFA設定画面
5. プロフィール画面（Cognito属性編集）

### Phase 2開始前チェックリスト

- [ ] Phase 1.5 Task 8完了レポート作成
- [ ] 並行運用期間の全メトリクス収集
- [ ] 問題点の洗い出しと対策完了
- [ ] フロントエンド開発環境準備
- [ ] CognitoAuthService実装計画確定
- [ ] ユーザー通知の準備

---

## 関連ドキュメント

- [Phase 1 完了レポート](./PHASE1_COMPLETION_REPORT.md) - Cognito基盤構築
- [Phase 1.5 Task 7完了レポート](./PHASE1.5_TASK7_PORTAL_CMS_COGNITO_INTEGRATION.md) - Portal CMS統合
- [Microservices Migration Plan](../../definitions/microservices-migration-plan.md) - 全体アーキテクチャ
- [Database Schema](../../definitions/database-schema.md) - usersテーブル定義
- [AWS Cognito Documentation](https://docs.aws.amazon.com/cognito/latest/developerguide/)

---

## 変更履歴

| 日付 | バージョン | 変更内容 | 更新者 |
|------|-----------|---------|--------|
| 2025-11-26 | 1.0.0 | 初版作成 | AI Development Assistant |

---

**レポート作成者**: AI Development Assistant  
**最終更新**: 2025年11月26日
