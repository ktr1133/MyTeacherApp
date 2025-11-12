<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AICostRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $rates = [
            // ===================================
            // OpenAI GPT-4 系
            // ===================================
            [
                'service_type' => 'gpt-4',
                'service_detail' => 'gpt-4-0613-input',
                'image_size' => null,
                'unit_cost_usd' => 0.000030,
                'token_conversion_rate' => 1,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI GPT-4 入力トークン単価',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'service_type' => 'gpt-4',
                'service_detail' => 'gpt-4-0613-output',
                'image_size' => null,
                'unit_cost_usd' => 0.000060,
                'token_conversion_rate' => 1,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI GPT-4 出力トークン単価',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ===================================
            // OpenAI GPT-3.5 Turbo
            // ===================================
            [
                'service_type' => 'gpt-3.5-turbo',
                'service_detail' => 'gpt-3.5-turbo-0125-input',
                'image_size' => null,
                'unit_cost_usd' => 0.0000005,
                'token_conversion_rate' => 1,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI GPT-3.5 Turbo 入力トークン単価',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'service_type' => 'gpt-3.5-turbo',
                'service_detail' => 'gpt-3.5-turbo-0125-output',
                'image_size' => null,
                'unit_cost_usd' => 0.0000015,
                'token_conversion_rate' => 1,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI GPT-3.5 Turbo 出力トークン単価',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ===================================
            // OpenAI DALL-E 3
            // ===================================
            [
                'service_type' => 'dalle3',
                'service_detail' => 'standard-input',
                'image_size' => '1024x1024',
                'unit_cost_usd' => 0.040,
                'token_conversion_rate' => 4000,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI DALL-E 3 Standard 1024x1024',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'service_type' => 'dalle3',
                'service_detail' => 'standard-output',
                'image_size' => '1024x1792',
                'unit_cost_usd' => 0.080,
                'token_conversion_rate' => 8000,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI DALL-E 3 Standard 1024x1792',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'service_type' => 'dalle3',
                'service_detail' => 'standard',
                'image_size' => '1792x1024',
                'unit_cost_usd' => 0.080,
                'token_conversion_rate' => 8000,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'OpenAI DALL-E 3 Standard 1792x1024',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ===================================
            // Replicate - Stable Diffusion
            // ===================================
            [
                'service_type' => 'anything-v4.0',
                'service_detail' => 'image_generation',
                'image_size' => '512x512',
                'unit_cost_usd' => 0.0023,
                'token_conversion_rate' => 230,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'Replicate anything-v4.0 画像生成 (512x512)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'service_type' => 'anything-v4.0',
                'service_detail' => 'image_generation',
                'image_size' => '768x768',
                'unit_cost_usd' => 0.0035,
                'token_conversion_rate' => 350,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'Replicate anything-v4.0 画像生成 (768x768)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'service_type' => 'anything-v4.0',
                'service_detail' => 'image_generation',
                'image_size' => '1024x1024',
                'unit_cost_usd' => 0.0050,
                'token_conversion_rate' => 500,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'Replicate anything-v4.0 画像生成 (1024x1024)',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ===================================
            // Replicate - Background Removal
            // ===================================
            [
                'service_type' => 'rembg',
                'service_detail' => 'background_removal',
                'image_size' => null, // サイズ不問
                'unit_cost_usd' => 0.0005,
                'token_conversion_rate' => 50,
                'is_active' => true,
                'effective_from' => $now,
                'note' => 'Replicate rembg 背景除去',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('ai_cost_rates')->insert($rates);
    }
}