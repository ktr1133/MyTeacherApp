<?php

namespace App\Console\Commands;

use App\Models\TeacherAvatar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAvatarCost extends Command
{
    protected $signature = 'test:verify-avatar-cost {avatar_id}';

    protected $description = 'Verify avatar generation cost in AI usage logs and token transactions';

    public function handle()
    {
        $avatarId = $this->argument('avatar_id');

        $avatar = TeacherAvatar::find($avatarId);
        if (!$avatar) {
            $this->error("Avatar {$avatarId} not found");
            return 1;
        }

        $this->info("=== Avatar {$avatarId} Cost Verification ===");
        $this->info("User ID: {$avatar->user_id}");
        $this->info("Model: {$avatar->draw_model_version}");
        $this->info("Is Chibi: " . ($avatar->is_chibi ? 'yes' : 'no'));
        $this->info("Status: {$avatar->generation_status}");
        $this->info("Created: {$avatar->created_at}");
        $this->newLine();

        // AI Usage Logs
        $aiLogs = DB::table('ai_usage_logs')
            ->where('usable_type', TeacherAvatar::class)
            ->where('usable_id', $avatarId)
            ->select('service_type', 'service_detail', 'units_used', 'token_cost', 'created_at')
            ->get();

        $this->info("AI Usage Logs: {$aiLogs->count()} records");
        
        $imageGen = $aiLogs->where('service_type', 'like', '%diffusion%');
        $comments = $aiLogs->where('service_type', 'gpt-4');
        
        $this->info("  Image generation: {$imageGen->count()} records, {$imageGen->sum('token_cost')} tokens");
        if ($imageGen->count() > 0) {
            $this->info("    Per image: {$imageGen->first()->token_cost} tokens");
        }
        
        $this->info("  Comments (GPT-4): {$comments->count()} records, {$comments->sum('token_cost')} tokens");
        $this->info("  Total in logs: {$aiLogs->sum('token_cost')} tokens");
        $this->newLine();

        // Token Transactions
        $transactions = DB::table('token_transactions')
            ->where('user_id', $avatar->user_id)
            ->where('created_at', '>=', $avatar->created_at)
            ->where('created_at', '<=', $avatar->created_at->addMinutes(10))
            ->where('amount', '<', 0)
            ->orderBy('created_at')
            ->get();

        $this->info("Token Transactions: {$transactions->count()} records");
        foreach ($transactions as $tx) {
            $this->info("  {$tx->created_at}: {$tx->amount} tokens (reason: {$tx->reason})");
        }
        $this->info("  Total consumed: {$transactions->sum('amount')} tokens");
        $this->newLine();

        // 比較
        $loggedCost = $aiLogs->sum('token_cost');
        $consumedCost = abs($transactions->sum('amount'));
        
        if ($loggedCost == $consumedCost) {
            $this->info("✅ PASS: Logged cost matches consumed cost ({$loggedCost} tokens)");
            return 0;
        } else {
            $this->error("❌ FAIL: Cost mismatch");
            $this->error("  Logged in AI Usage Logs: {$loggedCost} tokens");
            $this->error("  Consumed in Transactions: {$consumedCost} tokens");
            $this->error("  Difference: " . ($loggedCost - $consumedCost) . " tokens");
            return 1;
        }
    }
}
