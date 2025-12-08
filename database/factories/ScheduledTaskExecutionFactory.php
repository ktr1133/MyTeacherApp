<?php

namespace Database\Factories;

use App\Models\ScheduledTaskExecution;
use App\Models\ScheduledGroupTask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ScheduledTaskExecution Factory
 * 
 * スケジュールタスク実行履歴のテストデータ生成
 * 
 * @extends Factory<ScheduledTaskExecution>
 */
class ScheduledTaskExecutionFactory extends Factory
{
    protected $model = ScheduledTaskExecution::class;

    /**
     * ファクトリのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scheduled_task_id' => ScheduledGroupTask::factory(),
            'executed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['success', 'failed', 'skipped']),
            'created_task_id' => null,
            'deleted_task_id' => null,
            'note' => null,
            'error_message' => null,
        ];
    }

    /**
     * 成功状態の実行履歴
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'created_task_id' => Task::factory(),
            'note' => 'タスク作成完了',
            'error_message' => null,
        ]);
    }

    /**
     * 失敗状態の実行履歴
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'created_task_id' => null,
            'deleted_task_id' => null,
            'note' => null,
            'error_message' => $this->faker->randomElement([
                'トークン不足: グループマスターのトークン残高が不足しています',
                'グループメンバーが見つかりません',
                'データベースエラー',
            ]),
        ]);
    }

    /**
     * スキップ状態の実行履歴
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'skipped',
            'created_task_id' => null,
            'deleted_task_id' => null,
            'note' => $this->faker->randomElement([
                '祝日のためスキップ',
                '実行期間外のためスキップ',
                '一時停止中のためスキップ',
            ]),
            'error_message' => null,
        ]);
    }

    /**
     * 前回未完了タスク削除を含む実行履歴
     */
    public function withDeletedTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'created_task_id' => Task::factory(),
            'deleted_task_id' => Task::factory(),
            'note' => '前回未完了タスク削除、新規タスク作成',
            'error_message' => null,
        ]);
    }
}
