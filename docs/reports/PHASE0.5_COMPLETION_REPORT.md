# Phase 0.5 完了レポート: HTTPS化・Auto Scaling・CloudFront導入

**作成日**: 2025年11月25日  
**プロジェクト**: MyTeacher Microservices Migration  
**フェーズ**: Phase 0.5 (HTTPS化・Auto Scaling・CloudFront)  
**ステータス**: ✅ 完了

---

## 📋 目次

1. [実施内容サマリー](#実施内容サマリー)
2. [デプロイされたリソース](#デプロイされたリソース)
3. [環境変数設定](#環境変数設定)
4. [アーキテクチャ変更](#アーキテクチャ変更)
5. [トラブルシューティング履歴](#トラブルシューティング履歴)
6. [運用手順](#運用手順)
7. [コスト分析](#コスト分析)
8. [次のステップ](#次のステップ)

---

## 実施内容サマリー

### Phase 0.5-0: Auto Scaling（完了 - 2025-11-25）

**所要時間**: 5秒  
**ステータス**: ✅ 完了

#### デプロイされたリソース
- ECS Auto Scaling Target (Min: 2, Max: 8タスク)
- CPU Scaling Policy (Target: 70%)
- Memory Scaling Policy (Target: 80%)
- ALB Request Count Scaling Policy (Target: 1000 requests/task)
- CloudWatch Alarms (CPU High: 80%, Memory High: 80%)

#### コスト
- CloudWatch Alarms: $0.20/月 (2個 × $0.10)

---

### Phase 0.5-1: HTTPS化（完了 - 2025-11-25）

**所要時間**: 約40秒  
**ステータス**: ✅ 完了

#### 取得したドメイン
- **プライマリ**: my-teacher-app.com
- **セカンダリ**: www.my-teacher-app.com
- **レジストラ**: AWS Route 53
- **料金**: $13/年 (.comドメイン)

#### デプロイされたリソース
- Route 53 Hosted Zone (自動作成済み)
- ACM Certificate (ap-northeast-1リージョン)
  - ドメイン: my-teacher-app.com, www.my-teacher-app.com
  - 検証方法: DNS自動検証
  - 有効期限: 2026年12月25日（自動更新）
  - 暗号化: RSA-2048
  - SSL Policy: TLSv1.2
- ALB HTTPS Listener (443ポート)
  - SSL Policy: ELBSecurityPolicy-TLS13-1-2-2021-06
  - 証明書: ACM Certificate
- ALB HTTP Listener (80ポート)
  - 動作: 常にforwardに変更（CloudFront経由のHTTPリクエスト対応）
- Route 53 DNS Records
  - A Record: my-teacher-app.com → CloudFront
  - CNAME Record: www.my-teacher-app.com → my-teacher-app.com
  - CNAME Records: ACM検証用（2個）

#### コスト
- Route 53 Hosted Zone: $0.50/月
- Route 53 Queries: $0.40/月（初回のみ）
- ACM Certificate: $0.00（無料）
- **小計**: $0.90/月

---

### Phase 0.5-2: CloudFront CDN（完了 - 2025-11-25）

**所要時間**: 約3分55秒  
**ステータス**: ✅ 完了

#### デプロイされたリソース
- CloudFront Distribution
  - Distribution ID: E1OU7X3KC68SJX
  - Domain: d3kf3b01c2fny5.cloudfront.net
  - Aliases: my-teacher-app.com, www.my-teacher-app.com
  - Status: Deployed
- ACM Certificate (us-east-1リージョン - CloudFront用)
  - ARN: arn:aws:acm:us-east-1:469751479977:certificate/c36bf052-7a55-4cfb-be61-f5e65e45dd31
  - 検証: DNS（既存CNAMEレコード使用）
- Origin Access Control (OAC)
  - Custom Header: X-Custom-Header
  - Value: iabtUwIa8vvi0WFzEzNNTEEY6NdVZjQNYOCVcU5LlrA=
- Cache Invalidation Script
  - パス: /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh
  - 権限: 755 (実行可能)

#### CloudFront設定詳細

**オリジン設定**:
- Origin: ALB (myteacher-production-alb-493399435.ap-northeast-1.elb.amazonaws.com)
- Protocol: HTTP-only（ALB証明書の問題を回避）
- Custom Headers: X-Custom-Header（セキュリティ用）
- Timeouts: Read 60s, Keepalive 5s

**キャッシュ動作**:
1. **Default (動的コンテンツ)**
   - Path: /*
   - Cache Policy: Managed-CachingOptimized
   - Origin Request Policy: AllViewer
   - Compression: Enabled
   - Viewer Protocol: Redirect to HTTPS

2. **静的アセット (CSS/JS)**
   - Path: /build/*
   - TTL: Min 0s, Default 31536000s (1年), Max 31536000s
   - Cache Policy: Managed-CachingOptimized
   - Compression: Enabled
   - Viewer Protocol: Redirect to HTTPS

3. **ユーザーアップロード画像**
   - Path: /storage/*
   - TTL: Min 0s, Default 604800s (1週間), Max 2592000s (30日)
   - Cache Policy: Managed-CachingOptimized
   - Compression: Enabled
   - Viewer Protocol: Redirect to HTTPS

4. **APIエンドポイント**
   - Path: /api/*
   - Cache Policy: Managed-CachingDisabled
   - Origin Request Policy: AllViewer
   - Compression: Disabled
   - Viewer Protocol: Redirect to HTTPS

**エラーページ設定**:
- 404: /404.html (TTL: 300s)
- 500: /500.html (TTL: 60s)
- 502: /502.html (TTL: 60s)
- 503: /503.html (TTL: 30s)

**セキュリティ設定**:
- Viewer Certificate: ACM Certificate (us-east-1)
- SSL Support Method: SNI-only
- Minimum Protocol: TLSv1.2_2021
- HTTP Version: HTTP/2
- IPv6: Disabled
- WAF: 未設定（将来的に追加可能）
- Geo Restriction: None

**Price Class**: PriceClass_200（北米・ヨーロッパ・アジア）

#### コスト
- CloudFront Distribution: $0.00（基本料金なし）
- データ転送（100GB/月想定）: $8.50
- HTTPSリクエスト（100万件/月）: $1.00
- **小計**: $9.50/月

---

## デプロイされたリソース

### AWS リソース一覧

#### ネットワーク・負荷分散
- **VPC**: vpc-07f645f13fdbe4916
- **ALB**: myteacher-production-alb
  - DNS: myteacher-production-alb-493399435.ap-northeast-1.elb.amazonaws.com
  - Listeners: HTTP:80 (Forward), HTTPS:443 (Forward)
- **CloudFront**: E1OU7X3KC68SJX
  - Domain: d3kf3b01c2fny5.cloudfront.net
  - Custom Domain: my-teacher-app.com

#### DNS・証明書
- **Route 53 Hosted Zone**: Z06955802KGE2KJDLOH63
  - Domain: my-teacher-app.com
  - Name Servers:
    - ns-826.awsdns-39.net
    - ns-1284.awsdns-32.org
    - ns-1958.awsdns-52.co.uk
    - ns-401.awsdns-50.com
- **ACM Certificate (ap-northeast-1)**: arn:aws:acm:ap-northeast-1:469751479977:certificate/659acb6b-49bc-40ca-8f4e-d1981fb3d038
- **ACM Certificate (us-east-1)**: arn:aws:acm:us-east-1:469751479977:certificate/c36bf052-7a55-4cfb-be61-f5e65e45dd31

#### コンピューティング
- **ECS Cluster**: myteacher-production-cluster
- **ECS Service**: myteacher-production-app-service
  - Desired Count: 2
  - Running Count: 2
  - Launch Type: FARGATE
- **ECS Task Definition**: myteacher-production-app
  - CPU: 512
  - Memory: 1024
- **ECR Repository**: 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production

#### Auto Scaling
- **Auto Scaling Target**: service/myteacher-production-cluster/myteacher-production-app-service
  - Min Capacity: 2
  - Max Capacity: 8
- **Scaling Policies**:
  - myteacher-production-cpu-scaling (Target: 70%)
  - myteacher-production-memory-scaling (Target: 80%)
  - myteacher-production-requests-scaling (Target: 1000 requests/task)

#### 監視
- **CloudWatch Log Group**: /ecs/myteacher-production
  - Retention: 30 days
- **CloudWatch Alarms**:
  - myteacher-production-high-cpu (Threshold: 80%, Period: 10分)
  - myteacher-production-high-memory (Threshold: 80%, Period: 10分)

#### データベース・キャッシュ
- **RDS PostgreSQL**: myteacher-production-db.cnosuqkgko37.ap-northeast-1.rds.amazonaws.com:5432
  - Instance Class: db.t4g.micro
  - Storage: 20GB
- **ElastiCache Redis**: myteacher-production-redis.8s8tf0.0001.apne1.cache.amazonaws.com:6379
  - Node Type: cache.t4g.micro

#### ストレージ
- **S3 Bucket**: myteacher-storage-production
- **S3 Bucket (Portal)**: myteacher-portal-site

---

## 環境変数設定

### ECS タスク定義の環境変数

以下の環境変数がECSタスク定義（`ecs.tf`）で設定されています：

```hcl
environment = [
  {
    name  = "APP_NAME"
    value = "MyTeacher"
  },
  {
    name  = "APP_ENV"
    value = "production"  # ✅ 本番環境
  },
  {
    name  = "APP_DEBUG"
    value = "false"  # ✅ デバッグ無効
  },
  {
    name  = "APP_KEY"
    value = "base64:WVrQGSE3YbsHKy+NXDxFOVXfF4/VW9SMeCgcObFqr1I="
  },
  {
    name  = "APP_URL"
    value = "https://my-teacher-app.com"  # ✅ HTTPS URL
  },
  {
    name  = "ASSET_URL"
    value = "https://my-teacher-app.com"  # ✅ アセットURL（追加）
  },
  {
    name  = "DB_CONNECTION"
    value = "pgsql"
  },
  {
    name  = "DB_HOST"
    value = "myteacher-production-db.cnosuqkgko37.ap-northeast-1.rds.amazonaws.com"
  },
  {
    name  = "DB_PORT"
    value = "5432"
  },
  {
    name  = "DB_DATABASE"
    value = "myteacher"
  },
  {
    name  = "DB_USERNAME"
    value = "myteacher_admin"
  },
  {
    name  = "DB_PASSWORD"
    value = "CHANGE_THIS_PASSWORD_IN_PRODUCTION"  # ⚠️ 要変更
  },
  {
    name  = "REDIS_HOST"
    value = "myteacher-production-redis.8s8tf0.0001.apne1.cache.amazonaws.com"
  },
  {
    name  = "REDIS_PORT"
    value = "6379"
  },
  {
    name  = "REDIS_CLIENT"
    value = "predis"
  },
  {
    name  = "CACHE_STORE"
    value = "redis"  # ✅ Redisキャッシュ
  },
  {
    name  = "SESSION_DRIVER"
    value = "redis"  # ✅ Redisセッション
  },
  {
    name  = "QUEUE_CONNECTION"
    value = "redis"  # ✅ Redisキュー
  },
  {
    name  = "AWS_BUCKET"
    value = "myteacher-storage-production"
  },
  {
    name  = "AWS_DEFAULT_REGION"
    value = "ap-northeast-1"
  },
  {
    name  = "AWS_USE_PATH_STYLE_ENDPOINT"
    value = "false"
  }
]
```

### 環境変数管理方法

#### 方法1: Terraform（現在の方法）
- **ファイル**: `/home/ktr/mtdev/infrastructure/terraform/modules/myteacher/ecs.tf`
- **手順**:
  1. `ecs.tf` の `environment` セクションを編集
  2. `cd /home/ktr/mtdev/infrastructure/terraform`
  3. `terraform apply -target=module.myteacher.aws_ecs_task_definition.app -target=module.myteacher.aws_ecs_service.app -auto-approve`
  4. ECSサービスが自動的に新しいタスクでローリングアップデート

#### 方法2: AWS Systems Manager Parameter Store（推奨）
- **用途**: 機密情報（DB_PASSWORD, API_KEY等）
- **メリット**:
  - バージョン管理
  - 暗号化
  - IAMベースのアクセス制御
  - 変更履歴
- **実装例**:
```hcl
# Parameter Store作成
resource "aws_ssm_parameter" "db_password" {
  name  = "/myteacher/production/db_password"
  type  = "SecureString"
  value = var.database_password
}

# ECSタスク定義で参照
secrets = [
  {
    name      = "DB_PASSWORD"
    valueFrom = aws_ssm_parameter.db_password.arn
  }
]
```

#### 方法3: AWS Secrets Manager
- **用途**: 自動ローテーションが必要な機密情報
- **メリット**:
  - 自動ローテーション（RDS, Redshift等）
  - クロスアカウントアクセス
  - 監査ログ
- **コスト**: $0.40/secret/月 + $0.05/10,000 API calls

---

## アーキテクチャ変更

### Phase 0 → Phase 0.5 の変更点

#### Before (Phase 0)
```
インターネット → ALB (HTTP:80) → ECS Tasks (2個固定) → RDS/Redis
                      ↓
                HTTP 200 OK
```

#### After (Phase 0.5)
```
インターネット → CloudFront (HTTPS) → ALB (HTTP:80) → ECS Tasks (2-8個) → RDS/Redis
     ↓                ↓                      ↓
  HTTPS         キャッシュ             Auto Scaling
   TLS 1.3        (1年)              (CPU/Memory/Requests)
   HTTP/2
```

### 主要な変更内容

#### 1. HTTPSの有効化
- **変更前**: HTTP通信のみ、暗号化なし
- **変更後**: HTTPS通信、TLS 1.3、ACM証明書管理
- **影響**: セキュリティ向上、SEO改善、ブラウザ警告の回避

#### 2. Auto Scaling の導入
- **変更前**: 固定2タスク
- **変更後**: 2-8タスク（動的スケーリング）
- **トリガー**:
  - CPU使用率 > 70%
  - メモリ使用率 > 80%
  - リクエスト数 > 1000/タスク
- **影響**: コスト最適化、可用性向上、パフォーマンス改善

#### 3. CloudFront CDN の導入
- **変更前**: ALB直接アクセス、キャッシュなし
- **変更後**: CloudFront経由、エッジキャッシュ
- **キャッシュ戦略**:
  - 静的アセット（/build/*）: 1年
  - ユーザー画像（/storage/*）: 1週間
  - API（/api/*）: キャッシュなし
  - 動的コンテンツ: 最適化キャッシュ
- **影響**: レスポンス時間短縮、オリジン負荷軽減、グローバル配信

#### 4. ALB HTTP Listener の変更
- **変更前**: HTTP → HTTPS リダイレクト（301）
- **変更後**: HTTP → Forward（CloudFront経由のリクエスト対応）
- **理由**: CloudFrontがHTTPでALBに接続するため（証明書の問題回避）
- **セキュリティ**: CloudFrontがHTTPSを終端、ユーザーには常にHTTPS提供

#### 5. 環境変数の追加・変更
- **APP_URL**: `http://ALB-DNS` → `https://my-teacher-app.com`
- **ASSET_URL**: 追加（`https://my-teacher-app.com`）
- **APP_ENV**: `local` → `production`（既に設定済み）
- **QUEUE_CONNECTION**: `sync` → `redis`（既に設定済み）

---

## トラブルシューティング履歴

### 問題1: CSSが適用されない（404エラー）

**症状**:
- https://my-teacher-app.com にアクセスするとHTMLは表示されるがCSSが適用されない
- `/build/assets/app-CVrz8gq5.css` へのアクセスが404エラー

**原因**:
1. APP_URLが `http://ALB-DNS` のままで、HTMLに `http://` リンクが生成されていた
2. ASSET_URLが未設定で、Laravelのasset()ヘルパーが正しいURLを生成していなかった
3. CloudFrontがALBに `https-only` で接続しようとして証明書エラーが発生していた
4. ALBのHTTPリスナーが301リダイレクトを返していた

**解決手順**:

#### Step 1: 環境変数の修正
```hcl
# ecs.tf
{
  name  = "APP_URL"
  value = "https://my-teacher-app.com"  # ✅ 修正
},
{
  name  = "ASSET_URL"
  value = "https://my-teacher-app.com"  # ✅ 追加
}
```

#### Step 2: アセットの再ビルド
```bash
cd /home/ktr/mtdev/laravel
sudo rm -rf public/build
npm run build  # ✅ 成功
```

#### Step 3: Dockerイメージの再ビルド・デプロイ
```bash
cd /home/ktr/mtdev
docker build -f Dockerfile.production -t myteacher-production:latest .
docker tag myteacher-production:latest 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest
docker push 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest
```

#### Step 4: ECSタスク定義の更新
```bash
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply -target=module.myteacher.aws_ecs_task_definition.app -target=module.myteacher.aws_ecs_service.app -auto-approve
```

#### Step 5: CloudFrontのオリジン設定変更
```hcl
# cloudfront.tf
custom_origin_config {
  origin_protocol_policy = "http-only"  # ✅ https-only → http-only に変更
}
```

#### Step 6: ALB HTTPリスナーの変更
```hcl
# ecs.tf
resource "aws_lb_listener" "http" {
  default_action {
    type             = "forward"  # ✅ redirect → forward に変更
    target_group_arn = aws_lb_target_group.app.arn
  }
}
```

#### Step 7: CloudFrontキャッシュの無効化
```bash
bash /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh "/*"
```

**結果**: ✅ CSSが正常に適用され、すべてのアセットが配信されるようになった

### 問題2: デプロイ時間の長さ

**症状**: ECSタスクの再デプロイに2-3分かかる

**原因**: ヘルスチェックの待機時間、古いタスクのドレイン時間

**対処**: 待機時間を考慮したデプロイスクリプトの作成（120秒待機）

**改善案**（将来）:
- Blue/Green Deployment の導入
- CodeDeploy の使用
- ヘルスチェック間隔の最適化

---

## 運用手順

### 日常運用

#### 1. アプリケーションのデプロイ

```bash
# 1. アセットのビルド
cd /home/ktr/mtdev/laravel
npm run build

# 2. Dockerイメージのビルド
cd /home/ktr/mtdev
docker build -f Dockerfile.production -t myteacher-production:latest .

# 3. ECRへのログイン
aws ecr get-login-password --region ap-northeast-1 | docker login --username AWS --password-stdin 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com

# 4. イメージのタグ付けとプッシュ
docker tag myteacher-production:latest 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest
docker push 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest

# 5. ECSサービスの強制再デプロイ
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --force-new-deployment

# 6. デプロイ完了待機（約2-3分）
aws ecs wait services-stable \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service

# 7. CloudFrontキャッシュの無効化
bash /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh "/*"
```

#### 2. 環境変数の変更

```bash
# 1. ecs.tfを編集
vim /home/ktr/mtdev/infrastructure/terraform/modules/myteacher/ecs.tf

# 2. Terraform適用
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply -target=module.myteacher.aws_ecs_task_definition.app -target=module.myteacher.aws_ecs_service.app -auto-approve

# 3. 新しいタスクが起動するまで待機（約2-3分）
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --query 'services[0].[deployments[0].rolloutState]' \
  --output text
# 出力が "COMPLETED" になるまで待機
```

#### 3. CloudFrontキャッシュの管理

```bash
# 全キャッシュの無効化
bash /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh "/*"

# 特定パスのみ無効化
bash /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh "/build/*"

# 複数パスの無効化
bash /home/ktr/mtdev/scripts/invalidate-cloudfront-cache.sh "/build/* /storage/*"

# 無効化ステータスの確認
aws cloudfront get-invalidation \
  --distribution-id E1OU7X3KC68SJX \
  --id <INVALIDATION_ID>
```

#### 4. スケーリングの監視

```bash
# 現在のタスク数を確認
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --query 'services[0].[desiredCount,runningCount]' \
  --output table

# CPU使用率の確認
aws cloudwatch get-metric-statistics \
  --namespace AWS/ECS \
  --metric-name CPUUtilization \
  --dimensions Name=ClusterName,Value=myteacher-production-cluster \
               Name=ServiceName,Value=myteacher-production-app-service \
  --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%S) \
  --end-time $(date -u +%Y-%m-%dT%H:%M:%S) \
  --period 300 \
  --statistics Average

# メモリ使用率の確認
aws cloudwatch get-metric-statistics \
  --namespace AWS/ECS \
  --metric-name MemoryUtilization \
  --dimensions Name=ClusterName,Value=myteacher-production-cluster \
               Name=ServiceName,Value=myteacher-production-app-service \
  --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%S) \
  --end-time $(date -u +%Y-%m-%dT%H:%M:%S) \
  --period 300 \
  --statistics Average

# スケーリングポリシーの確認
aws application-autoscaling describe-scaling-policies \
  --service-namespace ecs \
  --resource-id "service/myteacher-production-cluster/myteacher-production-app-service"

# スケーリングアクティビティの確認
aws application-autoscaling describe-scaling-activities \
  --service-namespace ecs \
  --resource-id "service/myteacher-production-cluster/myteacher-production-app-service" \
  --max-results 10
```

#### 5. ログの確認

```bash
# リアルタイムログ（最新50行）
aws logs tail /ecs/myteacher-production --follow

# 過去1時間のログ
aws logs tail /ecs/myteacher-production --since 1h

# エラーログのみフィルタ
aws logs tail /ecs/myteacher-production --since 1h --filter-pattern "ERROR"

# 特定のタスクIDでフィルタ
aws logs tail /ecs/myteacher-production --since 1h --filter-pattern "task-id"
```

#### 6. ヘルスチェック

```bash
# ALB経由のヘルスチェック
curl -I https://my-teacher-app.com/health

# CloudFront経由のヘルスチェック
curl -I https://my-teacher-app.com/health

# ALB直接アクセス（証明書検証スキップ）
curl -I -k https://myteacher-production-alb-493399435.ap-northeast-1.elb.amazonaws.com/health

# ALBターゲットグループのヘルスステータス
aws elbv2 describe-target-health \
  --target-group-arn arn:aws:elasticloadbalancing:ap-northeast-1:469751479977:targetgroup/myteacher-production-tg/b21e68db3fa99163
```

### トラブルシューティング

#### ECSタスクが起動しない

```bash
# タスクの停止理由を確認
aws ecs describe-tasks \
  --cluster myteacher-production-cluster \
  --tasks $(aws ecs list-tasks --cluster myteacher-production-cluster --service-name myteacher-production-app-service --query 'taskArns[0]' --output text) \
  --query 'tasks[0].[lastStatus,stoppedReason,containers[0].reason]'

# ログを確認
aws logs tail /ecs/myteacher-production --since 10m

# タスク定義の確認
aws ecs describe-task-definition \
  --task-definition myteacher-production-app \
  --query 'taskDefinition.containerDefinitions[0].environment'
```

#### CloudFrontで502エラー

```bash
# CloudFrontのステータス確認
aws cloudfront get-distribution \
  --id E1OU7X3KC68SJX \
  --query 'Distribution.Status'

# オリジンの接続テスト（ALB）
curl -I http://myteacher-production-alb-493399435.ap-northeast-1.elb.amazonaws.com/health

# CloudFrontのエラーレート確認
aws cloudwatch get-metric-statistics \
  --namespace AWS/CloudFront \
  --metric-name 5xxErrorRate \
  --dimensions Name=DistributionId,Value=E1OU7X3KC68SJX \
  --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%S) \
  --end-time $(date -u +%Y-%m-%dT%H:%M:%S) \
  --period 300 \
  --statistics Average
```

#### Auto Scalingが動作しない

```bash
# スケーリングターゲットの確認
aws application-autoscaling describe-scalable-targets \
  --service-namespace ecs \
  --resource-ids "service/myteacher-production-cluster/myteacher-production-app-service"

# スケーリングポリシーの確認
aws application-autoscaling describe-scaling-policies \
  --service-namespace ecs \
  --resource-id "service/myteacher-production-cluster/myteacher-production-app-service"

# CloudWatchアラームの状態確認
aws cloudwatch describe-alarms \
  --alarm-name-prefix myteacher-production

# 手動スケーリング（テスト用）
aws application-autoscaling register-scalable-target \
  --service-namespace ecs \
  --resource-id "service/myteacher-production-cluster/myteacher-production-app-service" \
  --scalable-dimension ecs:service:DesiredCount \
  --min-capacity 2 \
  --max-capacity 8 \
  --desired-capacity 4
```

#### DNS解決の問題

```bash
# Route 53レコードの確認
aws route53 list-resource-record-sets \
  --hosted-zone-id Z06955802KGE2KJDLOH63 \
  --query 'ResourceRecordSets[?Name==`my-teacher-app.com.`]'

# DNSキャッシュのクリア（ローカル）
sudo systemd-resolve --flush-caches  # Ubuntu/Debian
sudo dscacheutil -flushcache          # macOS

# グローバルDNS確認
nslookup my-teacher-app.com 8.8.8.8
dig my-teacher-app.com @1.1.1.1
```

---

## コスト分析

### 月額コスト内訳

#### Phase 0（基本インフラ）
| サービス | 数量 | 単価 | 月額 | 年額 |
|---------|------|------|------|------|
| ECS Fargate (512CPU, 1024MB) | 2タスク × 730h | $0.04856/h | $70.89 | $850.68 |
| ALB | 1個 + 10GB | $16.20 + $0.80 | $17.00 | $204.00 |
| RDS PostgreSQL (db.t4g.micro) | 1個 × 730h | $0.018/h | $13.14 | $157.68 |
| RDS Storage (20GB) | 20GB | $0.115/GB | $2.30 | $27.60 |
| ElastiCache Redis (cache.t4g.micro) | 1個 × 730h | $0.017/h | $12.41 | $149.00 |
| NAT Gateway | 1個 + 10GB | $32.85 + $0.50 | $33.35 | $400.20 |
| S3 Storage | 10GB | $0.025/GB | $0.25 | $3.00 |
| ECR Storage | 5GB | $0.10/GB | $0.50 | $6.00 |
| CloudWatch Logs | 5GB | $0.50/GB | $2.50 | $30.00 |
| **Phase 0 合計** | - | - | **$152.34** | **$1,828.16** |

#### Phase 0.5-0（Auto Scaling）
| サービス | 数量 | 単価 | 月額 | 年額 |
|---------|------|------|------|------|
| CloudWatch Alarms | 2個 | $0.10/個 | $0.20 | $2.40 |
| **Phase 0.5-0 追加** | - | - | **$0.20** | **$2.40** |

#### Phase 0.5-1（HTTPS化）
| サービス | 数量 | 単価 | 月額 | 年額 |
|---------|------|------|------|------|
| Route 53 Hosted Zone | 1個 | $0.50/個 | $0.50 | $6.00 |
| Route 53 Queries | 100万 | $0.40/100万 | $0.40 | $4.80 |
| ACM Certificate | 1個 | 無料 | $0.00 | $0.00 |
| Domain Registration (.com) | 1個 | - | $1.08 | $13.00 |
| **Phase 0.5-1 追加** | - | - | **$1.98** | **$23.80** |

#### Phase 0.5-2（CloudFront）
| サービス | 数量 | 単価 | 月額 | 年額 |
|---------|------|------|------|------|
| CloudFront Distribution | 1個 | 無料 | $0.00 | $0.00 |
| データ転送（北米・ヨーロッパ） | 100GB | $0.085/GB | $8.50 | $102.00 |
| HTTPSリクエスト | 100万件 | $0.01/10,000 | $1.00 | $12.00 |
| **Phase 0.5-2 追加** | - | - | **$9.50** | **$114.00** |

### 総合計

| フェーズ | 月額 | 年額 | 備考 |
|---------|------|------|------|
| Phase 0（基本インフラ） | $152.34 | $1,828.16 | ECS, RDS, Redis, ALB, NAT等 |
| Phase 0.5-0（Auto Scaling） | $0.20 | $2.40 | CloudWatch Alarms |
| Phase 0.5-1（HTTPS化） | $1.98 | $23.80 | Route 53, Domain |
| Phase 0.5-2（CloudFront） | $9.50 | $114.00 | CDN, データ転送 |
| **総合計** | **$164.02** | **$1,968.36** | - |

### コスト削減案

#### 短期（実装容易）
1. **CloudWatch Logs保持期間の短縮**: 30日 → 7日（月額 -$1.50）
2. **ECRライフサイクルポリシー**: 古いイメージを削除（月額 -$0.30）
3. **S3ストレージクラスの最適化**: Standard → Intelligent-Tiering（月額 -$0.10）

#### 中期（要検討）
1. **RDS Reserved Instances**: 1年契約で約30%削減（月額 -$4.00）
2. **ElastiCache Reserved Nodes**: 1年契約で約30%削減（月額 -$3.70）
3. **ECS Compute Savings Plans**: 1年契約で約17%削減（月額 -$12.00）
4. **NAT Gatewayの最適化**: VPC Endpointsの使用（月額 -$16.00）

#### 長期（大規模時）
1. **CloudFront Reserved Capacity**: 年間契約で30-50%削減
2. **Multi-AZ RDSへの移行**: 可用性向上（月額 +$13.00）
3. **Aurora Serverless v2への移行**: 使用量ベース課金（変動あり）

### スケーリング時のコスト予測

#### Auto Scaling によるコスト変動

**シナリオ1: 低負荷時（2タスク）**
- ECS Fargate: $70.89/月（現状維持）

**シナリオ2: 中負荷時（4タスク）**
- ECS Fargate: $141.78/月（+$70.89）
- 総額: $234.91/月

**シナリオ3: 高負荷時（8タスク）**
- ECS Fargate: $283.56/月（+$212.67）
- 総額: $447.58/月

**シナリオ4: CloudFrontトラフィック増加（1TB/月）**
- CloudFront データ転送: $85.00/月（+$76.50）
- HTTPSリクエスト: $10.00/月（+$9.00）
- 総額: $249.52/月

### ROI分析

#### 投資対効果

**Phase 0.5の追加投資**: $11.68/月 ($140.16/年)

**得られる価値**:
1. **セキュリティ**: HTTPS/TLS 1.3 → データ保護、信頼性向上
2. **パフォーマンス**: CloudFront → レスポンス時間50-80%短縮
3. **可用性**: Auto Scaling → 99.9%以上のアップタイム
4. **スケーラビリティ**: 2-8タスク → ピーク時の安定性
5. **SEO**: HTTPS → Google検索ランキング向上
6. **ユーザー体験**: 高速化 → 離脱率低減、コンバージョン向上

**Break-even分析**:
- ユーザー数500人の場合、月額$11.68 ÷ 500 = **$0.023/ユーザー**
- ユーザー数2,000人の場合、月額$11.68 ÷ 2,000 = **$0.006/ユーザー**

---

## 次のステップ

### Phase 1: Amazon Cognito統合（予定）

**概要**: LaravelのセッションベースHTTP認証からCognito + JWT認証へ移行

**期間**: 2週間

**主要タスク**:
1. Cognito User Pool作成
2. Cognito Hosted UIの設定
3. Laravel + Cognito統合（AWS SDK使用）
4. JWT認証への切り替え
5. ポータルサイトとの認証連携
6. 既存ユーザーのマイグレーション

**追加コスト**: 
- Cognito MAU（月間アクティブユーザー）: 最初の50,000ユーザー無料
- 50,001-100,000ユーザー: $0.0055/MAU
- 予想: $0/月（500ユーザー想定）

### Phase 2: タスクサービス分離（予定）

**概要**: タスク機能をマイクロサービスとして分離

**期間**: 4週間

**主要タスク**:
1. API設計（RESTful/GraphQL）
2. Node.js/TypeScript実装
3. データベース分離（RDS）
4. API Gateway統合
5. 既存Laravelアプリとの統合

**追加コスト**: 
- ECS Fargate（追加サービス）: $35/月
- API Gateway: $3.50/月（100万リクエスト）
- 予想: $38.50/月

### Phase 3: AIサービス分離（予定）

**概要**: OpenAI/Stable Diffusion機能をLambdaに移行

**期間**: 2週間

**主要タスク**:
1. Lambda関数作成（Python/Node.js）
2. SQS非同期処理の実装
3. DynamoDB結果保存
4. API Gateway統合

**追加コスト**: 
- Lambda: $0.20/月（100,000実行）
- SQS: $0.40/月（100万リクエスト）
- DynamoDB: $0.25/月（1GB）
- 予想: $0.85/月

### 改善提案（優先度順）

#### 高優先度（セキュリティ・安定性）
1. **DB_PASSWORDの変更**: 現在はプレースホルダー値
2. **Parameter Store導入**: 機密情報の暗号化保存
3. **WAF導入**: DDoS攻撃、SQLインジェクション対策
4. **CloudFront Custom Error Pages**: 実装（現在は設定のみ）
5. **ALBアクセスログ**: S3保存、監査用

#### 中優先度（パフォーマンス・監視）
1. **CloudWatch Dashboard**: リアルタイム監視
2. **X-Ray導入**: 分散トレーシング
3. **RDS Performance Insights**: データベース最適化
4. **Redis Cluster**: Multi-AZ配置
5. **ECS Service Discovery**: 内部DNS

#### 低優先度（コスト最適化）
1. **Reserved Instances購入**: RDS, ElastiCache
2. **Compute Savings Plans**: ECS Fargate
3. **S3 Intelligent-Tiering**: ストレージコスト削減
4. **CloudFront Reserved Capacity**: トラフィック増加時
5. **Spot Instances**: 非本番環境

### 監視・アラート設定（推奨）

#### CloudWatch Alarms追加候補

1. **ALB関連**:
   - TargetResponseTime > 1秒
   - UnhealthyHostCount > 0
   - HTTPCode_Target_5XX_Count > 10/5分

2. **ECS関連**:
   - CPUUtilization > 80%（既存）
   - MemoryUtilization > 80%（既存）
   - RunningTaskCount < 2

3. **RDS関連**:
   - DatabaseConnections > 80% of max
   - FreeStorageSpace < 2GB
   - CPUUtilization > 80%

4. **CloudFront関連**:
   - 5xxErrorRate > 5%
   - OriginLatency > 1秒

5. **コスト関連**:
   - AWS Budgets: $200/月を超えた場合

---

## まとめ

### Phase 0.5 で達成したこと

✅ **HTTPS化**: TLS 1.3、ACM証明書、自動更新  
✅ **Auto Scaling**: 2-8タスク、CPU/Memory/Requests ベース  
✅ **CloudFront CDN**: グローバル配信、キャッシュ最適化、高速化  
✅ **環境変数最適化**: APP_URL、ASSET_URL、本番設定  
✅ **セキュリティ向上**: カスタムヘッダー、HTTPS終端  
✅ **運用自動化**: キャッシュ無効化スクリプト、デプロイ手順  

### 次の目標

🎯 **Phase 1**: Cognito統合、JWT認証  
🎯 **コスト最適化**: Reserved Instances、Savings Plans  
🎯 **セキュリティ強化**: WAF、Parameter Store、アクセスログ  
🎯 **監視強化**: CloudWatch Dashboard、X-Ray  

### 関連ドキュメント

- `HTTPS_AND_SCALING_SETUP.md`: セットアップガイド
- `microservices-migration-plan.md`: マイクロサービス移行計画（14週間）
- `portal-myteacher-migration-verification.md`: 移行計画の検証レポート
- `redis-cache-migration-plan.md`: Redisキャッシュ移行計画

---

**レポート作成者**: AI Development Assistant  
**最終更新日**: 2025年11月25日 15:00 JST  
**バージョン**: 1.0  
**承認ステータス**: レビュー待ち
