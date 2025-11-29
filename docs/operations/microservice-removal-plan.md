# マイクロサービス削除実行プラン

**作成日**: 2025-11-29  
**目的**: マイクロサービス関連リソースの完全削除とコスト削減  
**予想効果**: 月額$30-50削減、管理負荷軽減

---

## 📋 削除対象リソース

### 1. AWS リソース (Terraform管理)

#### Task Service関連
- **ECS Cluster**: task-service-cluster (Fargate)
- **ECS Service**: task-service
- **ECS Task Definition**: task-service-task
- **RDS PostgreSQL**: db.t3.micro (task-service-db)
- **Target Group**: task-service-tg
- **Security Group**: task-service-sg

#### Portal/API関連
- **Cognito User Pool**: myteacher-production
- **Cognito Identity Pool**: ap-northeast-1:54f12983-012f-4c84-9763-72a19cd023f2
- **API Gateway**: 4un3jpgg5b.execute-api.ap-northeast-1.amazonaws.com
- **DynamoDB Tables**: 
  - production-portal-app-updates
  - production-portal-contacts
  - production-portal-faqs
  - production-portal-maintenances

### 2. ソースコード (Git管理)

#### Task Service (37ファイル)
```
services/task-service/
├── src/ (15ファイル)
├── tests/ (8ファイル)
├── aws/ (5ファイル)
├── scripts/ (4ファイル)
└── 設定ファイル (5ファイル)
```

#### AI Service (22ファイル)
```
services/ai-service/
├── template.yaml
├── src/handlers/ (16ファイル)
└── ドキュメント (5ファイル)
```

#### GitHub Actions
```
.github/workflows/
├── task-service-ci-cd.yml
├── task-service-ci-cd-main.yml
└── task-service-ci-cd-production.yml
```

---

## 🚀 削除手順

### Phase 1: 安全確認 (30分)

1. **MyTeacher本番環境の確認**
   ```bash
   # 本番環境が正常稼働していることを確認
   curl -I https://my-teacher-app.com
   aws ecs describe-services --cluster myteacher-production-cluster --services myteacher-production-app-service
   ```

2. **Task Serviceの停止確認**
   ```bash
   # Task Serviceが使用されていないことを確認
   aws ecs describe-services --cluster task-service-cluster --services task-service
   ```

3. **バックアップ確認**
   ```bash
   # RDSスナップショット確認
   aws rds describe-db-snapshots --db-instance-identifier task-service-db
   
   # Terraformステートバックアップ
   cp infrastructure/terraform/terraform.tfstate infrastructure/terraform/terraform.tfstate.pre-removal-$(date +%Y%m%d).backup
   ```

### Phase 2: AWS リソース削除 (1-2時間)

#### Step 1: ECS Task Service停止
```bash
cd /home/ktr/mtdev/infrastructure/terraform

# Task Serviceのタスク数を0に
aws ecs update-service \
  --cluster task-service-cluster \
  --service task-service \
  --desired-count 0

# タスク停止確認 (数分待機)
aws ecs describe-services --cluster task-service-cluster --services task-service
```

#### Step 2: Terraform Destroy (順次実行)
```bash
# DynamoDBテーブル削除
terraform destroy -target=aws_dynamodb_table.portal_app_updates
terraform destroy -target=aws_dynamodb_table.portal_contacts
terraform destroy -target=aws_dynamodb_table.portal_faqs
terraform destroy -target=aws_dynamodb_table.portal_maintenances

# API Gateway削除
terraform destroy -target=aws_apigatewayv2_api.main
terraform destroy -target=aws_apigatewayv2_stage.production
terraform destroy -target=aws_apigatewayv2_integration.main

# Cognito削除
terraform destroy -target=aws_cognito_identity_pool.main
terraform destroy -target=aws_cognito_user_pool.main
terraform destroy -target=aws_cognito_user_pool_client.web
terraform destroy -target=aws_cognito_user_pool_client.admin

# Task Service ECS削除
terraform destroy -target=aws_ecs_service.task_service
terraform destroy -target=aws_ecs_task_definition.task_service
terraform destroy -target=aws_ecs_cluster.task_service

# Task Service RDS削除
terraform destroy -target=aws_db_instance.task_service_db
terraform destroy -target=aws_db_subnet_group.task_service

# ネットワークリソース削除
terraform destroy -target=aws_lb_target_group.task_service
terraform destroy -target=aws_security_group.task_service
```

#### Step 3: ECR Repository削除 (任意)
```bash
# Task Service用ECRリポジトリ削除
aws ecr delete-repository \
  --repository-name task-service \
  --force
```

### Phase 3: ソースコード削除 (30分)

```bash
cd /home/ktr/mtdev

# services/配下のマイクロサービス削除
rm -rf services/task-service/
rm -rf services/ai-service/

# GitHub Actions削除
rm -f .github/workflows/task-service-ci-cd.yml
rm -f .github/workflows/task-service-ci-cd-main.yml
rm -f .github/workflows/task-service-ci-cd-production.yml

# 削除確認
git status
```

### Phase 4: Laravel統合実装 (2-3時間)

