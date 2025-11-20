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
                'name' => '0.5Mトークン',
                'description' => 'スタンダードプラン',
                'token_amount' => 500000,
                'price' => 400,
                'sort_order' => 1,
                'is_active' => true,
                'features' => [
                    '画像生成最小モデル',
                ],
            ],
            [
                'name' => '2.5Mトークン',
                'description' => 'ラージプラン',
                'token_amount' => 2500000,
                'price' => 1800,
                'sort_order' => 2,
                'is_active' => true,
                'features' => [
                    'タスク分解 約100回',
                    '20%お得',
                ],
            ],
            [
                'name' => '5Mトークン',
                'description' => 'プレミアムプラン',
                'token_amount' => 5000000,
                'price' => 3400,
                'sort_order' => 3,
                'is_active' => true,
                'features' => [
                    'タスク分解 約200回',
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