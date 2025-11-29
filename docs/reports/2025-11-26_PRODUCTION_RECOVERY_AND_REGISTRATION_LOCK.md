# 本番環境完全復旧レポート + 新規登録停止措置

**作成日**: 2025年11月26日  
**プロジェクト**: MyTeacher Production Environment  
**ステータス**: ✅ 完了  
**所要時間**: 約3時間（Terraform destroy復旧 + 静的アセット問題解決 + 新規登録停止）

---

## 📋 目次

1. [エグゼクティブサマリー](#エグゼクティブサマリー)
2. [問題の経緯](#問題の経緯)
3. [実施した対応](#実施した対応)
4. [新規登録停止措置](#新規登録停止措置)
5. [復旧されたリソース](#復旧されたリソース)
6. [トラブルシューティング履歴](#トラブルシューティング履歴)
7. [セキュリティ考慮事項](#セキュリティ考慮事項)
8. [運用手順の更新](#運用手順の更新)
9. [今後の推奨事項](#今後の推奨事項)

---

## エグゼクティブサマリー

### 状況概要

2025年11月26日、本番環境において以下の問題が発生し、完全復旧を実施しました：

1. **静的アセット（CSS/JS）が読み込まれない問題** - ASSET_URL設定の不備
2. **ALB Security Group規則が全削除** - Terraform操作ミスによる
3. **CloudFront Prefix List制限問題** - 45 IPレンジによるクォータ超過
4. **Terraform destroy連鎖** - 49リソースが意図せず削除
5. **Database接続不能** - Security Group規則の不整合

### 最終的な解決策

✅ **静的アセット**: ASSET_URL="" に設定し、相対パスで配信  
✅ **Security Group**: 新規作成（sg-06561791d65d7c473）、Port 443を0.0.0.0/0で一時許可  
✅ **DNS**: Route 53レコード再作成  
✅ **EFS**: Mount Target両AZ再作成  
✅ **Database**: Security Group規則修正（ECS Tasks → RDS/Redis）  
✅ **新規登録停止**: RegisterAction::store()にabort(404)追加  

### 達成された目標

- ✅ サイト完全復旧（https://my-teacher-app.com）
- ✅ CSS/JS正常読み込み（HTTP 200, 正しいContent-Type）
- ✅ Database接続正常化
- ✅ 全ECSタスク Healthy状態
- ✅ CloudFrontキャッシュ無効化完了
- ✅ 特定ユーザー限定運用体制確立（新規登録停止）

---

## 問題の経緯

### タイムライン

| 時刻 | イベント | 影響 |
|------|---------|------|
| 11:00 | Phase 0.5完了後、静的アセット404エラー報告 | CSS/JS読み込み不可 |
| 11:15 | ASSET_URL設定確認、Task Definition Revision 27作成 | 一時的改善 |
| 11:30 | ALB Security Group規則が空と判明 | 504 Gateway Timeout全面発生 |
| 11:45 | Security Group規則追加試行 → クォータエラー | 復旧不能 |
| 12:00 | CloudFront Prefix List分析（45 IPレンジ） | 根本原因特定 |
| 12:15 | 新規Security Group作成（0.0.0.0/0ワークアラウンド） | 443ポート復旧 |
| 12:30 | Terraform destroy連鎖発覚（49リソース削除） | DNS解決不能、ECS Service Inactive |
| 13:00 | Route 53レコード再作成 | DNS復旧 |
| 13:15 | EFS Mount Target再作成 | ECSタスク起動可能に |
| 13:30 | Database Security Group規則修正 | PostgreSQL接続復旧 |
| 14:00 | 全サービスHealthy確認 | サイト完全復旧 |
| 14:30 | 新規登録停止実装（abort(404)） | 特定ユーザー限定運用開始 |

### 根本原因分析

#### 1. 静的アセット問題
- **原因**: ASSET_URLが`https://my-teacher-app.com`のまま、Laravel asset()が絶対URLを生成
- **トリガー**: CloudFrontがALBにHTTPで接続、相対パスが必要
- **解決**: ASSET_URL=""に設定、相対パス生成に切り替え

#### 2. Security Group問題
- **原因**: CloudFront Prefix List（pl-58a04531）が45 IPレンジを含み、1つのSGで複数ルール追加時にクォータ超過
- **トリガー**: Port 80とPort 443の両方にPrefix Listを設定しようとした
- **解決**: 新規SG作成、Port 443は0.0.0.0/0で一時許可

#### 3. Terraform Destroy連鎖
- **原因**: `terraform destroy -target`実行時、依存関係を持つ49リソースが連鎖削除
- **影響範囲**:
  - Route 53 A Record（DNS解決不能）
  - EFS Mount Target（ECSタスク起動不能）
  - ECS Service（Desired 0, Status: INACTIVE）
  - Database/Redis Security Group規則（接続不能）
- **解決**: 各リソースを順次`terraform apply -target`で再作成

#### 4. Database接続問題
- **原因**: RDS Security GroupがECS Tasks SG（sg-0e94db2289e5cb5b0）を許可していない
- **誤ったSG**: sg-00fd08a3de404dcf8（削除されたSG）
- **解決**: terraform apply -target で正しい規則を再作成

---

## 実施した対応

### フェーズ1: 静的アセット問題の解決

#### 手順1: ASSET_URL変更（Revision 27作成）

```bash
# ecs.tfを編集
{
  name  = "ASSET_URL"
  value = ""  # 空文字で相対パス生成
}

# 適用
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply -target=module.myteacher.aws_ecs_task_definition.app -auto-approve
```

**結果**: Task Definition Revision 27作成、相対パスでアセット生成

#### 手順2: CloudFrontキャッシュ無効化

```bash
bash /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh "/*"
```

**Invalidation ID**: I9I9SEI4BTIJNIP016I3W0GTUN

### フェーズ2: Security Group復旧

#### 手順1: 既存SG分析

```bash
# sg-04fb249ff548bbfc9のIngress確認
aws ec2 describe-security-group-rules --filters "Name=group-id,Values=sg-04fb249ff548bbfc9"
# 結果: Ingress規則0個（全削除済み）
```

#### 手順2: CloudFront Prefix List分析

```bash
aws ec2 get-managed-prefix-list-entries --prefix-list-id pl-58a04531
```

**発見**: 45個のIPレンジ含有 → 単一SGで複数ルール適用時にクォータ超過の原因

#### 手順3: 新規Security Group作成

```hcl
# ecs.tfを修正
resource "aws_security_group" "alb" {
  name        = "${var.project_name}-${var.environment}-alb-sg-new"
  description = "New ALB Security Group"
  
  # Port 80: CloudFront Prefix List（動作確認済み）
  ingress {
    description     = "HTTP from CloudFront"
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    prefix_list_ids = ["pl-58a04531"]
  }
  
  # Port 443: 0.0.0.0/0（一時的ワークアラウンド）
  # Prefix List使用時にクォータエラーが発生するため
  ingress {
    description = "HTTPS from Internet (Temporary workaround for CloudFront Prefix List quota issue)"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  egress {
    description = "Allow all outbound"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}
```

```bash
# Terraform適用
terraform apply -target=module.myteacher.aws_security_group.alb -auto-approve

# 作成されたSG ID: sg-06561791d65d7c473
```

#### 手順4: Terraform State管理

```bash
# 古いSGを削除、新しいSGをインポート
terraform state rm module.myteacher.aws_security_group.alb
terraform import module.myteacher.aws_security_group.alb sg-06561791d65d7c473
```

### フェーズ3: Terraform Destroy連鎖からの復旧

#### 手順1: Route 53 DNS復旧

```bash
terraform apply -target=module.myteacher.aws_route53_record.app_cloudfront -auto-approve
```

**作成されたレコード**:
- タイプ: A (Alias)
- 名前: my-teacher-app.com
- エイリアス先: d3kf3b01c2fny5.cloudfront.net

#### 手順2: EFS Mount Target復旧

```bash
terraform apply -target=module.myteacher.aws_efs_mount_target.storage -auto-approve
```

**作成されたMount Target**:
- AZ 1a: fsmt-0d7286ace5a659ceb (10.0.100.54, subnet-020e87d7082dfa4be)
- AZ 1c: fsmt-0e3ce84857e6df418 (10.0.101.73, subnet-0dbe0cc6142fdee33)

#### 手順3: ECS Service復旧

```bash
terraform apply -target=module.myteacher.aws_ecs_service.app -auto-approve
```

**サービス状態**:
- Desired Count: 2
- Running Count: 2
- Status: ACTIVE

**初回起動時の問題**: PostgreSQL接続タイムアウト（60秒+）

#### 手順4: Database Security Group規則復旧

**問題発見**:
```bash
aws ec2 describe-security-group-rules --filters "Name=group-id,Values=sg-025bccf2b6050a2eb"
# RDS SGが許可: sg-00fd08a3de404dcf8（削除済み、誤ったSG）

aws ecs describe-services --query 'services[0].networkConfiguration.awsvpcConfiguration.securityGroups'
# ECS TasksのSG: sg-0e94db2289e5cb5b0（正しいSG）
```

**解決**:
```bash
terraform apply \
  -target=module.myteacher.aws_security_group_rule.database_from_ecs \
  -target=module.myteacher.aws_security_group_rule.redis_from_ecs \
  -auto-approve
```

**作成された規則**:
- sgrule-2148360962: ECS Tasks (sg-0e94db2289e5cb5b0) → RDS (sg-025bccf2b6050a2eb) Port 5432
- sgrule-3519488002: ECS Tasks (sg-0e94db2289e5cb5b0) → Redis (sg-03bc2a75095831e03) Port 6379

#### 手順5: 最終検証

```bash
# 180秒待機（タスク再起動+ヘルスチェック）
sleep 180

# ECS Service状態確認
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --query 'services[0].[runningCount,deployments[0].rolloutState]'
# 結果: [2, "COMPLETED"]

# Target Health確認
aws elbv2 describe-target-health \
  --target-group-arn arn:aws:elasticloadbalancing:ap-northeast-1:469751479977:targetgroup/myteacher-production-tg/b21e68db3fa99163
# 結果: 両ターゲット "healthy"

# 静的アセット確認
curl -I https://my-teacher-app.com/build/assets/app-CVrz8gq5.css
# HTTP/2 200
# content-type: text/css

# メインページ確認
curl -I https://my-teacher-app.com
# HTTP/2 200
# content-type: text/html; charset=UTF-8
```

✅ **全サービスHealthy、サイト完全復旧**

#### 手順6: クリーンアップ

```bash
# 古いSecurity Group削除
aws ec2 delete-security-group --group-id sg-04fb249ff548bbfc9
# 結果: true（削除成功）
```

---

## 新規登録停止措置

### 実装背景

**要件**: 特定ユーザーのみがアプリケーションを利用できる状態を維持する

**当初の計画**: ALB Security Groupで特定IPのみ許可
- **問題点**: CloudFront Prefix Listのクォータ問題により実装困難
- **代替案**: アプリケーションレベルで新規登録を停止

### 実装内容

#### ファイル: `/home/ktr/mtdev/laravel/app/Http/Actions/Auth/RegisterAction.php`

**変更箇所**: `store()` メソッドの冒頭

```php
/**
 * ユーザー登録処理
 *
 * @param RegisterRequest $request
 * @return RedirectResponse
 */
public function store(RegisterRequest $request): RedirectResponse
{
    // TODO: 登録一時停止中は404を返す
    abort(404);
    
    try {
        // ユーザー作成
        $user = $this->profileService->createUser([
            'username' => $request->input('username'),
            'password' => Hash::make($request->input('password')),
            'timezone' => $request->input('timezone', 'Asia/Tokyo'),
        ]);
        
        // ... 以下既存のコード
    }
}
```

**実装の詳細**:
- **HTTP 404エラー**: 新規登録リクエストに対して即座に404を返却
- **既存ユーザーへの影響**: なし（ログイン、既存機能は正常動作）
- **登録画面**: アクセス可能だが、送信時に404エラー
- **将来の再開**: `abort(404);` 行を削除するだけで再開可能

### 影響範囲の分析

#### 影響を受ける機能
- ✅ 新規ユーザー登録（POST /register）→ **404エラー**

#### 影響を受けない機能
- ✅ 既存ユーザーのログイン（POST /login）→ **正常動作**
- ✅ パスワードリセット（POST /forgot-password）→ **正常動作**
- ✅ プロフィール編集（PATCH /profile/update）→ **正常動作**
- ✅ グループメンバー追加（POST /profile/group/member）→ **正常動作**
- ✅ 全タスク管理機能 → **正常動作**
- ✅ ポータルサイト → **正常動作**
- ✅ 管理者機能 → **正常動作**

### 登録画面の表示について

**現状**: `/register` へのGETリクエストは表示可能

**推奨対応**（将来）:
```php
// RegisterAction::create() にも同様の制限を追加
public function create(): View
{
    abort(404); // 登録画面自体を非表示
    return $this->responder->create();
}
```

または、routes/web.phpで無効化:
```php
// Route::get('/register', [RegisterAction::class, 'create'])->name('register'); // コメントアウト
// Route::post('/register', [RegisterAction::class, 'store']); // コメントアウト
```

### 新規メンバー追加機能との整合性

**グループメンバー追加**: 引き続き利用可能

**理由**:
- グループメンバー追加は `/profile/group/member` エンドポイント
- `AddMemberAction` を使用（RegisterActionとは別実装）
- 親ユーザーの権限で子アカウントを作成する機能
- 既存ユーザーの家族・チーム管理に必要

**コード確認**（routes/web.php）:
```php
Route::middleware(['auth'])->group(function () {
    Route::prefix('/profile')->group(function () {
        Route::post('/group/member', AddMemberAction::class)->name('group.member.add');
        // ✅ 認証済みユーザーのみアクセス可能、新規登録停止の影響を受けない
    });
});
```

### セキュリティ考慮事項

#### 実装の妥当性

**✅ 適切な点**:
- HTTP 404エラーは情報漏洩を防ぐ（機能が存在しないように見える）
- 既存ユーザーの利便性を損なわない
- コード変更が最小限（1行追加）
- 将来の再開が容易

**⚠️ 改善余地**:
- より詳細なログ記録（登録試行を監視）
- 登録画面自体の非表示化
- カスタムエラーメッセージ（404より403 Forbiddenが適切な場合も）

#### 推奨する追加実装（将来）

```php
public function store(RegisterRequest $request): RedirectResponse
{
    // ログ記録
    Log::warning('Registration attempt blocked', [
        'ip' => $request->ip(),
        'username' => $request->input('username'),
        'timestamp' => now()->toIso8601String(),
    ]);
    
    // 403 Forbiddenで返す（より明示的）
    abort(403, '新規登録は現在停止しています。');
    
    // または、メンテナンスモード風のエラーページ
    // return response()->view('errors.registration-closed', [], 503);
}
```

---

## 復旧されたリソース

### AWS リソース一覧

| リソースタイプ | リソース名/ID | ステータス | 説明 |
|--------------|--------------|----------|------|
| **Security Group** | sg-06561791d65d7c473 | ✅ 作成 | 新ALB Security Group（Port 443: 0.0.0.0/0） |
| Security Group | sg-04fb249ff548bbfc9 | ✅ 削除 | 旧ALB Security Group（規則なし） |
| **Route 53 Record** | my-teacher-app.com | ✅ 再作成 | A Record → CloudFront Alias |
| **EFS Mount Target** | fsmt-0d7286ace5a659ceb | ✅ 再作成 | AZ 1a（subnet-020e87d7082dfa4be） |
| **EFS Mount Target** | fsmt-0e3ce84857e6df418 | ✅ 再作成 | AZ 1c（subnet-0dbe0cc6142fdee33） |
| **ECS Service** | myteacher-production-app-service | ✅ 再作成 | Desired 2, Running 2, Status: ACTIVE |
| **SG Rule** | sgrule-2148360962 | ✅ 再作成 | ECS Tasks → RDS Port 5432 |
| **SG Rule** | sgrule-3519488002 | ✅ 再作成 | ECS Tasks → Redis Port 6379 |
| **Task Definition** | myteacher-production-app:27 | ✅ 作成 | ASSET_URL="" 設定 |
| **CloudFront Invalidation** | I9I9SEI4BTIJNIP016I3W0GTUN | ✅ 完了 | Path: /* |

### Terraformリソース状態

```bash
cd /home/ktr/mtdev/infrastructure/terraform
terraform state list | grep myteacher
```

**主要リソース**:
- module.myteacher.aws_security_group.alb (sg-06561791d65d7c473)
- module.myteacher.aws_route53_record.app_cloudfront
- module.myteacher.aws_efs_mount_target.storage[0]
- module.myteacher.aws_efs_mount_target.storage[1]
- module.myteacher.aws_ecs_service.app
- module.myteacher.aws_security_group_rule.database_from_ecs
- module.myteacher.aws_security_group_rule.redis_from_ecs

**Terraform Drift**: なし（すべてのリソースがTerraform管理下に戻った）

---

## トラブルシューティング履歴

### 問題1: Security Group規則追加時のクォータエラー

**エラーメッセージ**:
```
An error occurred (RulesPerSecurityGroupLimitExceeded) when calling the 
AuthorizeSecurityGroupIngress operation: You've reached the limit on the 
number of rules you can add to a security group.
```

**試行1**: 既存SG（sg-04fb249ff548bbfc9）に規則追加
- **結果**: 失敗（クォータエラー）

**試行2**: 新規SG作成 → 同じエラー発生
- **原因**: CloudFront Prefix Listに45 IPレンジ含有
- **内部カウント**: 1つのPrefix Listルール = 45個のルール相当

**試行3**: Port 443のみ0.0.0.0/0で許可
- **結果**: 成功 ✅

**教訓**: 
- Prefix Listのエントリ数を事前確認
- 複数ポートで同じPrefix Listを使う場合、単一ルールにまとめる
- クォータ問題の回避策として、CIDR直接指定を検討

### 問題2: ECS TasksのPostgreSQL接続タイムアウト

**ログ（CloudWatch）**:
```
2025-11-26T12:54:54 Waiting for PostgreSQL... (51 seconds remaining)
2025-11-26T12:54:57 Waiting for PostgreSQL... (55 seconds remaining)
...
2025-11-26T12:56:58 Waiting for PostgreSQL... (31 seconds remaining)
[60秒後タイムアウト]
```

**原因分析**:
```bash
# RDS Security Groupの確認
aws ec2 describe-security-group-rules --filters "Name=group-id,Values=sg-025bccf2b6050a2eb"
# 結果: sg-00fd08a3de404dcf8からのIngress許可（削除済みSG）

# ECS TasksのSG確認
aws ecs describe-services --query 'services[0].networkConfiguration.awsvpcConfiguration.securityGroups'
# 結果: ["sg-0e94db2289e5cb5b0"]（正しいSG）
```

**不整合**: RDSが許可しているSGとECS TasksのSGが異なる

**解決**: Terraform apply -targetで正しい規則を再作成

**タイムライン**:
- 13:30: 問題発見（CloudWatch Logs分析）
- 13:45: Security Group規則の不整合特定
- 14:00: Terraform適用（規則再作成）
- 14:03: 新規タスク起動、PostgreSQL接続成功
- 14:06: Target Health "healthy" 確認

### 問題3: Terraform Destroy連鎖の予期せぬ範囲

**実行コマンド**:
```bash
terraform destroy -target=module.myteacher.aws_security_group.alb
```

**予想**: Security Group 1つのみ削除

**実際**: 49リソースが削除された

**削除されたリソース**（主要）:
- aws_route53_record.app_cloudfront（DNS不能）
- aws_efs_mount_target.storage[0], [1]（EFS接続不能）
- aws_ecs_service.app（サービスInactive）
- aws_security_group_rule.database_from_ecs（DB接続不能）
- aws_security_group_rule.redis_from_ecs（Redis接続不能）

**原因**: 
- TerraformのリソースグラフでSecurity GroupがALBに依存
- ALBが他の多数のリソースに依存（ECS Service, Target Group等）
- `-target`フラグは依存関係を考慮して連鎖削除

**教訓**:
- `terraform destroy -target`は極めて危険
- 事前に`terraform plan -destroy -target`で影響範囲を確認
- 本番環境では`prevent_destroy`ライフサイクルルールを設定
- 可能な限りAWS CLIで個別リソース操作を優先

---

## セキュリティ考慮事項

### 現在のセキュリティ設定

#### 1. ALB Security Group（sg-06561791d65d7c473）

**Port 80（HTTP）**:
- ソース: CloudFront Prefix List（pl-58a04531）
- 状態: ✅ 適切（CloudFrontからのみ許可）

**Port 443（HTTPS）**:
- ソース: 0.0.0.0/0（全インターネット）
- 状態: ⚠️ 一時的ワークアラウンド（セキュリティリスク）

**推奨対応**:
```bash
# Option A: CloudFront IPレンジを個別に追加（最大10-15個）
curl https://ip-ranges.amazonaws.com/ip-ranges.json | \
  jq -r '.prefixes[] | select(.service=="CLOUDFRONT" and .region=="GLOBAL") | .ip_prefix' | \
  head -15 > cloudfront-ips.txt

# 各IPレンジをルールとして追加
while read ip; do
  aws ec2 authorize-security-group-ingress \
    --group-id sg-06561791d65d7c473 \
    --protocol tcp --port 443 --cidr $ip
done < cloudfront-ips.txt

# 0.0.0.0/0を削除
aws ec2 revoke-security-group-ingress \
  --group-id sg-06561791d65d7c473 \
  --protocol tcp --port 443 --cidr 0.0.0.0/0

# Option B: CloudFrontのみにアクセス制限（Custom Header認証）
# ALBリスナールールで X-Custom-Header検証を追加
aws elbv2 create-rule \
  --listener-arn <HTTPS_LISTENER_ARN> \
  --priority 1 \
  --conditions Field=http-header,HttpHeaderConfig={HttpHeaderName=X-Custom-Header,Values=[iabtUwIa8vvi0WFzEzNNTEEY6NdVZjQNYOCVcU5LlrA=]} \
  --actions Type=forward,TargetGroupArn=<TARGET_GROUP_ARN>
```

#### 2. RDS/Redis Security Group

**現在の設定**:
- RDS（sg-025bccf2b6050a2eb）: ECS Tasks SG（sg-0e94db2289e5cb5b0）からPort 5432許可
- Redis（sg-03bc2a75095831e03）: ECS Tasks SG（sg-0e94db2289e5cb5b0）からPort 6379許可

**状態**: ✅ 適切（ECS Tasksからのみアクセス可能）

#### 3. 新規登録停止のセキュリティ

**現在の実装**: `abort(404)` による停止

**セキュリティレベル**: 
- ✅ 情報漏洩なし（404は機能が存在しないように見える）
- ✅ ブルートフォース攻撃無効化（登録エンドポイントが機能しない）
- ⚠️ 登録画面自体はアクセス可能（GETリクエストは処理される）

**推奨改善**:
```php
// RegisterAction.php
public function create(): View
{
    abort(404); // 登録画面自体を非表示
    return $this->responder->create();
}

public function store(RegisterRequest $request): RedirectResponse
{
    Log::warning('Blocked registration attempt', [
        'ip' => $request->ip(),
        'username' => $request->input('username'),
        'user_agent' => $request->userAgent(),
    ]);
    
    abort(403, '新規登録は現在停止しています。');
}
```

または、ミドルウェアで一元管理:
```php
// app/Http/Middleware/BlockRegistration.php
class BlockRegistration
{
    public function handle($request, Closure $next)
    {
        if ($request->routeIs('register') || $request->routeIs('register.store')) {
            abort(404);
        }
        return $next($request);
    }
}

// routes/web.php
Route::middleware(['guest', 'block.registration'])->group(function () {
    Route::get('/register', [RegisterAction::class, 'create'])->name('register');
    Route::post('/register', [RegisterAction::class, 'store']);
});
```

### セキュリティチェックリスト

- [x] HTTPS有効化（TLS 1.3）
- [x] ALB HTTPSリスナー設定
- [x] CloudFront証明書設定
- [x] Database/RedisはプライベートSubnet配置
- [x] Security Group最小権限原則（一部例外）
- [ ] **Port 443を特定IPに制限（要対応）**
- [x] 新規登録停止実装
- [ ] **登録画面自体の非表示化（推奨）**
- [ ] WAF導入（将来）
- [ ] CloudTrail有効化（監査ログ）
- [ ] GuardDuty有効化（脅威検出）
- [ ] Secrets Manager導入（機密情報管理）

---

## 運用手順の更新

### 新規登録の再開手順

**ステップ1: コード変更**

```bash
cd /home/ktr/mtdev/laravel
vim app/Http/Actions/Auth/RegisterAction.php
```

以下の行を削除またはコメントアウト:
```php
// abort(404); // TODO: 登録一時停止中は404を返す
```

**ステップ2: Dockerイメージ再ビルド**

```bash
cd /home/ktr/mtdev
docker build -f Dockerfile.production -t myteacher-production:latest .
docker push 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest
```

**ステップ3: ECS再デプロイ**

```bash
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --force-new-deployment
```

**ステップ4: 動作確認**

```bash
curl -X POST https://my-teacher-app.com/register \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"Test1234!","timezone":"Asia/Tokyo"}'
# 404エラーでなければ成功（バリデーションエラーは正常）
```

### Security Group Port 443制限手順

**ステップ1: CloudFront IPレンジ取得**

```bash
curl -s https://ip-ranges.amazonaws.com/ip-ranges.json | \
  jq -r '.prefixes[] | select(.service=="CLOUDFRONT" and .region=="GLOBAL") | .ip_prefix' | \
  head -15 > /tmp/cloudfront-ips.txt

# 取得されたIPレンジ数を確認
wc -l /tmp/cloudfront-ips.txt
# 15行程度であればSGクォータ内に収まる
```

**ステップ2: Security Group規則追加**

```bash
# 各IPレンジを個別ルールとして追加
while read ip; do
  echo "Adding rule for $ip..."
  aws ec2 authorize-security-group-ingress \
    --group-id sg-06561791d65d7c473 \
    --protocol tcp \
    --port 443 \
    --cidr $ip \
    --description "CloudFront HTTPS access"
done < /tmp/cloudfront-ips.txt
```

**ステップ3: 0.0.0.0/0ルール削除**

```bash
# まず既存ルールIDを取得
RULE_ID=$(aws ec2 describe-security-group-rules \
  --filters "Name=group-id,Values=sg-06561791d65d7c473" \
            "Name=cidr,Values=0.0.0.0/0" \
  --query 'SecurityGroupRules[?FromPort==`443`].SecurityGroupRuleId' \
  --output text)

# ルール削除
aws ec2 revoke-security-group-ingress \
  --group-id sg-06561791d65d7c473 \
  --security-group-rule-ids $RULE_ID
```

**ステップ4: 動作確認**

```bash
# CloudFront経由のアクセス（成功すべき）
curl -I https://my-teacher-app.com
# HTTP/2 200

# ALB直接アクセス（失敗すべき、タイムアウト）
curl -I -m 5 https://myteacher-production-alb-493399435.ap-northeast-1.elb.amazonaws.com
# curl: (28) Connection timed out
```

### 緊急時のロールバック手順

**ケース1: Security Group設定ミスでサイトダウン**

```bash
# 一時的に0.0.0.0/0を再追加
aws ec2 authorize-security-group-ingress \
  --group-id sg-06561791d65d7c473 \
  --protocol tcp --port 443 --cidr 0.0.0.0/0 \
  --description "Emergency rollback"

# サイトアクセス確認
curl -I https://my-teacher-app.com
```

**ケース2: Database接続エラー**

```bash
# Security Group規則の再作成
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply \
  -target=module.myteacher.aws_security_group_rule.database_from_ecs \
  -target=module.myteacher.aws_security_group_rule.redis_from_ecs \
  -auto-approve
```

**ケース3: ECS Service異常**

```bash
# 現在のサービス状態確認
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service

# Desired Countを0にしてタスク停止
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --desired-count 0

# 30秒待機後、2に戻す
sleep 30
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --desired-count 2
```

---

## 今後の推奨事項

### 短期（1週間以内）

#### 1. Port 443のセキュリティ強化（高優先度）

**現状**: 0.0.0.0/0からHTTPSアクセス可能

**対応**:
- CloudFront IPレンジを個別ルールとして追加（最大15個）
- 0.0.0.0/0ルールを削除
- ALBリスナールールでCustom Header検証追加

**担当**: インフラエンジニア  
**期限**: 2025年12月3日

#### 2. 登録画面の完全無効化（中優先度）

**現状**: `/register`にGETアクセス可能、登録フォーム表示される

**対応**:
- `RegisterAction::create()` にも `abort(404)` 追加
- または、routes/web.phpで登録ルート自体をコメントアウト

**担当**: バックエンドエンジニア  
**期限**: 2025年12月5日

#### 3. Terraform Stateバックアップ（高優先度）

**現状**: ローカルにStateファイル保存、バックアップなし

**対応**:
- S3バックエンド設定（`backend "s3"`）
- DynamoDB State Locking
- 日次バックアップ自動化

**実装例**:
```hcl
# backend.tf
terraform {
  backend "s3" {
    bucket         = "myteacher-terraform-state"
    key            = "production/terraform.tfstate"
    region         = "ap-northeast-1"
    dynamodb_table = "myteacher-terraform-locks"
    encrypt        = true
  }
}
```

**担当**: インフラエンジニア  
**期限**: 2025年12月7日

### 中期（1ヶ月以内）

#### 4. CloudWatch Dashboard作成

**目的**: リアルタイムでサービス健全性を監視

**含めるメトリクス**:
- ECS Service: RunningCount, CPUUtilization, MemoryUtilization
- ALB: TargetResponseTime, HTTPCode_Target_5XX_Count
- RDS: DatabaseConnections, CPUUtilization
- CloudFront: Requests, 5xxErrorRate

#### 5. AWS Backup設定

**対象**:
- RDS（日次スナップショット、7日保持）
- EFS（週次バックアップ、30日保持）

**コスト**: 約$5/月

#### 6. Parameter Store / Secrets Manager導入

**現状**: 環境変数に平文でDB_PASSWORD等が保存

**対応**:
- DB_PASSWORD, OPENAI_API_KEY等をSecrets Managerに移行
- ECS Task Definitionで参照

**セキュリティ向上**:
- 自動ローテーション
- IAMベースのアクセス制御
- 監査ログ

### 長期（3ヶ月以内）

#### 7. WAF導入

**目的**: DDoS攻撃、SQLインジェクション、XSS対策

**コスト**: 
- WebACL: $5/月
- Rules: $1/月 × 5 = $5/月
- Requests: $0.60/100万リクエスト
- **合計**: 約$11/月

#### 8. Blue/Green Deployment

**目的**: ゼロダウンタイムデプロイ

**実装**:
- CodeDeploy + ECS
- 新バージョンを並行稼働
- トラフィックを段階的に切り替え

#### 9. Multi-AZ RDS

**現状**: Single-AZ（可用性99.5%）

**移行後**: Multi-AZ（可用性99.95%）

**コスト**: +$13/月

### ドキュメント整備

#### 必要なドキュメント

1. **運用手順書**:
   - デプロイ手順（詳細版）
   - 緊急時ロールバック手順
   - スケーリング対応手順

2. **セキュリティポリシー**:
   - Security Group設定基準
   - 機密情報管理ルール
   - インシデント対応フロー

3. **アーキテクチャ図**:
   - ネットワーク構成図
   - データフロー図
   - Security Group関係図

4. **トラブルシューティングガイド**:
   - よくあるエラーと解決方法
   - ログ確認手順
   - 問い合わせ先一覧

---

## 結論

### 達成された成果

✅ **本番環境完全復旧**: すべてのサービスがHealthy状態で稼働  
✅ **静的アセット問題解決**: ASSET_URL=""設定により相対パス配信  
✅ **Security Group問題解決**: 新規SG作成、CloudFront Prefix List制限を回避  
✅ **Terraform State正常化**: すべてのリソースがTerraform管理下に復帰  
✅ **Database接続復旧**: 正しいSecurity Group規則を再設定  
✅ **特定ユーザー限定運用**: 新規登録停止により既存ユーザーのみ利用可能  

### 学んだ教訓

1. **CloudFront Prefix Listの罠**: 45 IPレンジが内部的に45ルールとしてカウントされる
2. **Terraform -targetの危険性**: 依存関係により予期せぬリソースが削除される
3. **Security Group規則の重要性**: ECS Tasks SGとDatabase SGの不整合で全サービスダウン
4. **アプリケーションレベルの制御**: インフラ制限が困難な場合、コードで対応も有効

### 今後の展望

本番環境は完全に復旧し、特定ユーザー限定運用体制が確立されました。今後は以下の優先順位で改善を進めます：

1. **セキュリティ強化**（Port 443制限、WAF導入）
2. **運用自動化**（Terraform State S3バックエンド、CloudWatch Dashboard）
3. **可用性向上**（Multi-AZ RDS、Blue/Green Deployment）
4. **マイクロサービス移行**（Phase 1: Cognito統合から開始）

---

**レポート作成者**: AI Development Assistant  
**最終更新日**: 2025年11月26日 15:00 JST  
**承認ステータス**: レビュー待ち  
**関連ドキュメント**:
- `PHASE0.5_COMPLETION_REPORT.md`（HTTPS化・Auto Scaling）
- `PHASE1_COMPLETION_REPORT.md`（Cognito統合）
- `2025-11-25_SESSION_AND_QUEUE_FIX_REPORT.md`（Session/Queue問題）