#### Step 1: Mobile API Action作成
```bash
cd /home/ktr/mtdev/laravel

# API Actionディレクトリ作成
mkdir -p app/Http/Actions/Api/Task
mkdir -p app/Http/Responders/Api/Task
```

#### Step 2: API Action実装
```php
// laravel/app/Http/Actions/Api/Task/StoreTaskApiAction.php
<?php

namespace App\Http\Actions\Api\Task;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * モバイルアプリ用タスク作成API
 * 既存TaskManagementServiceを活用
 */
class StoreTaskApiAction
{
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}
    
    public function __invoke(StoreTaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskService->createTask(
                $request->user(),
                $request->validated()
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'task' => $task->load(['images', 'tags']),
                ],
                'message' => 'タスクが作成されました。',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'タスクの作成に失敗しました。',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}
```

#### Step 3: APIルート追加
```php
// laravel/routes/api.php
use App\Http\Actions\Api\Task\StoreTaskApiAction;
use App\Http\Actions\Api\Task\UpdateTaskApiAction;
use App\Http\Actions\Api\Task\DeleteTaskApiAction;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // タスク管理API
    Route::post('/tasks', StoreTaskApiAction::class)->name('api.tasks.store');
    Route::put('/tasks/{task}', UpdateTaskApiAction::class)->name('api.tasks.update');
    Route::delete('/tasks/{task}', DeleteTaskApiAction::class)->name('api.tasks.destroy');
});
```

### Phase 5: 動作確認 (1時間)

```bash
# Laravel API動作確認
cd /home/ktr/mtdev/laravel
php artisan test --filter TaskApiTest

# Sanctum認証テスト
curl -X POST https://my-teacher-app.com/api/v1/tasks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"title": "テストタスク"}'

# MyTeacher既存機能確認
php artisan test
```

### Phase 6: Git Commit & Push (15分)

```bash
cd /home/ktr/mtdev

# Git追加・コミット
git add .
git commit -m "refactor: マイクロサービス削除とLaravel統合実装

- Task Service (Node.js) 削除: services/task-service/
- AI Service (Lambda) 削除: services/ai-service/
- GitHub Actions削除: task-service-ci-cd*.yml
- Laravel Mobile API実装: app/Http/Actions/Api/Task/
- Terraform削除: Cognito, API Gateway, DynamoDB, Task Service ECS/RDS

コスト削減: $30-50/月
管理負荷軽減: ECS別クラスター、RDS追加インスタンス削除
既存機能: 完全維持 (TaskManagementService活用)"

git push origin main
```

---

## ✅ 完了確認チェックリスト

### AWS リソース
- [ ] Task Service ECS Cluster削除完了
- [ ] Task Service RDS削除完了
- [ ] Cognito削除完了
- [ ] API Gateway削除完了
- [ ] DynamoDB 4テーブル削除完了
- [ ] MyTeacher本番環境は正常稼働

### ソースコード
- [ ] services/task-service/ 削除完了
- [ ] services/ai-service/ 削除完了
- [ ] GitHub Actions削除完了
- [ ] Laravel Mobile API実装完了
- [ ] APIルート追加完了

### 動作確認
- [ ] Laravel API動作確認完了
- [ ] Sanctum認証動作確認
- [ ] 既存MyTeacher機能動作確認
- [ ] テスト実行成功

### コスト確認
- [ ] AWS請求額確認 ($164 → $124目標)
- [ ] 不要リソース完全削除確認

---

## 🔄 ロールバック手順 (緊急時)

### Terraform復旧
```bash
cd /home/ktr/mtdev/infrastructure/terraform

# バックアップから復旧
cp terraform.tfstate.pre-removal-*.backup terraform.tfstate

# リソース再作成
terraform apply
```

### Git復旧
```bash
# コミット取り消し
git reset --hard HEAD~1

# リモート強制プッシュ (注意!)
git push origin main --force
```

---

## 📊 予想効果

### コスト削減
- **Task Service削除**: 約$20-30/月
- **RDS db.t3.micro削除**: 約$13/月
- **Cognito/API Gateway/DynamoDB**: 約$5-10/月
- **合計削減**: 約$38-53/月

### 管理負荷軽減
- ECS Cluster管理: 2個 → 1個
- RDS Instance管理: 3個 → 2個
- 削除サービス: Cognito, API Gateway, DynamoDB
- 監視対象減少: CloudWatch Metrics大幅削減

### アーキテクチャ単純化
- マイクロサービス複雑性排除
- Laravelモノリス統合
- API認証統一 (Sanctum)
- デバッグ・監視容易化

---

## 📝 注意事項

1. **Terraform Destroy実行時**: リソース依存関係に注意、順次実行推奨
2. **RDS削除前**: 最終スナップショット取得確認
3. **Cognito削除前**: 使用中のユーザー確認 (現在は未使用想定)
4. **API Gateway削除前**: アクセスログ確認 (トラフィックなし確認)
5. **削除後**: CloudWatch Logsグループも手動削除 (課金対象)

---

この計画に沿って実施することで、安全かつ確実にマイクロサービスを削除し、シンプルなモノリス構成に戻すことができます。
