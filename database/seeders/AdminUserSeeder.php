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
        // 管理者ユーザーを作成（既に存在する場合はスキップ）
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('password'), // 本番環境では強力なパスワードに変更
                'group_id' => null,
                'group_edit_flg' => false,
                'is_admin' => true,
            ]
        );

        // テストユーザーを作成（既に存在する場合はスキップ）
        User::firstOrCreate(
            ['username' => 'testuser'],
            [
                'password' => Hash::make('password'), // 本番環境では強力なパスワードに変更
                'group_id' => null,
                'group_edit_flg' => false,
                'is_admin' => false,
            ]
        );
    }
}