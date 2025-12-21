<?php

namespace Tests\Feature\Avatar;

use Tests\TestCase;
use App\Models\User;
use App\Models\TeacherAvatar;
use App\Jobs\GenerateAvatarImagesJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * モデル別プロンプト生成のテスト
 */
class ModelSpecificPromptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Booru-styleモデル判定テスト
     */
    public function test_booru_style_model_detection(): void
    {
        $user = User::factory()->create(['theme' => 'adult']);
        
        // anything-v4.0
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $user->id,
            'draw_model_version' => 'anything-v4.0',
            'sex' => 'female',
            'hair_color' => 'black',
            'hair_style' => 'long',
            'eye_color' => 'brown',
            'clothing' => 'suit',
            'accessory' => 'glasses',
            'body_type' => 'average',
            'is_chibi' => false,
        ]);

        $job = new GenerateAvatarImagesJob($avatar->id);
        $reflection = new \ReflectionClass($job);
        
        // isBooruStyleModelメソッドをテスト
        $method = $reflection->getMethod('isBooruStyleModel');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($job, 'anything-v4.0'));
        $this->assertTrue($method->invoke($job, 'animagine-xl-3.1'));
        $this->assertFalse($method->invoke($job, 'stable-diffusion-3.5-medium'));
    }

    /**
     * Booru-styleプロンプト生成テスト
     */
    public function test_booru_style_prompt_generation(): void
    {
        $user = User::factory()->create(['theme' => 'adult']);
        
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $user->id,
            'draw_model_version' => 'anything-v4.0',
            'sex' => 'female',
            'hair_color' => 'black',
            'hair_style' => 'long',
            'eye_color' => 'brown',
            'clothing' => 'suit',
            'accessory' => 'glasses',
            'body_type' => 'average',
            'is_chibi' => false,
            'seed' => 12345,
        ]);

        $job = new GenerateAvatarImagesJob($avatar->id);
        $reflection = new \ReflectionClass($job);
        
        // buildBasePromptBooruStyleメソッドをテスト
        $method = $reflection->getMethod('buildBasePromptBooruStyle');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($job, $avatar);
        
        // Booru-style特有のタグが含まれているか確認
        // NSFW回避のため1girlタグは削除済み
        $this->assertStringContainsString('solo', $prompt);
        $this->assertStringContainsString('long_hair', $prompt);
        $this->assertStringContainsString('black_hair', $prompt);
        $this->assertStringContainsString('brown_eyes', $prompt);
        // NSFW回避のためbusiness_suit→simple_outfitに変更
        $this->assertStringContainsString('simple_outfit', $prompt);
        // NSFW回避のためglasses→eyewearに変更
        $this->assertStringContainsString('eyewear', $prompt);
        $this->assertStringContainsString('anime', $prompt);
        
        // 自然言語形式（スペース区切り）は含まれていない
        $this->assertStringNotContainsString('wearing', $prompt);
        $this->assertStringNotContainsString('character ID', $prompt);
    }

    /**
     * 自然言語プロンプト生成テスト
     */
    public function test_natural_language_prompt_generation(): void
    {
        $user = User::factory()->create(['theme' => 'child']);
        
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $user->id,
            'draw_model_version' => 'stable-diffusion-3.5-medium',
            'sex' => 'male',
            'hair_color' => 'brown',
            'hair_style' => 'short',
            'eye_color' => 'blue',
            'clothing' => 'casual',
            'accessory' => null,
            'body_type' => 'slim',
            'is_chibi' => false,
            'seed' => 67890,
        ]);

        $job = new GenerateAvatarImagesJob($avatar->id);
        $reflection = new \ReflectionClass($job);
        
        // buildBasePromptNaturalLanguageメソッドをテスト
        $method = $reflection->getMethod('buildBasePromptNaturalLanguage');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($job, $avatar);
        
        // 自然言語形式が含まれているか確認
        $this->assertStringContainsString('male', $prompt);
        $this->assertStringContainsString('short hair', $prompt);
        $this->assertStringContainsString('brown hair', $prompt);
        $this->assertStringContainsString('blue eyes', $prompt);
        $this->assertStringContainsString('wearing casual clothes', $prompt);
        $this->assertStringContainsString('supporter character ID', $prompt);
        $this->assertStringContainsString('bright sparkling eyes', $prompt); // 子どもテーマ
        
        // Booru-style（アンダースコア）は含まれていない
        $this->assertStringNotContainsString('1boy', $prompt);
        $this->assertStringNotContainsString('short_hair', $prompt);
        $this->assertStringNotContainsString('brown_eyes', $prompt);
    }

    /**
     * ちびキャラプロンプトテスト（Booru-style）
     */
    public function test_chibi_prompt_booru_style(): void
    {
        $user = User::factory()->create(['theme' => 'child']);
        
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $user->id,
            'draw_model_version' => 'animagine-xl-3.1',
            'sex' => 'female',
            'is_chibi' => true,
        ]);

        $job = new GenerateAvatarImagesJob($avatar->id);
        $reflection = new \ReflectionClass($job);
        
        $method = $reflection->getMethod('buildBasePromptBooruStyle');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($job, $avatar);
        
        // ちびキャラ専用タグが含まれているか確認
        $this->assertStringContainsString('chibi', $prompt);
        $this->assertStringContainsString('super_deformed', $prompt);
        $this->assertStringContainsString('cute', $prompt);
        
        // 子どもテーマ専用タグも含まれる（NSFW回避のため調整済み）
        $this->assertStringContainsString('bright_expression', $prompt);
        $this->assertStringContainsString('friendly_look', $prompt);
    }

    /**
     * 表情・ポーズのBooru-style変換テスト
     */
    public function test_expression_and_pose_conversion_to_booru_style(): void
    {
        $user = User::factory()->create();
        $avatar = TeacherAvatar::factory()->create(['user_id' => $user->id]);

        $job = new GenerateAvatarImagesJob($avatar->id);
        $reflection = new \ReflectionClass($job);
        
        // 表情変換テスト（Danbooru顔文字とNSFW回避タグ追加済み）
        $expressionMethod = $reflection->getMethod('convertExpressionToBooruStyle');
        $expressionMethod->setAccessible(true);
        
        $this->assertEquals('smile, :d, happy, cheerful', $expressionMethod->invoke($job, 'happy expression'));
        $this->assertEquals('sad, :(, downcast_eyes, melancholy', $expressionMethod->invoke($job, 'sad expression'));
        $this->assertEquals('surprised, o_o, open_mouth, wide_eyes, shocked', $expressionMethod->invoke($job, 'surprised expression'));
        
        // ポーズ変換テスト（simple_pose追加済み）
        $poseMethod = $reflection->getMethod('convertPoseToBooruStyle');
        $poseMethod->setAccessible(true);
        
        $this->assertEquals('full_body, standing, simple_pose', $poseMethod->invoke($job, 'full body standing pose'));
        $this->assertEquals('upper_body, portrait, centered', $poseMethod->invoke($job, 'upper body portrait'));
    }
}
