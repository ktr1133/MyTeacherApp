<?php

namespace Database\Factories;

use App\Models\AvatarImage;
use App\Models\TeacherAvatar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvatarImage>
 */
class AvatarImageFactory extends Factory
{
    protected $model = AvatarImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_avatar_id' => TeacherAvatar::factory(),
            'image_type' => $this->faker->randomElement(['full_body', 'bust']),
            'expression_type' => $this->faker->randomElement(['normal', 'happy', 'sad', 'angry']),
            's3_path' => 'avatars/test/' . $this->faker->uuid . '.png',
            's3_url' => 'https://test-bucket.s3.amazonaws.com/avatars/test/' . $this->faker->uuid . '.png',
        ];
    }
}
