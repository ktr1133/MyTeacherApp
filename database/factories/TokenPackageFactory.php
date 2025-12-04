<?php

namespace Database\Factories;

use App\Models\TokenPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenPackage>
 */
class TokenPackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TokenPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // モデルのfillableプロパティから取得したカラムのみ定義
        // $fillable = (new TokenPackage())->getFillable();
        // fillable: name, description, token_amount, price, stripe_price_id, 
        //           stripe_product_id, sort_order, is_active, is_subscription, features
        
        return [
            'name' => fake()->randomElement(['0.5Mトークン', '1Mトークン', '2.5Mトークン', '5Mトークン']),
            'description' => fake()->sentence(),
            'token_amount' => fake()->randomElement([500000, 1000000, 2500000, 5000000]),
            'price' => fake()->numberBetween(400, 5000),
            'stripe_price_id' => 'price_test_' . fake()->uuid(),
            'stripe_product_id' => 'prod_test_' . fake()->uuid(),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'is_subscription' => false,
            'features' => null,
        ];
    }

    /**
     * Indicate that the package is inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the package is a subscription.
     *
     * @return static
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_subscription' => true,
        ]);
    }
}
