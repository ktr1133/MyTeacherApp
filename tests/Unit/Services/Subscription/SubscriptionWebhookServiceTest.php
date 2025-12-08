<?php

namespace Tests\Unit\Services\Subscription;

use App\Models\Group;
use App\Services\Subscription\SubscriptionWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * SubscriptionWebhookService Unit Test
 * 
 * 期間終了検知とGroupsテーブルリセット機能のテスト
 */
class SubscriptionWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionWebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionWebhookService::class);
    }

    /**
     * 期間終了したサブスクリプションをリセットできる
     */
    public function test_期間終了したサブスクリプションをリセットできる(): void
    {
        // Arrange
        $group = Group::factory()->create([
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 10,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $group->id,
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(), // 1日前に終了
        ]);

        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'status' => 'canceled',
                    'current_period_end' => now()->subDay()->timestamp,
                    'metadata' => [
                        'group_id' => $group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];

        // Act
        $this->service->handleSubscriptionUpdated($payload);

        // Assert
        $group->refresh();
        $this->assertFalse($group->subscription_active);
        $this->assertNull($group->subscription_plan);
        $this->assertEquals(6, $group->max_members);
        $this->assertEquals(1, $group->max_groups);
    }

    /**
     * 猶予期間中はリセットしない
     */
    public function test_猶予期間中はリセットしない(): void
    {
        // Arrange
        $group = Group::factory()->create([
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 10,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $group->id,
            'stripe_id' => 'sub_test456',
            'stripe_status' => 'canceled',
            'ends_at' => now()->addDay(), // 1日後に終了
        ]);

        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test456',
                    'status' => 'canceled',
                    'current_period_end' => now()->addDay()->timestamp, // 猶予期間中
                    'metadata' => [
                        'group_id' => $group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];

        // Act
        $this->service->handleSubscriptionUpdated($payload);

        // Assert
        $group->refresh();
        $this->assertFalse($group->subscription_active); // statusがcanceledなので無効化
        $this->assertEquals('family', $group->subscription_plan); // プランは維持
        $this->assertEquals(6, $group->max_members); // プランのmax_membersが設定される
    }

    /**
     * アクティブなサブスクリプションはリセットしない
     */
    public function test_アクティブなサブスクリプションはリセットしない(): void
    {
        // Arrange
        $group = Group::factory()->create([
            'subscription_active' => true,
            'subscription_plan' => 'enterprise',
            'max_members' => 20,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $group->id,
            'stripe_id' => 'sub_test789',
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);

        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test789',
                    'status' => 'active', // アクティブ
                    'current_period_end' => now()->addMonth()->timestamp,
                    'metadata' => [
                        'group_id' => $group->id,
                        'plan' => 'enterprise',
                    ],
                ],
            ],
        ];

        // Act
        $this->service->handleSubscriptionUpdated($payload);

        // Assert
        $group->refresh();
        $this->assertTrue($group->subscription_active); // 変更なし
        $this->assertEquals('enterprise', $group->subscription_plan); // 変更なし
    }

    /**
     * 既にリセット済みの場合はスキップ（冪等性）
     */
    public function test_既にリセット済みの場合はスキップ(): void
    {
        // Arrange
        $group = Group::factory()->create([
            'subscription_active' => false, // 既にリセット済み
            'subscription_plan' => null,
            'max_members' => 6,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $group->id,
            'stripe_id' => 'sub_test999',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);

        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test999',
                    'status' => 'canceled',
                    'current_period_end' => now()->subDay()->timestamp,
                    'metadata' => [
                        'group_id' => $group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];

        // Logモック: 期間終了検知ログと既にリセット済みログを期待
        Log::shouldReceive('info')
            ->once()
            ->with('Webhook: Subscription period ended detected', \Mockery::type('array'));
        
        Log::shouldReceive('info')
            ->once()
            ->with('Webhook: Group already reset', [
                'group_id' => $group->id,
                'trigger' => 'webhook',
            ]);

        // Act
        $this->service->handleSubscriptionUpdated($payload);

        // Assert（状態変化なし）
        $group->refresh();
        $this->assertFalse($group->subscription_active);
        $this->assertNull($group->subscription_plan);
    }

    /**
     * metadata.group_idがない場合はエラーログを出力
     */
    public function test_metadataがない場合はエラーログを出力(): void
    {
        // Arrange
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_no_metadata',
                    'status' => 'canceled',
                    'current_period_end' => now()->subDay()->timestamp,
                    // metadata なし
                ],
            ],
        ];

        Log::shouldReceive('error')
            ->once()
            ->with('Subscription updated: metadata missing', \Mockery::type('array'));

        // Act
        $this->service->handleSubscriptionUpdated($payload);

        // Assert（例外なく処理終了）
        $this->assertTrue(true);
    }
}
