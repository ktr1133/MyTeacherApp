<?php

use App\Models\User;
use App\Models\Group;
use App\Models\MonthlyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 月次レポートテスト（Pest形式）
 * 
 * マイグレーション確認済み:
 * - 2025_11_30_112052_create_monthly_reports_table.php
 *   - monthly_reports.group_id (foreign key to groups.id, onDelete('cascade'))
 *   - monthly_reports.report_month (date, YYYY-MM-01形式)
 *   - monthly_reports.generated_at (timestamp, nullable)
 *   - monthly_reports.member_task_summary (json, nullable)
 *   - monthly_reports.group_task_completed_count (integer, default 0)
 *   - monthly_reports.group_task_total_reward (integer, default 0)
 *   - monthly_reports.group_task_details (json, nullable)
 *   - monthly_reports.normal_task_count_previous_month (integer, default 0)
 *   - monthly_reports.group_task_count_previous_month (integer, default 0)
 *   - monthly_reports.reward_previous_month (integer, default 0)
 *   - monthly_reports.pdf_path (string, nullable)
 * 
 * - 2025_12_01_145451_add_ai_comment_and_group_task_summary_to_monthly_reports_table.php
 *   - monthly_reports.ai_comment (text, nullable)
 *   - monthly_reports.ai_comment_tokens_used (integer, default 0)
 *   - monthly_reports.group_task_summary (json, nullable)
 * 
 * - 2025_11_30_111950_add_subscription_fields_to_groups_table.php
 *   - groups.report_enabled_until (date, nullable)
 */

uses(RefreshDatabase::class);

