<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'famicoapp@gmail.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password');

        // 管理者ユーザーを作成または更新
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => $adminEmail,
                'name' => 'Administrator',
                'password' => Hash::make($adminPassword),
                'group_id' => null,
                'group_edit_flg' => false,
                'is_admin' => true,
            ]
        );

        $this->command->info("管理者ユーザーを作成/更新しました: {$adminEmail}");

        // テストユーザーを作成（既に存在する場合はスキップ）
        User::firstOrCreate(
            ['username' => 'testuser'],
            [
                'email' => 'testuser@myteacher.local',
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'group_id' => null,
                'group_edit_flg' => false,
                'is_admin' => false,
            ]
        );

        $this->command->info('テストユーザーを作成しました: testuser@myteacher.local');
    }
}