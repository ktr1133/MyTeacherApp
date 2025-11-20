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
        $settings = [
            [
                'amount' => 50000,
            ],
        ];

        foreach ($settings as $setting) {
            FreeTokenSetting::create($setting);
        }

        $this->command->info('無料トークン設定を作成しました。');
    }
}