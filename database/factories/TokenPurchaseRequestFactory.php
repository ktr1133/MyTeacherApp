<?php

namespace Database\Factories;

use App\Models\TokenPackage;
use App\Models\TokenPurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * トークン購入リクエストファクトリ
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenPurchaseRequest>
 */
class TokenPurchaseRequestFactory extends Factory
{
    /**
     * モデルの対応するファクトリ
     *
     * @var string
     */
    protected $model = TokenPurchaseRequest::class;

    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_id' => TokenPackage::factory(),
            'status' => 'pending',
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * 承認済みステート
     */
    public function approved(User $approver): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * 却下済みステート
     */
    public function rejected(User $approver, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason ?? '却下理由',
        ]);
    }

    /**
     * 承認待ちステート（デフォルト）
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }
}
