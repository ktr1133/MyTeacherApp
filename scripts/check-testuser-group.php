<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// testuserの確認
$testuser = \App\Models\User::where('username', 'testuser')->first();
if (!$testuser) {
    echo "❌ testuserが見つかりません\n";
    exit(1);
}

echo "【testuserの情報】\n";
echo "ID: {$testuser->id}\n";
echo "Username: {$testuser->username}\n";
echo "Group ID: {$testuser->group_id}\n\n";

// グループメンバー確認
$members = \App\Models\User::where('group_id', $testuser->group_id)->get();
echo "【グループメンバー一覧】\n";
echo "グループID: {$testuser->group_id} / メンバー数: {$members->count()}人\n\n";

foreach ($members as $member) {
    $displayName = $member->display_name ?: '(なし)';
    echo "ID: {$member->id} | Username: {$member->username} | Display: {$displayName}\n";
}

// 既存の月次レポート確認
echo "\n【既存の月次レポート】\n";
$reports = \App\Models\MonthlyReport::where('group_id', $testuser->group_id)
    ->orderBy('report_month', 'asc')
    ->get();

if ($reports->isEmpty()) {
    echo "レポートなし\n";
} else {
    foreach ($reports as $report) {
        $month = \Carbon\Carbon::parse($report->report_month)->format('Y年m月');
        echo "{$month}: ID {$report->id}\n";
    }
}
