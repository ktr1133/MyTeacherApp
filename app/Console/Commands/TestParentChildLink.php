<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\Profile\ProfileManagementService;
use App\Services\Profile\GroupService;

class TestParentChildLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:parent-child-link 
                            {child_user_id : 子ユーザーのID} 
                            {--parent-username=parent_test : 親ユーザー名}
                            {--parent-email=parent_test@example.com : 親メールアドレス}
                            {--rollback : テスト後にロールバック}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '親子紐づけとグループ作成のテスト（データベース直接操作）';

    protected ProfileManagementService $profileService;
    protected GroupService $groupService;

    public function __construct(
        ProfileManagementService $profileService,
        GroupService $groupService
    ) {
        parent::__construct();
        $this->profileService = $profileService;
        $this->groupService = $groupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $childUserId = $this->argument('child_user_id');
        $parentUsername = $this->option('parent-username');
        $parentEmail = $this->option('parent-email');
        $rollback = $this->option('rollback');

        $this->info("=== 親子紐づけテスト開始 ===\n");

        // 子ユーザー確認
        $child = User::find($childUserId);
        if (!$child) {
            $this->error("❌ 子ユーザー（ID: {$childUserId}）が見つかりません");
            return Command::FAILURE;
        }

        $this->info("✅ 子ユーザー確認:");
        $this->line("  - ID: {$child->id}");
        $this->line("  - Username: {$child->username}");
        $this->line("  - Email: {$child->email}");
        $this->line("  - 現在のGroup ID: " . ($child->group_id ?? 'NULL'));
        $this->newLine();

        // 既存グループチェック
        if ($child->group_id !== null) {
            $this->warn("⚠️  子ユーザーは既にグループ（ID: {$child->group_id}）に所属しています");
            if (!$this->confirm('続行しますか？', false)) {
                return Command::SUCCESS;
            }
        }

        try {
            DB::beginTransaction();

            // 親アカウント作成
            $this->info("📝 親アカウント作成中...");
            $parentData = [
                'username' => $parentUsername,
                'email' => $parentEmail,
                'name' => $parentUsername,
                'password' => Hash::make('password123'),
                'timezone' => 'Asia/Tokyo',
                'privacy_policy_version' => config('legal.current_versions.privacy_policy'),
                'terms_version' => config('legal.current_versions.terms_of_service'),
                'privacy_policy_agreed_at' => now(),
                'terms_agreed_at' => now(),
            ];

            $parent = $this->profileService->createUser($parentData);
            $this->info("✅ 親アカウント作成完了:");
            $this->line("  - ID: {$parent->id}");
            $this->line("  - Username: {$parent->username}");
            $this->line("  - Email: {$parent->email}");
            $this->newLine();

            // グループ作成 + 親子紐づけ
            $this->info("👨‍👧 グループ作成 + 親子紐づけ実行中...");
            $group = $this->groupService->createFamilyGroup($parent, $child);
            $this->info("✅ グループ作成完了:");
            $this->line("  - Group ID: {$group->id}");
            $this->line("  - Group Name: {$group->name}");
            $this->line("  - Master User ID: {$group->master_user_id}");
            $this->newLine();

            // 結果確認
            $parent->refresh();
            $child->refresh();

            $this->info("=== 最終結果 ===");
            $this->table(
                ['項目', '親', '子'],
                [
                    ['User ID', $parent->id, $child->id],
                    ['Username', $parent->username, $child->username],
                    ['Group ID', $parent->group_id, $child->group_id],
                    ['group_edit_flg', $parent->group_edit_flg ? '✓' : '✗', $child->group_edit_flg ? '✓' : '✗'],
                    ['parent_user_id', '-', $child->parent_user_id ?? 'NULL'],
                    ['parent_invitation_token', '-', $child->parent_invitation_token ? '削除済み' : 'NULL'],
                ]
            );

            if ($rollback) {
                DB::rollBack();
                $this->warn("🔄 トランザクションをロールバックしました（テストモード）");
                return Command::SUCCESS;
            }

            DB::commit();
            $this->info("\n✅ 親子紐づけテスト成功！データベースにコミットしました。");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ エラーが発生しました:");
            $this->error($e->getMessage());
            $this->newLine();
            $this->line("Stack Trace:");
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
