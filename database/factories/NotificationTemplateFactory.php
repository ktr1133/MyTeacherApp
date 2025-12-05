<?php

namespace Database\Factories;

use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'source' => 'admin',
            'type' => 'general',
            'priority' => 'normal',
            'title' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
        ];
    }

    /**
     * 重要な通知
     */
    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'important',
        ]);
    }

    /**
     * システム通知
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'system',
        ]);
    }
}
