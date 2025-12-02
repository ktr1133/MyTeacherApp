<?php

// テスト用スクリプト: 月次レポート機能の動作確認

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Group;
use App\Models\User;
use App\Services\Report\MonthlyReportService;
use Illuminate\Support\Facades\DB;

echo "=== 月次レポート機能テスト ===\n\n";

// 1. データベース接続確認
echo "1. データベース接続確認...\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ DB接続成功\n\n";
} catch (\Exception $e) {
    echo "   ❌ DB接続失敗: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. テーブル構造確認
echo "2. monthly_reportsテーブル構造確認...\n";
try {
    $columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'monthly_reports' AND column_name IN ('ai_comment', 'ai_comment_tokens_used', 'group_task_summary')");
    foreach ($columns as $col) {
        echo "   ✅ {$col->column_name}: {$col->data_type}\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ テーブル確認失敗: " . $e->getMessage() . "\n\n";
}

// 3. グループ取得
echo "3. テストグループ取得...\n";
$group = Group::where('subscription_active', true)->first();
if ($group) {
    echo "   ✅ グループ取得成功: {$group->name} (ID: {$group->id})\n";
    echo "   - サブスクリプション: " . ($group->subscription_active ? 'アクティブ' : '非アクティブ') . "\n";
    echo "   - マスターユーザーID: {$group->master_user_id}\n\n";
} else {
    echo "   ❌ アクティブなグループが見つかりません\n\n";
    exit(1);
}

// 4. マスターユーザーとアバター確認
echo "4. マスターユーザーとアバター確認...\n";
$master = $group->master;
if ($master) {
    echo "   ✅ マスターユーザー: {$master->name}\n";
    $avatar = $master->teacher_avatar;
    if ($avatar) {
        echo "   ✅ アバター存在: tone={$avatar->tone}, enthusiasm={$avatar->enthusiasm}\n\n";
    } else {
        echo "   ⚠️  アバター未設定（デフォルト性格で生成されます）\n\n";
    }
} else {
    echo "   ❌ マスターユーザーが見つかりません\n\n";
}

// 5. MonthlyReportServiceのインスタンス化確認
echo "5. MonthlyReportServiceのインスタンス化...\n";
try {
    $service = app(\App\Services\Report\MonthlyReportServiceInterface::class);
    echo "   ✅ サービス取得成功: " . get_class($service) . "\n\n";
} catch (\Exception $e) {
    echo "   ❌ サービス取得失敗: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 6. 利用可能な月の取得
echo "6. 利用可能な月の取得...\n";
try {
    $availableMonths = $service->getAvailableMonths($group, 6);
    echo "   ✅ 利用可能な月: " . count($availableMonths) . "件\n";
    foreach (array_slice($availableMonths, 0, 3) as $month) {
        $hasReport = $month['has_report'] ? '✓' : '×';
        echo "   - {$month['label']} [{$hasReport}]\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ 月取得失敗: " . $e->getMessage() . "\n\n";
}

// 7. レポート生成テスト（今月）
echo "7. レポート生成テスト（今月: 2025-12）...\n";
try {
    $report = $service->generateMonthlyReport($group, '2025-12');
    echo "   ✅ レポート生成成功\n";
    echo "   - レポートID: {$report->id}\n";
    echo "   - 生成日時: {$report->generated_at}\n";
    echo "   - AIコメント: " . (empty($report->ai_comment) ? '未生成' : mb_substr($report->ai_comment, 0, 50) . '...') . "\n";
    echo "   - トークン使用: {$report->ai_comment_tokens_used}\n";
    echo "   - グループタスク集計: " . (empty($report->group_task_summary) ? '0件' : count(json_decode($report->group_task_summary, true)) . '件') . "\n\n";
} catch (\Exception $e) {
    echo "   ⚠️  レポート生成中にエラー: " . $e->getMessage() . "\n";
    echo "   （OpenAI API未設定の場合、AIコメントなしで生成されます）\n\n";
}

// 8. 表示用データ整形テスト
echo "8. 表示用データ整形テスト...\n";
try {
    $existingReport = $service->getMonthlyReport($group, '2025-12');
    if ($existingReport) {
        $formatted = $service->formatReportForDisplay($existingReport);
        echo "   ✅ データ整形成功\n";
        echo "   - 通常タスク合計: {$formatted['total_normal_tasks']}件\n";
        echo "   - グループタスク合計: {$formatted['total_group_tasks']}件\n";
        echo "   - 総報酬: {$formatted['total_reward']}ポイント\n";
        echo "   - メンバー数: " . count($formatted['member_task_summary']) . "人\n";
        echo "   - AIコメント: " . (empty($formatted['ai_comment']) ? 'なし' : 'あり') . "\n\n";
    } else {
        echo "   ⚠️  レポートが見つかりません\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ データ整形失敗: " . $e->getMessage() . "\n\n";
}

// 9. トレンドデータ取得テスト
echo "9. トレンドデータ取得テスト（6ヶ月分）...\n";
try {
    $trendData = $service->getTrendData($group, '2025-12', 6);
    echo "   ✅ トレンドデータ取得成功\n";
    echo "   - ラベル数: " . count($trendData['labels']) . "\n";
    echo "   - データセット数: " . count($trendData['datasets']) . "\n";
    echo "   - メンバー数: " . count($trendData['members']) . "\n";
    if (!empty($trendData['labels'])) {
        echo "   - 期間: " . $trendData['labels'][0] . " 〜 " . end($trendData['labels']) . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ トレンドデータ取得失敗: " . $e->getMessage() . "\n\n";
}

echo "=== テスト完了 ===\n";
echo "\n次のステップ: ブラウザで http://localhost:8080/reports/monthly にアクセスして画面表示を確認してください。\n";
