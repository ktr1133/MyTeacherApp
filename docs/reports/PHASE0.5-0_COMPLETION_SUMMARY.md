# Phase 0.5-0 完了サマリー

**実施日**: 2025年11月25日  
**フェーズ**: Phase 0.5-0 - ECS Auto Scaling実装  
**ステータス**: ✅ 完全デプロイ完了

---

## 📊 実装概要

Phase 0完了環境（ECS/Fargate + ALB）に対して、自動スケーリング機能を追加しました。

### デプロイされたリソース（5個）

| リソース | タイプ | 設定値 |
|---------|--------|--------|
| Auto Scaling Target | `aws_appautoscaling_target.ecs` | Min: 2, Max: 8タスク |
| CPU Scaling Policy | `aws_appautoscaling_policy.cpu` | Target: 70% CPU |
| Memory Scaling Policy | `aws_appautoscaling_policy.memory` | Target: 80% Memory |
| High CPU Alarm | `aws_cloudwatch_metric_alarm.high_cpu` | Threshold: 80%, 10分評価 |
| High Memory Alarm | `aws_cloudwatch_metric_alarm.high_memory` | Threshold: 80%, 10分評価 |

---

## 🎯 スケーリング動作

### Scale-out（タスク増加）

**トリガー条件:**
- CPU使用率 > 70%（平均、5分間）
- **または** メモリ使用率 > 80%（平均、5分間）

**動作:**
- 1タスクずつ追加（最大8タスクまで）
- Cooldown: 60秒（過剰スケールアウト防止）

### Scale-in（タスク減少）

**トリガー条件:**
- CPU使用率 < 70%（平均、5分間）
- **かつ** メモリ使用率 < 80%（平均、5分間）

**動作:**
- 1タスクずつ削除（最小2タスクまで）
- Cooldown: 300秒（安定性確保）

### アラーム

**発火条件:**
- CPU > 80% **または** メモリ > 80% が10分継続

**通知先:**
- CloudWatch Alarmsダッシュボード
- （将来）SNS経由でメール/Slack通知可能

---

## 🔧 実装で遭遇したIAM権限エラーと解決

### エラー1: application-autoscaling:TagResource

**エラーメッセージ:**
```
User: arn:aws:iam::469751479977:user/infrauser is not authorized to perform: 
application-autoscaling:TagResource
```

**解決策:**
`IAM_PERMISSIONS_MYTEACHER.md` セクション10に以下を追加:
- `application-autoscaling:TagResource`
- `application-autoscaling:UntagResource`
- `application-autoscaling:ListTagsForResource`

### エラー2: iam:CreateServiceLinkedRole

**エラーメッセージ:**
```
ValidationException: User is missing the following permissions: 
iam:CreateServiceLinkedRole
```

**解決策:**
Service-Linked Role作成権限を追加:
```json
{
  "Effect": "Allow",
  "Action": ["iam:CreateServiceLinkedRole"],
  "Resource": "arn:aws:iam::*:role/aws-service-role/ecs.application-autoscaling.amazonaws.com/AWSServiceRoleForApplicationAutoScaling_ECSService",
  "Condition": {
    "StringLike": {
      "iam:AWSServiceName": "ecs.application-autoscaling.amazonaws.com"
    }
  }
}
```

**Service-Linked Roleとは:**
- Application Auto Scalingが自動作成する特殊なIAMロール
- 初回のみ作成、以降は再利用される
- ユーザーが手動で作成・管理する必要はない

---

## 💰 コスト影響

### 追加コスト

| サービス | 料金 | 備考 |
|---------|------|------|
| Application Auto Scaling | **無料** | AWS提供機能 |
| CloudWatch Alarms | **$0.20/月** | 2アラーム × $0.10 |
| 追加ECSタスク | **変動** | スケールアウト時のみ |

### ECSタスク追加コスト（スケールアウト時）

**1タスクあたり:**
- Fargate vCPU: $0.04656/時間 × 0.5 vCPU = $0.02328/時間
- Fargate Memory: $0.00511/時間 × 1GB = $0.00511/時間
- **合計**: 約$0.028/時間/タスク

**最大構成（8タスク稼働時）:**
- 追加タスク: 6個（2基本 + 6追加）
- 追加コスト: $0.168/時間 = **$121/月**（24時間フル稼働の場合）

