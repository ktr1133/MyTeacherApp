<?php

namespace Database\Factories;

use App\Models\TaskProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskProposal>
 */
class TaskProposalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaskProposal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_task_text' => fake()->sentence(),
            'proposal_context' => fake()->sentence(), // NOT NULL制約対応（nullableではない）
            'proposed_tasks_json' => [
                ['title' => fake()->sentence(3)],
                ['title' => fake()->sentence(3)],
                ['title' => fake()->sentence(3)],
            ],
            'model_used' => 'gpt-4o-mini',
            'prompt_tokens' => fake()->numberBetween(50, 200),
            'completion_tokens' => fake()->numberBetween(100, 300),
            'total_tokens' => fake()->numberBetween(150, 500),
            'was_adopted' => false,
        ];
    }

    /**
     * 特定のユーザーに関連付ける
     *
     * @param User $user
     * @return static
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 採用済み状態にする
     *
     * @param array<int> $taskIds
     * @return static
     */
    public function adopted(array $taskIds = [1, 2, 3]): static
    {
        return $this->state(fn (array $attributes) => [
            'was_adopted' => true,
            'adopted_task_ids' => json_encode($taskIds),
            'adopted_proposed_tasks_json' => $attributes['proposed_tasks_json'],
        ]);
    }

    /**
     * 特定のタスク提案内容を設定する
     *
     * @param array $proposedTasks
     * @return static
     */
    public function withProposedTasks(array $proposedTasks): static
    {
        return $this->state(fn (array $attributes) => [
            'proposed_tasks_json' => $proposedTasks,
        ]);
    }

    /**
     * 特定のトークン使用量を設定する
     *
     * @param int $prompt
     * @param int $completion
     * @return static
     */
    public function withTokenUsage(int $prompt, int $completion): static
    {
        return $this->state(fn (array $attributes) => [
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $prompt + $completion,
        ]);
    }
}
