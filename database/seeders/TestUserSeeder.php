<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * テストユーザー作成Seeder
 * 
 * Phase 3統合テスト用のテストユーザーを作成します。
 * 
 * 作成されるユーザー:
 * - Email: test@example.com
 * - Password: password123
 * - Role: Parent (adult theme)
 * - グループ: "テストファミリー"
 * 
 * @see /home/ktr/mtdev/docs/operations/phase3-manual-testing-guide.md
 */
class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // ===== 既存ユーザー確認 =====
            $existingUser = User::where('email', 'test@example.com')->first();
            
            if ($existingUser) {
                $this->command->info('✅ Test user already exists (ID: ' . $existingUser->id . ')');
                $this->displayUserInfo($existingUser);
                return;
            }

            // ===== グループ作成 =====
            $group = Group::create([
                'name' => 'テストファミリー',
            ]);

            $this->command->info('✅ Test group created (ID: ' . $group->id . ')');


            // ===== テストユーザー作成（親） =====
            $user = User::create([
                'name' => 'テストユーザー（親）',
                'username' => 'test_parent',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'group_id' => $group->id,
                'theme' => 'adult',  // 大人テーマ
                'email_verified_at' => now(),
                // 通知設定（JSON）- usersテーブルのnotification_settingsカラム
                'notification_settings' => [
                    'push_enabled' => true,
                    'push_task_enabled' => true,
                    'push_group_enabled' => true,
                    'push_token_enabled' => true,
                    'push_system_enabled' => true,
                    'push_sound_enabled' => true,
                    'push_vibration_enabled' => true,
                ],
            ]);

            // ===== グループマスター設定 =====
            $group->update(['master_user_id' => $user->id]);

            $this->command->info('✅ Test user created successfully!');
            $this->command->newLine();
            $this->displayUserInfo($user);
            
            // ===== 子アカウント作成（オプション） =====
            $childUser = User::create([
                'name' => 'テストユーザー（子）',
                'username' => 'test_child',
                'email' => 'test-child@example.com',
                'password' => Hash::make('password123'),
                'group_id' => $group->id,
                'theme' => 'child',  // 子供テーマ
                'email_verified_at' => now(),
                // 通知設定（JSON）- usersテーブルのnotification_settingsカラム
                'notification_settings' => [
                    'push_enabled' => true,
                    'push_task_enabled' => true,
                    'push_group_enabled' => true,
                    'push_token_enabled' => true,
                    'push_system_enabled' => true,
                    'push_sound_enabled' => true,
                    'push_vibration_enabled' => true,
                ],
            ]);

            $this->command->info('✅ Child test user created (ID: ' . $childUser->id . ')');
            $this->command->newLine();
            $this->command->info('📋 Summary:');
            $this->command->info('  - Group: ' . $group->name . ' (ID: ' . $group->id . ')');
            $this->command->info('  - Parent User: ' . $user->email . ' / password123');
            $this->command->info('  - Child User: ' . $childUser->email . ' / password123');
        });
    }

    /**
     * ユーザー情報を表示
     */
    private function displayUserInfo(User $user): void
    {
        $this->command->info('📋 Test User Information:');
        $this->command->table(
            ['Property', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Username', $user->username],
                ['Email', $user->email],
                ['Password', 'password123'],
                ['Role', $user->role],
                ['Theme', $user->theme],
                ['Group ID', $user->group_id],
                ['Group Name', $user->group?->name ?? 'N/A'],
                ['Created At', $user->created_at->format('Y-m-d H:i:s')],
            ]
        );

        $this->command->newLine();
        $this->command->info('✨ You can now login with:');
        $this->command->info('   Email: test@example.com');
        $this->command->info('   Password: password123');
    }
}
