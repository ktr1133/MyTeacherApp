<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 * 
 * タスクモデルのファクトリ - テストデータ生成用
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'span' => fake()->numberBetween(1, 30),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'priority' => fake()->numberBetween(1, 5),
            'reward' => fake()->numberBetween(10, 1000),
            'requires_approval' => fake()->boolean(30),
            'requires_image' => fake()->boolean(20),
            'is_completed' => false,
            'approved_at' => null,
            'completed_at' => null,
            'source_proposal_id' => null,
            'assigned_by_user_id' => null,
            'approved_by_user_id' => null,
            'group_task_id' => null,
        ];
    }

    /**
     * タスクを完了済みとする
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * 承認が必要なタスクとする
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
        ]);
    }

    /**
     * 承認済みタスクとする
     */
    public function approved(User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => now(),
            'approved_by_user_id' => $approver?->id ?? User::factory()->create()->id,
        ]);
    }

    /**
     * グループタスクとする
     */
    public function groupTask(string $groupTaskId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'group_task_id' => $groupTaskId ?? (string) Str::uuid(),
            'assigned_by_user_id' => User::factory()->create()->id,
        ]);
    }

    /**
     * 画像が必要なタスクとする
     */
    public function requiresImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_image' => true,
        ]);
    }
}
