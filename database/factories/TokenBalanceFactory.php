<?php

namespace Database\Factories;

use App\Models\TokenBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenBalance>
 */
class TokenBalanceFactory extends Factory
{
    protected $model = TokenBalance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $freeBalance = $this->faker->numberBetween(0, 1000000);
        $paidBalance = $this->faker->numberBetween(0, 2000000);
        $balance = $freeBalance + $paidBalance;

        return [
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $this->faker->numberBetween(1, 100),
            'balance' => $balance,
            'free_balance' => $freeBalance,
            'paid_balance' => $paidBalance,
            'free_balance_reset_at' => now()->addMonth(),
            'total_consumed' => $this->faker->numberBetween(0, 500000),
            'monthly_consumed' => $this->faker->numberBetween(0, 100000),
            'monthly_consumed_reset_at' => now()->addMonth(),
        ];
    }

    /**
     * 残高ゼロ
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0,
            'free_balance' => 0,
            'paid_balance' => 0,
        ]);
    }
}
