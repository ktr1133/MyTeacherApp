<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserDeviceToken>
 */
class UserDeviceTokenFactory extends Factory
{
    protected $model = UserDeviceToken::class;

    /**
     * ファクトリーのデフォルト状態
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_token' => 'fcm_token_' . fake()->unique()->uuid(),
            'device_type' => fake()->randomElement(['ios', 'android']),
            'device_name' => fake()->randomElement(['iPhone 15 Pro', 'Pixel 8', 'iPad Pro', 'Galaxy S23']),
            'app_version' => fake()->randomElement(['1.0.0', '1.1.0', '1.2.0', '2.0.0']),
            'is_active' => true,
            'last_used_at' => now(),
        ];
    }

    /**
     * 非アクティブなデバイストークン
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * 30日以上未使用のデバイストークン
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function old(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'last_used_at' => now()->subDays(31),
            ];
        });
    }

    /**
     * iOSデバイストークン
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ios(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'device_type' => 'ios',
                'device_name' => fake()->randomElement(['iPhone 15 Pro', 'iPhone 14', 'iPad Pro']),
            ];
        });
    }

    /**
     * Androidデバイストークン
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function android(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'device_type' => 'android',
                'device_name' => fake()->randomElement(['Pixel 8', 'Galaxy S23', 'Xperia 1 V']),
            ];
        });
    }
}
