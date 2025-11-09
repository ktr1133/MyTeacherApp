<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TokenPackage;

/**
 * トークンパッケージシーダー
 * 
 * 販売するトークン商品を登録します。
 */
class TokenPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => '1Mトークン',
                'description' => 'スタンダードプラン - タスク分解約20回分',
                'token_amount' => 1000000,
                'price' => 500,
                'sort_order' => 1,
                'is_active' => true,
                'features' => [
                    'タスク分解 約20回',
                    'AIアシスタント 約33回',
                    '日報生成 約50回',
                ],
            ],
            [
                'name' => '5Mトークン',
                'description' => 'ビジネスプラン - お得な大容量パック',
                'token_amount' => 5000000,
                'price' => 2000,
                'sort_order' => 2,
                'is_active' => true,
                'features' => [
                    'タスク分解 約100回',
                    'AIアシスタント 約166回',
                    '日報生成 約250回',
                    '20%お得',
                ],
            ],
            [
                'name' => '10Mトークン',
                'description' => 'エンタープライズプラン - 大規模チーム向け',
                'token_amount' => 10000000,
                'price' => 3500,
                'sort_order' => 3,
                'is_active' => true,
                'features' => [
                    'タスク分解 約200回',
                    'AIアシスタント 約333回',
                    '日報生成 約500回',
                    '30%お得',
                ],
            ],
        ];

        foreach ($packages as $package) {
            TokenPackage::create($package);
        }

        $this->command->info('トークンパッケージマスタを作成しました。');
    }
}