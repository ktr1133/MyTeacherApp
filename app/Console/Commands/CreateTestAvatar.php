<?php

namespace App\Console\Commands;

use App\Models\TeacherAvatar;
use App\Models\User;
use App\Jobs\GenerateAvatarImagesJob;
use Illuminate\Console\Command;

class CreateTestAvatar extends Command
{
    protected $signature = 'test:create-avatar 
                            {user_id : User ID}
                            {--model=stable-diffusion-3.5-medium : AI model}
                            {--chibi : Generate chibi style}';

    protected $description = 'Create test avatar and dispatch generation job';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $model = $this->option('model');
        $isChibi = $this->option('chibi');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found");
            return 1;
        }

        $this->info("User {$userId} token balance: {$user->tokenBalance->current_balance}");

        $avatar = TeacherAvatar::create([
            'user_id' => $userId,
            'name' => 'Test Avatar ' . now()->format('Y-m-d H:i:s'),
            'draw_model_version' => $model,
            'is_chibi' => $isChibi,
            'is_transparent' => false,
            'image_size' => '512x512',
            'generation_status' => 'pending',
            'estimated_token_usage' => 23000,
        ]);

        $this->info("Created Avatar ID: {$avatar->id}");
        $this->info("Model: {$model}, Chibi: " . ($isChibi ? 'yes' : 'no'));
        
        // 期待コスト計算
        $costPerImage = match($model) {
            'stable-diffusion-3.5-medium' => 4600,
            'anything-v4.0' => 1000,
            'animagine-xl-3.1' => 400,
            default => 0,
        };
        $imageCount = 5; // 5枚（全身またはバストアップ × 5表情）
        $commentCost = 44; // GPT-4コメント約44トークン
        $expectedCost = ($imageCount * $costPerImage) + $commentCost;
        
        $this->info("Expected cost: {$imageCount} images x {$costPerImage} + comments {$commentCost} = {$expectedCost} tokens");
        
        GenerateAvatarImagesJob::dispatch($avatar);
        $this->info("Job dispatched. Check queue worker logs.");
        $this->info("To verify: php artisan test:verify-avatar-cost {$avatar->id}");

        return 0;
    }
}