**実際のコスト:**
- 通常時（2タスク）: 追加コストなし
- ピーク時のみスケールアウト: 月$10-30程度（予想）

---

## ✅ 検証結果

### 現在の稼働状況

```bash
$ aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --query 'services[0].[serviceName,desiredCount,runningCount]'

myteacher-production-app-service
2
2
```

### Terraformステート確認

```bash
$ terraform state list | grep -E "(appautoscaling|cloudwatch_metric_alarm)" | grep myteacher
module.myteacher.aws_appautoscaling_target.ecs
module.myteacher.aws_appautoscaling_policy.cpu
module.myteacher.aws_appautoscaling_policy.memory
module.myteacher.aws_cloudwatch_metric_alarm.high_cpu
module.myteacher.aws_cloudwatch_metric_alarm.high_memory
```

### CloudWatch Metricsでの確認

```bash
# CPU使用率（過去1時間）
aws cloudwatch get-metric-statistics \
  --namespace AWS/ECS \
  --metric-name CPUUtilization \
  --dimensions Name=ClusterName,Value=myteacher-production-cluster \
              Name=ServiceName,Value=myteacher-production-app-service \
  --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%S) \
  --end-time $(date -u +%Y-%m-%dT%H:%M:%S) \
  --period 300 \
  --statistics Average

# メモリ使用率（過去1時間）
aws cloudwatch get-metric-statistics \
  --namespace AWS/ECS \
  --metric-name MemoryUtilization \
  --dimensions Name=ClusterName,Value=myteacher-production-cluster \
              Name=ServiceName,Value=myteacher-production-app-service \
  --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%S) \
  --end-time $(date -u +%Y-%m-%dT%H:%M:%S) \
  --period 300 \
  --statistics Average
```

---

## 📝 更新されたドキュメント

1. **IAM_PERMISSIONS_MYTEACHER.md**
   - セクション10更新: Service-Linked Role作成権限追加

2. **IAM_PERMISSION_UPDATE_REQUEST_AUTOSCALING.md**
   - AWS管理者向けIAM権限追加依頼書を作成

3. **HTTPS_AND_SCALING_SETUP.md**
   - Phase 0.5-0完了ステータス反映

4. **PHASE0_IMPLEMENTATION_REPORT.md**
   - Phase 0.5-0セクション追加（詳細な実装記録）

---

## 🚀 次のステップ: Phase 0.5-1（HTTPS化）

### 必要な作業

1. **ドメイン取得**
   - Route 53で新規取得、または外部レジストラで取得
   - 例: `myteacher.jp`、`app.myteacher.jp`

2. **terraform.tfvars更新**
   ```hcl
   myteacher_domain_name = "app.myteacher.jp"
   myteacher_create_route53_zone = true  # 新規作成の場合
   myteacher_enable_https = true
   ```

3. **IAM権限追加（AWS管理者に依頼）**
   - Route 53: 11アクション
   - ACM: 8アクション
   - 依頼書: `IAM_PERMISSION_UPDATE_REQUEST_AUTOSCALING.md` 参照

4. **デプロイ実行**
   ```bash
   cd /home/ktr/mtdev/infrastructure/terraform
   terraform plan -target=module.myteacher
   terraform apply -target=module.myteacher -auto-approve
   ```

5. **ACM証明書DNS検証待機（5-15分）**

6. **HTTPS動作確認**
   ```bash
   curl -I https://app.myteacher.jp/health
   ```

### 予想コスト追加

- Route 53 Hosted Zone: **$0.50/月**
- ACM証明書: **無料**
- ALB HTTPS Listener: **追加料金なし**

**合計追加コスト**: 約$0.50/月

---

## 📚 関連ドキュメント

- [IAM権限ドキュメント](IAM_PERMISSIONS_MYTEACHER.md)
- [IAM権限追加依頼書](IAM_PERMISSION_UPDATE_REQUEST_AUTOSCALING.md)
- [HTTPS・スケーリングセットアップガイド](../HTTPS_AND_SCALING_SETUP.md)
- [Phase 0実装レポート](../PHASE0_IMPLEMENTATION_REPORT.md)

---

**作成日**: 2025年11月25日  
**プロジェクト**: MyTeacher マイクロサービス移行  
**ステータス**: Phase 0.5-0 完了、Phase 0.5-1 準備完了
