<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'master_user_id' => User::factory(),
            // サブスクリプション関連のデフォルト値
            'subscription_active' => false,
            'subscription_plan' => null,
            'max_members' => 5, // 無料プランのデフォルト
            'max_groups' => 1,
            'free_group_task_limit' => 5, // 無料プランのデフォルト
            'group_task_count_current_month' => 0,
            'group_task_count_reset_at' => Carbon::now()->endOfMonth(),
            'free_trial_days' => 0,
            'report_enabled_until' => null,
        ];
    }

    /**
     * サブスクリプション有効なグループの状態を示す
     */
    public function subscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_active' => true,
            'subscription_plan' => 'price_test_plan',
            'max_members' => 999, // 実質無制限
            'max_groups' => 999,
            'free_group_task_limit' => 999, // 実質無制限
        ]);
    }

    /**
     * 無料プランのグループの状態を示す
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_active' => false,
            'subscription_plan' => null,
            'max_members' => 5,
            'max_groups' => 1,
            'free_group_task_limit' => 5,
        ]);
    }

    /**
     * タスク作成上限に達したグループの状態を示す
     */
    public function taskLimitReached(): static
    {
        return $this->state(function (array $attributes) {
            $limit = $attributes['free_group_task_limit'] ?? 5;
            return [
                'subscription_active' => false,
                'group_task_count_current_month' => $limit,
            ];
        });
    }

    /**
     * リセット期限切れのグループの状態を示す
     */
    public function resetExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_task_count_reset_at' => Carbon::now()->subDay(),
        ]);
    }
}
