#!/bin/bash
set -e

# MyTeacher スケジュールタスク監視のCloudWatch設定スクリプト
# 使用方法: ./setup-scheduled-tasks-monitoring.sh [--dry-run]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_GROUP_NAME="/ecs/myteacher-production"
SNS_TOPIC_ARN="${SNS_TOPIC_ARN:-}"  # 環境変数から取得（要設定）

# カラー出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

DRY_RUN=false
if [[ "$1" == "--dry-run" ]]; then
    DRY_RUN=true
    echo -e "${YELLOW}DRY RUN MODE: 実際の変更は行いません${NC}\n"
fi

log_info() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

# SNSトピックの確認
if [[ -z "$SNS_TOPIC_ARN" ]]; then
    log_warn "SNS_TOPIC_ARN環境変数が設定されていません"
    log_warn "アラーム通知を有効化するには、以下を実行してください："
    echo "  export SNS_TOPIC_ARN='arn:aws:sns:ap-northeast-1:ACCOUNT_ID:myteacher-alerts'"
    echo ""
fi

# =====================================================
# 1. メトリクスフィルターの作成
# =====================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. メトリクスフィルターの設定"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# スケジュールタスク実行失敗のフィルター
FILTER_NAME_1="ScheduledTasksFailures"
FILTER_PATTERN_1='[time, request_id, level=ERROR*, msg="Scheduled tasks execution completed with failures*"]'
METRIC_NAME_1="ScheduledTasksFailureCount"

echo "フィルター: $FILTER_NAME_1"
if $DRY_RUN; then
    log_info "[DRY RUN] メトリクスフィルター作成をスキップ"
else
    if aws logs describe-metric-filters \
        --log-group-name "$LOG_GROUP_NAME" \
        --filter-name-prefix "$FILTER_NAME_1" \
        --query 'metricFilters[0].filterName' \
        --output text 2>/dev/null | grep -q "$FILTER_NAME_1"; then
        
        log_warn "メトリクスフィルター '$FILTER_NAME_1' は既に存在します（スキップ）"
    else
        aws logs put-metric-filter \
            --log-group-name "$LOG_GROUP_NAME" \
            --filter-name "$FILTER_NAME_1" \
            --filter-pattern "$FILTER_PATTERN_1" \
            --metric-transformations \
                metricName="$METRIC_NAME_1",metricNamespace="MyTeacher/ScheduledTasks",metricValue=1,defaultValue=0,unit=Count
        
        log_info "メトリクスフィルター '$FILTER_NAME_1' を作成しました"
    fi
fi

# 個別タスク失敗のフィルター
FILTER_NAME_2="ScheduledTaskIndividualFailures"
FILTER_PATTERN_2='[time, request_id, level=ERROR*, msg="Failed to execute scheduled task*"]'
METRIC_NAME_2="ScheduledTaskIndividualFailureCount"

echo "フィルター: $FILTER_NAME_2"
if $DRY_RUN; then
    log_info "[DRY RUN] メトリクスフィルター作成をスキップ"
else
    if aws logs describe-metric-filters \
        --log-group-name "$LOG_GROUP_NAME" \
        --filter-name-prefix "$FILTER_NAME_2" \
        --query 'metricFilters[0].filterName' \
        --output text 2>/dev/null | grep -q "$FILTER_NAME_2"; then
        
        log_warn "メトリクスフィルター '$FILTER_NAME_2' は既に存在します（スキップ）"
    else
        aws logs put-metric-filter \
            --log-group-name "$LOG_GROUP_NAME" \
            --filter-name "$FILTER_NAME_2" \
            --filter-pattern "$FILTER_PATTERN_2" \
            --metric-transformations \
                metricName="$METRIC_NAME_2",metricNamespace="MyTeacher/ScheduledTasks",metricValue=1,defaultValue=0,unit=Count
        
        log_info "メトリクスフィルター '$FILTER_NAME_2' を作成しました"
    fi
fi

# スケジュールタスク成功のフィルター（監視用）
FILTER_NAME_3="ScheduledTasksSuccess"
FILTER_PATTERN_3='[time, request_id, level=INFO*, msg="Scheduled tasks executed successfully*"]'
METRIC_NAME_3="ScheduledTasksSuccessCount"

echo "フィルター: $FILTER_NAME_3"
if $DRY_RUN; then
    log_info "[DRY RUN] メトリクスフィルター作成をスキップ"
