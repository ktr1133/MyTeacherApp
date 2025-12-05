<?php

namespace Database\Factories;

use App\Models\MonthlyReport;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonthlyReport>
 */
class MonthlyReportFactory extends Factory
{
    protected $model = MonthlyReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportMonth = now()->subMonth()->startOfMonth();
        
        return [
            'group_id' => Group::factory(),
            'report_month' => $reportMonth,
            'generated_at' => now(),
            'member_task_summary' => [
                'member_1' => [
                    'normal_task_count' => fake()->numberBetween(10, 50),
                    'group_task_count' => fake()->numberBetween(5, 20),
                    'total_reward' => fake()->numberBetween(1000, 5000),
                ],
            ],
            'group_task_completed_count' => fake()->numberBetween(20, 100),
            'group_task_total_reward' => fake()->numberBetween(5000, 20000),
            'group_task_details' => [
                [
                    'task_title' => fake()->sentence(),
                    'count' => fake()->numberBetween(1, 10),
                    'reward' => fake()->numberBetween(100, 1000),
                ],
            ],
            'group_task_summary' => [
                'top_category' => 'study',
                'total_count' => fake()->numberBetween(20, 100),
                'avg_completion_rate' => fake()->randomFloat(2, 0.5, 1.0),
            ],
            'ai_comment' => fake()->paragraph(),
            'ai_comment_tokens_used' => fake()->numberBetween(100, 500),
            'normal_task_count_previous_month' => fake()->numberBetween(10, 50),
            'group_task_count_previous_month' => fake()->numberBetween(5, 20),
            'reward_previous_month' => fake()->numberBetween(1000, 5000),
            'pdf_path' => null,
        ];
    }

    /**
     * 特定の年月を指定
     */
    public function forMonth(string $yearMonth): static
    {
        return $this->state(fn (array $attributes) => [
            'report_month' => \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth(),
        ]);
    }

    /**
     * PDF生成済み
     */
    public function withPdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'pdf_path' => 'reports/monthly/' . fake()->uuid() . '.pdf',
        ]);
    }

    /**
     * AIコメントなし
     */
    public function withoutAiComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_comment' => null,
            'ai_comment_tokens_used' => 0,
        ]);
    }
}
