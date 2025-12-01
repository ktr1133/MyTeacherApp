<?php

namespace Database\Factories;

use App\Models\ScheduledGroupTask;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledGroupTask>
 */
class ScheduledGroupTaskFactory extends Factory
{
    protected $model = ScheduledGroupTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'requires_image' => fake()->boolean(),
            'requires_approval' => fake()->boolean(),
            'reward' => fake()->numberBetween(100, 10000),
            'assigned_user_id' => null,
            'auto_assign' => fake()->boolean(),
            'schedules' => [
                [
                    'type' => 'daily',
                    'time' => '09:00',
                ],
            ],
            'due_duration_days' => fake()->numberBetween(0, 7),
            'due_duration_hours' => fake()->numberBetween(0, 23),
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(90),
            'skip_holidays' => fake()->boolean(),
            'move_to_next_business_day' => fake()->boolean(),
            'delete_incomplete_previous' => fake()->boolean(),
            'is_active' => true,
            'paused_at' => null,
        ];
    }

    /**
     * スケジュールを一時停止状態にする
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'paused_at' => now(),
        ]);
    }

    /**
     * スケジュールを非アクティブにする
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 期限切れのスケジュールにする
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(90),
            'end_date' => now()->subDays(1),
        ]);
    }

    /**
     * 特定のユーザーに自動割り当てする
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_user_id' => $user->id,
            'auto_assign' => false,
        ]);
    }

    /**
     * 自動割り当てを有効にする
     */
    public function autoAssign(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_assign' => true,
            'assigned_user_id' => null,
        ]);
    }

    /**
     * 画像必須タスクにする
     */
    public function requiresImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_image' => true,
        ]);
    }

    /**
     * 承認必須タスクにする
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
        ]);
    }

    /**
     * 祝日スキップを有効にする
     */
    public function skipHolidays(): static
    {
        return $this->state(fn (array $attributes) => [
            'skip_holidays' => true,
            'move_to_next_business_day' => true,
        ]);
    }

    /**
     * 毎日実行のスケジュールにする
     */
    public function dailySchedule(string $time = '09:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedules' => [
                [
                    'type' => 'daily',
                    'time' => $time,
                ],
            ],
        ]);
    }

    /**
     * 毎週実行のスケジュールにする
     */
    public function weeklySchedule(array $days = [1], string $time = '09:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedules' => [
                [
                    'type' => 'weekly',
                    'days' => $days,
                    'time' => $time,
                ],
            ],
        ]);
    }

    /**
     * 毎月実行のスケジュールにする
     */
    public function monthlySchedule(array $dates = [1], string $time = '09:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedules' => [
                [
                    'type' => 'monthly',
                    'dates' => $dates,
                    'time' => $time,
                ],
            ],
        ]);
    }
}