else
    if aws logs describe-metric-filters \
        --log-group-name "$LOG_GROUP_NAME" \
        --filter-name-prefix "$FILTER_NAME_3" \
        --query 'metricFilters[0].filterName' \
        --output text 2>/dev/null | grep -q "$FILTER_NAME_3"; then
        
        log_warn "メトリクスフィルター '$FILTER_NAME_3' は既に存在します（スキップ）"
    else
        aws logs put-metric-filter \
            --log-group-name "$LOG_GROUP_NAME" \
            --filter-name "$FILTER_NAME_3" \
            --filter-pattern "$FILTER_PATTERN_3" \
            --metric-transformations \
                metricName="$METRIC_NAME_3",metricNamespace="MyTeacher/ScheduledTasks",metricValue=1,defaultValue=0,unit=Count
        
        log_info "メトリクスフィルター '$FILTER_NAME_3' を作成しました"
    fi
fi

echo ""

# =====================================================
# 2. CloudWatch Alarmの作成
# =====================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2. CloudWatch Alarmの設定"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [[ -z "$SNS_TOPIC_ARN" ]]; then
    log_warn "SNS_TOPIC_ARNが未設定のため、アラーム作成をスキップします"
    log_warn "通知を有効化するには、SNS_TOPIC_ARNを設定して再実行してください"
else
    # Alarm 1: スケジュールタスク失敗検知
    ALARM_NAME_1="MyTeacher-ScheduledTasks-Failures"
    echo "アラーム: $ALARM_NAME_1"
    
    if $DRY_RUN; then
        log_info "[DRY RUN] アラーム作成をスキップ"
    else
        aws cloudwatch put-metric-alarm \
            --alarm-name "$ALARM_NAME_1" \
            --alarm-description "スケジュールタスクの実行に失敗したタスクが存在する" \
            --metric-name "$METRIC_NAME_1" \
            --namespace "MyTeacher/ScheduledTasks" \
            --statistic Sum \
            --period 300 \
            --evaluation-periods 1 \
            --threshold 1 \
            --comparison-operator GreaterThanOrEqualToThreshold \
            --treat-missing-data notBreaching \
            --alarm-actions "$SNS_TOPIC_ARN"
        
        log_info "アラーム '$ALARM_NAME_1' を作成しました"
    fi

    # Alarm 2: スケジューラー停止検知
    ALARM_NAME_2="MyTeacher-ScheduledTasks-NoExecutions"
    echo "アラーム: $ALARM_NAME_2"
    
    if $DRY_RUN; then
        log_info "[DRY RUN] アラーム作成をスキップ"
    else
        aws cloudwatch put-metric-alarm \
            --alarm-name "$ALARM_NAME_2" \
            --alarm-description "スケジュールタスクが1時間以上実行されていない（スケジューラー停止）" \
            --metric-name "$METRIC_NAME_3" \
            --namespace "MyTeacher/ScheduledTasks" \
            --statistic Sum \
            --period 3600 \
            --evaluation-periods 1 \
            --threshold 1 \
            --comparison-operator LessThanThreshold \
            --treat-missing-data breaching \
            --alarm-actions "$SNS_TOPIC_ARN"
        
        log_info "アラーム '$ALARM_NAME_2' を作成しました"
    fi
fi

echo ""

# =====================================================
# 3. 設定確認
# =====================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3. 設定確認"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if ! $DRY_RUN; then
    echo "作成されたメトリクスフィルター:"
    aws logs describe-metric-filters \
        --log-group-name "$LOG_GROUP_NAME" \
        --query 'metricFilters[?starts_with(filterName, `ScheduledTask`)].filterName' \
        --output table

    if [[ -n "$SNS_TOPIC_ARN" ]]; then
        echo ""
        echo "作成されたアラーム:"
        aws cloudwatch describe-alarms \
            --alarm-name-prefix "MyTeacher-ScheduledTasks" \
            --query 'MetricAlarms[].AlarmName' \
            --output table
    fi
fi

echo ""
log_info "セットアップが完了しました"

# =====================================================
# 4. 次のステップ
# =====================================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "次のステップ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. CloudWatchコンソールで設定を確認:"
echo "   https://console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#alarmsV2:"
echo ""
echo "2. テストアラームを送信:"
echo "   aws cloudwatch set-alarm-state \\"
echo "     --alarm-name MyTeacher-ScheduledTasks-Failures \\"
echo "     --state-value ALARM \\"
echo "     --state-reason 'Testing alarm notification'"
echo ""
echo "3. メトリクスを確認:"
echo "   https://console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#metricsV2:graph=~();namespace=MyTeacher/ScheduledTasks"
echo ""
