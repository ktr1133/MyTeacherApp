<?php

namespace Database\Factories;

use Laravel\Cashier\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Laravel\Cashier\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'default',
            'stripe_id' => 'sub_' . fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'stripe_status' => 'active',
            'stripe_price' => config('const.stripe.subscription_plans.family.price_id', 'price_test'),
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * アクティブなサブスクリプション
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);
    }

    /**
     * トライアル中のサブスクリプション
     */
    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * キャンセル済みサブスクリプション
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'canceled',
            'ends_at' => now()->addDays(30),
        ]);
    }

    /**
     * 期限切れサブスクリプション
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDays(1),
        ]);
    }

    /**
     * 未払いサブスクリプション
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'past_due',
        ]);
    }

    /**
     * 不完全なサブスクリプション
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'incomplete',
        ]);
    }

    /**
     * 不完全（期限切れ）サブスクリプション
     */
    public function incompleteExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'incomplete_expired',
        ]);
    }

    /**
     * 一時停止中のサブスクリプション
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'paused',
        ]);
    }

    /**
     * 指定したプランのサブスクリプション
     */
    public function plan(string $planName): static
    {
        $priceId = config("const.stripe.subscription_plans.{$planName}.price_id", 'price_test');
        
        return $this->state(fn (array $attributes) => [
            'stripe_price' => $priceId,
        ]);
    }

    /**
     * ファミリープラン
     */
    public function family(): static
    {
        return $this->plan('family');
    }

    /**
     * ビジネスプラン
     */
    public function business(): static
    {
        return $this->plan('business');
    }

    /**
     * 指定した数量のサブスクリプション
     */
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
