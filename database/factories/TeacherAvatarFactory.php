<?php

namespace Database\Factories;

use App\Models\TeacherAvatar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherAvatar>
 */
class TeacherAvatarFactory extends Factory
{
    protected $model = TeacherAvatar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'seed' => $this->faker->numberBetween(1000, 9999999),
            'sex' => $this->faker->randomElement(['male', 'female']),
            'hair_color' => $this->faker->randomElement(['black', 'brown', 'blonde', 'red']),
            'hair_style' => $this->faker->randomElement(['short', 'long', 'medium']),
            'eye_color' => $this->faker->randomElement(['brown', 'blue', 'green', 'black']),
            'clothing' => $this->faker->randomElement(['casual', 'formal', 'sporty']),
            'accessory' => $this->faker->randomElement(['glasses', 'hat', null]),
            'body_type' => $this->faker->randomElement(['slim', 'normal', 'muscular']),
            'tone' => $this->faker->randomElement(['friendly', 'formal', 'casual']),
            'enthusiasm' => $this->faker->randomElement(['high', 'medium', 'low']),
            'formality' => $this->faker->randomElement(['formal', 'casual', 'neutral']),
            'humor' => $this->faker->randomElement(['high', 'moderate', 'low']),
            'draw_model_version' => 'stable-diffusion-xl-1024-v1-0',
            'is_transparent' => false,
            'is_chibi' => false,
            'estimated_token_usage' => 0,
            'generation_status' => 'pending',
            'last_generated_at' => null,
            'is_visible' => true,
        ];
    }

    /**
     * 生成完了状態のアバター
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_status' => 'completed',
            'last_generated_at' => now(),
        ]);
    }

    /**
     * 非表示状態のアバター
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
