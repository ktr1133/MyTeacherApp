<?php

namespace Database\Factories;

use App\Models\TokenTransaction;
use App\Models\TokenBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenTransaction>
 */
class TokenTransactionFactory extends Factory
{
    protected $model = TokenTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['consume', 'purchase', 'grant', 'free_reset']);
        $amount = $type === 'consume' ? -$this->faker->numberBetween(1000, 50000) : $this->faker->numberBetween(100000, 1000000);
        $tokenBalance = TokenBalance::factory();

        return [
            'tokenable_type' => $tokenBalance->tokenable_type ?? 'App\\Models\\User',
            'tokenable_id' => $tokenBalance->tokenable_id ?? 1,
            'user_id' => 1,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->faker->numberBetween(0, 2000000),
            'reason' => $type === 'consume' ? 'AI機能: タスク分解' : 'トークン購入',
            'related_type' => null,
            'related_id' => null,
        ];
    }
}
