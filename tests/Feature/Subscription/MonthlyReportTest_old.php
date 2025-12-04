<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use App\Models\Group;
use App\Models\MonthlyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 月次レポートテスト
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
class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: 月次レポートを作成できる
     * 
     * @return void
     */
    public function test_can_create_monthly_report(): void
    {
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
        $this->assertDatabaseHas('monthly_reports', [
            'id' => $report->id,
            'group_id' => $group->id,
            'group_task_completed_count' => 10,
            'group_task_total_reward' => 5000,
        ]);
        
        // report_monthが正しく保存されていることを確認
        $this->assertEquals('2025-12-01', $report->fresh()->report_month->format('Y-m-d'));
    }

    /**
     * Test 2: 同一グループ・同一月のレポートは重複作成できない（unique制約）
     * 
     * @return void
     */
    public function test_cannot_create_duplicate_report_for_same_group_and_month(): void
    {
        // マイグレーション確認: unique(['group_id', 'report_month'])
        $group = Group::factory()->create();
        
        MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
        ]);

        // 同じグループ・月で再作成しようとすると例外
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
        ]);
    }

    /**
     * Test 3: member_task_summaryにJSON形式でメンバー別タスク集計を保存できる
     * 
     * @return void
     */
    public function test_can_store_member_task_summary_as_json(): void
    {
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
        $this->assertNotNull($report->member_task_summary);
        $this->assertEquals(5, $report->member_task_summary[$user1->id]['completed_count']);
        $this->assertEquals(3, $report->member_task_summary[$user2->id]['completed_count']);
    }

    /**
     * Test 4: group_task_detailsにJSON形式でグループタスク詳細を保存できる
     * 
     * @return void
     */
    public function test_can_store_group_task_details_as_json(): void
    {
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
        $this->assertNotNull($report->group_task_details);
        $this->assertCount(2, $report->group_task_details);
        $this->assertEquals('グループタスク1', $report->group_task_details[0]['title']);
        $this->assertEquals(2000, $report->group_task_details[1]['reward']);
    }

    /**
     * Test 5: AIコメントとトークン消費数を保存できる
     * 
     * @return void
     */
    public function test_can_store_ai_comment_and_tokens_used(): void
    {
        // マイグレーション確認: ai_comment (text, nullable), ai_comment_tokens_used (integer, default 0)
        $group = Group::factory()->create();

        $aiComment = "今月は先月と比べてグループタスクの完了数が増加しました。特にメンバーAさんの貢献が目立ちます。";

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'ai_comment' => $aiComment,
            'ai_comment_tokens_used' => 350,
        ]);

        $this->assertEquals($aiComment, $report->ai_comment);
        $this->assertEquals(350, $report->ai_comment_tokens_used);
    }

    /**
     * Test 6: group_task_summaryにメンバー別グループタスク集計を保存できる
     * 
     * @return void
     */
    public function test_can_store_group_task_summary_as_json(): void
    {
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
        $this->assertNotNull($report->group_task_summary);
        $this->assertEquals('Alice', $report->group_task_summary[$user1->id]['name']);
        $this->assertEquals(3, $report->group_task_summary[$user1->id]['completed_count']);
        $this->assertEquals(2, $report->group_task_summary[$user2->id]['completed_count']);
    }

    /**
     * Test 7: 前月比データを保存できる
     * 
     * @return void
     */
    public function test_can_store_previous_month_comparison_data(): void
    {
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

        $this->assertEquals(50, $report->normal_task_count_previous_month);
        $this->assertEquals(8, $report->group_task_count_previous_month);
        $this->assertEquals(4000, $report->reward_previous_month);
    }

    /**
     * Test 8: PDFファイルパスを保存できる
     * 
     * @return void
     */
    public function test_can_store_pdf_path(): void
    {
        // マイグレーション確認: pdf_path (string, nullable)
        $group = Group::factory()->create();

        $pdfPath = 'monthly-reports/group-' . $group->id . '/2025-12-report.pdf';

        $report = MonthlyReport::create([
            'group_id' => $group->id,
            'report_month' => '2025-12-01',
            'pdf_path' => $pdfPath,
        ]);

        $this->assertEquals($pdfPath, $report->pdf_path);
    }

    /**
     * Test 9: グループ削除時に月次レポートもカスケード削除される
     * 
     * @return void
     */
    public function test_monthly_reports_are_cascade_deleted_with_group(): void
    {
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

        $this->assertDatabaseHas('monthly_reports', ['id' => $report1->id]);
        $this->assertDatabaseHas('monthly_reports', ['id' => $report2->id]);

        // グループを物理削除（forceDelete）してカスケード削除をテスト
        // SoftDeletesのdelete()では外部キー制約が発火しない
        $group->forceDelete();

        // 月次レポートも削除される
        $this->assertDatabaseMissing('monthly_reports', ['id' => $report1->id]);
        $this->assertDatabaseMissing('monthly_reports', ['id' => $report2->id]);
    }

    /**
     * Test 10: report_enabled_untilがnullの場合はレポート生成不可とする（ビジネスロジック）
     * 
     * @return void
     */
    public function test_report_generation_disabled_when_report_enabled_until_is_null(): void
    {
        // マイグレーション確認: groups.report_enabled_until (date, nullable)
        $group = Group::factory()->create([
            'report_enabled_until' => null,  // レポート機能無効
        ]);

        // ビジネスロジック: report_enabled_until が null → レポート生成不可
        // （実際のロジックはService層で実装される想定）
        $canGenerateReport = $group->report_enabled_until !== null && $group->report_enabled_until >= now()->toDateString();

        $this->assertFalse($canGenerateReport);
    }
}
