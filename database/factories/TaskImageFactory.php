<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskImage>
 * 
 * タスク画像モデルのファクトリ - テストデータ生成用
 */
class TaskImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaskImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'file_path' => 'task_approvals/' . fake()->uuid() . '.jpg',
            'approved_at' => null,
            'delete_at' => null,
        ];
    }

    /**
     * 承認済み画像とする
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => now(),
        ]);
    }
}
