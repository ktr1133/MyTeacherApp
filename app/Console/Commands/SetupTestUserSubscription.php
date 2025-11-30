<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

class SetupTestUserSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:testuser-subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup family plan subscription for testuser (local development)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Family Plan subscription for testuser...');

        $user = User::where('username', 'testuser')->first();

        if (!$user) {
            $this->error('Error: testuser not found');
            return Command::FAILURE;
        }

        $group = $user->group;

        if (!$group) {
            $this->error('Error: testuser has no group');
            return Command::FAILURE;
        }

        // Groupにstripe_idを設定（なければ）
        if (!$group->stripe_id) {
            $group->stripe_id = 'cus_testuser_' . time();
            $this->info('Set stripe_id: ' . $group->stripe_id);
        }

        // ファミリープラン設定
        $group->subscription_active = true;
        $group->subscription_plan = 'family';
        $group->max_members = 6;
        $group->save();

        $this->info('Group updated!');
        $this->line('  subscription_active: true');
        $this->line('  subscription_plan: family');
        $this->line('  max_members: 6');

        // 既存のサブスクリプションを削除
        Subscription::where('user_id', $group->id)->delete();

        // Subscriptionレコード作成（GroupのBillableトレイトを使用）
        $subscription = new Subscription();
        $subscription->user_id = $group->id;  // GroupのID（Cashierのデフォルトカラム名）
        $subscription->type = 'default';
        $subscription->stripe_id = 'sub_testuser_' . time();
        $subscription->stripe_status = 'active';
        $subscription->stripe_price = config('const.stripe.subscription_plans.family.price_id');
        $subscription->quantity = 1;
        $subscription->trial_ends_at = now()->addDays(14);
        $subscription->save();

        $this->newLine();
        $this->info('Subscription created!');
        $this->line('  user_id (group_id): ' . $subscription->user_id);
        $this->line('  stripe_id: ' . $subscription->stripe_id);
        $this->line('  stripe_status: active');
        $this->line('  trial_ends_at: ' . $subscription->trial_ends_at->format('Y-m-d H:i:s'));

        $this->newLine();
        $this->info('✅ Setup complete!');
        $this->line('   testuser can now access: http://localhost:8080/subscriptions');
        $this->line('   Login URL: http://localhost:8080/login');

        return Command::SUCCESS;
    }
}
