<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FreeTokenSetting;

/**
 * 無料トークン設定シーダー
 * 
 * 販売するトークン商品を登録します。
 */
class FreeTokenSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存レコードがあれば削除（または最初の1件のみ残す）
        if (FreeTokenSetting::count() === 0) {
            FreeTokenSetting::create([
                'amount' => 50000,
            ]);
            $this->command->info('無料トークン設定を作成しました。');
        } else {
            $this->command->info('無料トークン設定は既に存在します。スキップしました。');
        }
    }
}