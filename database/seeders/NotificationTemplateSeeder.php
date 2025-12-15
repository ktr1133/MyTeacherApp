<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;
use App\Models\User;
use Carbon\Carbon;

/**
 * 通知テンプレートSeeder（テスト・開発環境用）
 * 
 * Push通知テスト用の通知テンプレートを作成します。
 * 本番環境では使用しないでください。
 * 
 * 実行方法:
 * php artisan db:seed --class=NotificationTemplateSeeder
 * 
 * @package Database\Seeders
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // 管理者ユーザー（test@example.com）を取得
        $adminUser = User::where('email', 'test@example.com')->first();
        
        if (!$adminUser) {
            $this->command->error('❌ Admin user (test@example.com) not found. Please run TestUserSeeder first.');
            return;
        }

        $now = Carbon::now();
        
        $templates = [
            // 1. タスク関連通知
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.group_task_created'),
                'priority' => 'normal',
                'title' => 'グループタスクが作成されました',
                'message' => '新しいグループタスクが作成されました。確認してください。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.approval_required'),
                'priority' => 'normal',
                'title' => 'タスクの承認が必要です',
                'message' => 'タスクが完了しました。承認してください。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.task_approved'),
                'priority' => 'normal',
                'title' => 'タスクが承認されました',
                'message' => 'タスクが承認され、トークンが付与されました。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.task_rejected'),
                'priority' => 'normal',
                'title' => 'タスクが却下されました',
                'message' => 'タスクが却下されました。再度取り組んでください。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            
            // 2. トークン関連通知
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.token_low'),
                'priority' => 'important',
                'title' => 'トークン残量低下',
                'message' => 'トークンの残量が少なくなっています。購入をご検討ください。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.token_depleted'),
                'priority' => 'important',
                'title' => 'トークンが不足しています',
                'message' => 'トークンが枯渇しました。AI機能を使用するにはトークンの購入が必要です。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.payment_success'),
                'priority' => 'normal',
                'title' => '決済が完了しました',
                'message' => 'トークンの購入が完了しました。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            
            // 3. システム通知
            [
                'sender_id' => $adminUser->id,
                'source' => 'system',
                'type' => config('const.notification_types.avatar_generated'),
                'priority' => 'info',
                'title' => 'アバター生成が完了しました',
                'message' => '先生のアバター画像の生成が完了しました。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => null,
                'updated_by' => $adminUser->id,
            ],
            
            // 4. 管理者通知
            [
                'sender_id' => $adminUser->id,
                'source' => 'admin',
                'type' => config('const.notification_types.admin_announcement'),
                'priority' => 'normal',
                'title' => '【テスト】お知らせ',
                'message' => 'これはテスト用のお知らせ通知です。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => 'test-announcement-2025',
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => $now->copy()->addDays(30),
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'admin',
                'type' => config('const.notification_types.admin_maintenance'),
                'priority' => 'important',
                'title' => '【テスト】メンテナンスのお知らせ',
                'message' => 'これはテスト用のメンテナンス通知です。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => $now->copy()->addDays(7),
                'updated_by' => $adminUser->id,
            ],
            [
                'sender_id' => $adminUser->id,
                'source' => 'admin',
                'type' => config('const.notification_types.admin_update'),
                'priority' => 'info',
                'title' => '【テスト】アプリ更新のお知らせ',
                'message' => 'これはテスト用のアップデート通知です。',
                'data' => null,
                'action_url' => null,
                'action_text' => null,
                'official_page_slug' => null,
                'target_type' => 'all',
                'target_ids' => null,
                'publish_at' => $now,
                'expire_at' => $now->copy()->addDays(14),
                'updated_by' => $adminUser->id,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::create($template);
        }

        $this->command->info('✅ Notification templates created successfully!');
        $this->command->info('');
        $this->command->info('📋 Created Templates:');
        $this->command->info('- Task notifications: 4 templates (group_task_created, approval_required, task_approved, task_rejected)');
        $this->command->info('- Token notifications: 3 templates (token_low, token_depleted, payment_success)');
        $this->command->info('- System notifications: 1 template (avatar_generated)');
        $this->command->info('- Admin notifications: 3 templates (admin_announcement, admin_maintenance, admin_update)');
        $this->command->info('');
        $this->command->info('Total: 11 templates');
    }
}