describe('Monthly Report', function () {
    test('月次レポートを作成できる', function () {
        // マイグレーション確認: 基本カラムが全て存在
        $group = Group::factory()->create();
        
        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',  // date型（YYYY-MM-DD形式）
            'generated_at' => now(),
            'group_task_completed_count' => 10,
            'group_task_total_reward' => 5000,
        ]);

        // DBではdate型として保存されるため、日付部分のみで比較
        expect($report)->not->toBeNull();
        expect($report->group_id)->toBe($group->id);
        expect($report->group_task_completed_count)->toBe(10);
        expect($report->group_task_total_reward)->toBe(5000);
        
        // report_monthが正しく保存されていることを確認
        expect($report->fresh()->report_month->format('Y-m-d'))->toBe('2025-12-01');
    });

    test('同一グループ・同一月のレポートは重複作成できない（unique制約）', function () {
        // マイグレーション確認: unique(['group_id', 'report_month'])
        $group = Group::factory()->create();
        
        MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
        ]);

        // 同じグループ・月で再作成しようとすると例外
        expect(fn() => MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('member_task_summaryにJSON形式でメンバー別タスク集計を保存できる', function () {
        // マイグレーション確認: member_task_summary (json, nullable)
        $group = Group::factory()->create();
        $user1 = User::factory()->create(['group_id' => $group->id]);
        $user2 = User::factory()->create(['group_id' => $group->id]);

        $summary = [
            $user1->id => [
                'completed_count' => 5,
                'tasks' => [
                    ['title' => 'Task 1', 'completed_at' => '2025-12-05'],
                    ['title' => 'Task 2', 'completed_at' => '2025-12-10'],
                ],
            ],
            $user2->id => [
                'completed_count' => 3,
                'tasks' => [
                    ['title' => 'Task A', 'completed_at' => '2025-12-08'],
                ],
            ],
        ];

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'member_task_summary' => $summary,
        ]);

        // JSON保存・取得確認
        expect($report->member_task_summary)->not->toBeNull();
        expect($report->member_task_summary[$user1->id]['completed_count'])->toBe(5);
        expect($report->member_task_summary[$user2->id]['completed_count'])->toBe(3);
    });

    test('group_task_detailsにJSON形式でグループタスク詳細を保存できる', function () {
        // マイグレーション確認: group_task_details (json, nullable)
        $group = Group::factory()->create();

        $details = [
            ['task_id' => 1, 'title' => 'グループタスク1', 'reward' => 1000, 'completed_at' => '2025-12-05'],
            ['task_id' => 2, 'title' => 'グループタスク2', 'reward' => 2000, 'completed_at' => '2025-12-10'],
        ];

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'group_task_details' => $details,
            'group_task_completed_count' => 2,
            'group_task_total_reward' => 3000,
        ]);

        // JSON保存・取得確認
        expect($report->group_task_details)->not->toBeNull();
        expect($report->group_task_details)->toHaveCount(2);
        expect($report->group_task_details[0]['title'])->toBe('グループタスク1');
        expect($report->group_task_details[1]['reward'])->toBe(2000);
    });

    test('AIコメントとトークン消費数を保存できる', function () {
        // マイグレーション確認: ai_comment (text, nullable), ai_comment_tokens_used (integer, default 0)
        $group = Group::factory()->create();

        $aiComment = "今月は先月と比べてグループタスクの完了数が増加しました。特にメンバーAさんの貢献が目立ちます。";

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'ai_comment' => $aiComment,
            'ai_comment_tokens_used' => 350,
        ]);

        expect($report->ai_comment)->toBe($aiComment);
        expect($report->ai_comment_tokens_used)->toBe(350);
    });

    test('group_task_summaryにメンバー別グループタスク集計を保存できる', function () {
        // マイグレーション確認: group_task_summary (json, nullable)
        $group = Group::factory()->create();
        $user1 = User::factory()->create(['group_id' => $group->id, 'name' => 'Alice']);
        $user2 = User::factory()->create(['group_id' => $group->id, 'name' => 'Bob']);

        $summary = [
            $user1->id => [
                'name' => 'Alice',
                'completed_count' => 3,
                'reward' => 3000,
                'tasks' => [
                    ['title' => 'Task X', 'reward' => 1000, 'completed_at' => '2025-12-05', 'tags' => ['urgent']],
                ],
            ],
            $user2->id => [
                'name' => 'Bob',
                'completed_count' => 2,
                'reward' => 2000,
                'tasks' => [
                    ['title' => 'Task Y', 'reward' => 1000, 'completed_at' => '2025-12-08', 'tags' => []],
                ],
            ],
        ];

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'group_task_summary' => $summary,
        ]);

        // JSON保存・取得確認
        expect($report->group_task_summary)->not->toBeNull();
        expect($report->group_task_summary[$user1->id]['name'])->toBe('Alice');
        expect($report->group_task_summary[$user1->id]['completed_count'])->toBe(3);
        expect($report->group_task_summary[$user2->id]['completed_count'])->toBe(2);
    });

    test('前月比データを保存できる', function () {
        // マイグレーション確認: 
        // - normal_task_count_previous_month (integer, default 0)
        // - group_task_count_previous_month (integer, default 0)
        // - reward_previous_month (integer, default 0)
        $group = Group::factory()->create();

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'normal_task_count_previous_month' => 50,
            'group_task_count_previous_month' => 8,
            'reward_previous_month' => 4000,
        ]);

        expect($report->normal_task_count_previous_month)->toBe(50);
        expect($report->group_task_count_previous_month)->toBe(8);
        expect($report->reward_previous_month)->toBe(4000);
    });

    test('PDFファイルパスを保存できる', function () {
        // マイグレーション確認: pdf_path (string, nullable)
        $group = Group::factory()->create();

        $pdfPath = 'monthly-reports/group-' . $group->id . '/2025-12-report.pdf';

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'pdf_path' => $pdfPath,
        ]);

        expect($report->pdf_path)->toBe($pdfPath);
    });

    test('グループ削除時に月次レポートもカスケード削除される', function () {
        // マイグレーション確認: foreign('group_id') onDelete('cascade')
        $group = Group::factory()->create();

        $report1 = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-11-01',
        ]);

        $report2 = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
        ]);

        expect($report1->id)->not->toBeNull();
        expect($report2->id)->not->toBeNull();

        // グループを物理削除（forceDelete）してカスケード削除をテスト
        // SoftDeletesのdelete()では外部キー制約が発火しない
        $group->forceDelete();

        // 月次レポートも削除される
        expect(MonthlyReport::find($report1->id))->toBeNull();
        expect(MonthlyReport::find($report2->id))->toBeNull();
    });

    test('report_enabled_untilがnullの場合はレポート生成不可とする（ビジネスロジック）', function () {
        // マイグレーション確認: groups.report_enabled_until (date, nullable)
        $group = Group::factory()->create([
            'report_enabled_until' => null,  // レポート機能無効
        ]);

        // ビジネスロジック: report_enabled_until が null → レポート生成不可
        // （実際のロジックはService層で実装される想定）
        $canGenerateReport = $group->report_enabled_until !== null && $group->report_enabled_until >= now()->toDateString();

        expect($canGenerateReport)->toBeFalse();
    });
});
