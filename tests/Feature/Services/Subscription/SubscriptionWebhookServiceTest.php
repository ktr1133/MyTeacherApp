<?php

use App\Models\Group;
use App\Models\User;
use App\Services\Subscription\SubscriptionWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SubscriptionWebhookService::class);
    
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create([
        'master_user_id' => $this->user->id,
        'subscription_active' => false,
        'subscription_plan' => null,
        'max_members' => 6,
    ]);
    
    // グループにユーザーを関連付け
    $this->user->update(['group_id' => $this->group->id]);
});

describe('handleSubscriptionCreated', function () {
    it('サブスクリプション作成時にグループが正しく更新される - ファミリープラン', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionCreated($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeTrue()
            ->and($this->group->subscription_plan)->toBe('family')
            ->and($this->group->max_members)->toBe(6);
    });

    it('サブスクリプション作成時にグループが正しく更新される - エンタープライズプラン', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test456',
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'enterprise',
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionCreated($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeTrue()
            ->and($this->group->subscription_plan)->toBe('enterprise')
            ->and($this->group->max_members)->toBe(20);
    });

    it('メタデータが不足している場合はエラーログを出力し処理を中断する', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Subscription created: metadata missing', \Mockery::any());
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test789',
                    'metadata' => [], // group_id と plan が欠落
                ],
            ],
        ];
        
        $this->service->handleSubscriptionCreated($payload);
        
        $this->group->refresh();
        
        // グループは変更されていないことを確認
        expect($this->group->subscription_active)->toBeFalse()
            ->and($this->group->subscription_plan)->toBeNull();
    });

    it('存在しないグループIDの場合は例外をスローする', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test999',
                    'metadata' => [
                        'group_id' => 99999, // 存在しないID
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        expect(fn() => $this->service->handleSubscriptionCreated($payload))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});

describe('handleSubscriptionUpdated', function () {
    beforeEach(function () {
        // サブスクリプションが有効な状態でテスト開始
        $this->group->update([
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 6,
        ]);
    });

    it('サブスクリプション更新時にグループが正しく更新される - アクティブ状態', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'status' => 'active',
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'enterprise', // プラン変更
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionUpdated($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeTrue()
            ->and($this->group->subscription_plan)->toBe('enterprise')
            ->and($this->group->max_members)->toBe(20);
    });

    it('サブスクリプション更新時にグループが正しく更新される - トライアル中', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'status' => 'trialing',
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionUpdated($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeTrue()
            ->and($this->group->subscription_plan)->toBe('family');
    });

    it('サブスクリプション更新時にグループが正しく更新される - 非アクティブ状態', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'status' => 'canceled',
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionUpdated($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeFalse()
            ->and($this->group->subscription_plan)->toBe('family');
    });

    it('メタデータが不足している場合はエラーログを出力し処理を中断する', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Subscription updated: metadata missing', \Mockery::any());
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test789',
                    'status' => 'active',
                    'metadata' => [], // group_id が欠落
                ],
            ],
        ];
        
        $originalPlan = $this->group->subscription_plan;
        
        $this->service->handleSubscriptionUpdated($payload);
        
        $this->group->refresh();
        
        // グループは変更されていないことを確認
        expect($this->group->subscription_plan)->toBe($originalPlan);
    });
});

describe('handleSubscriptionDeleted', function () {
    beforeEach(function () {
        // サブスクリプションが有効な状態でテスト開始
        $this->group->update([
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 6,
        ]);
    });

    it('サブスクリプション削除時にグループが無効化される', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'metadata' => [
                        'group_id' => $this->group->id,
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionDeleted($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeFalse()
            ->and($this->group->subscription_plan)->toBeNull()
            ->and($this->group->max_members)->toBe(6); // 無料枠にリセット
    });

    it('エンタープライズプランからの削除も正しく処理される', function () {
        $this->group->update([
            'subscription_plan' => 'enterprise',
            'max_members' => 20,
        ]);
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test456',
                    'metadata' => [
                        'group_id' => $this->group->id,
                    ],
                ],
            ],
        ];
        
        $this->service->handleSubscriptionDeleted($payload);
        
        $this->group->refresh();
        
        expect($this->group->subscription_active)->toBeFalse()
            ->and($this->group->subscription_plan)->toBeNull()
            ->and($this->group->max_members)->toBe(6);
    });

    it('メタデータが不足している場合はエラーログを出力し処理を中断する', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Subscription deleted: metadata missing', \Mockery::any());
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test789',
                    'metadata' => [], // group_id が欠落
                ],
            ],
        ];
        
        $originalActive = $this->group->subscription_active;
        
        $this->service->handleSubscriptionDeleted($payload);
        
        $this->group->refresh();
        
        // グループは変更されていないことを確認
        expect($this->group->subscription_active)->toBe($originalActive);
    });

    it('存在しないグループIDの場合は例外をスローする', function () {
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test999',
                    'metadata' => [
                        'group_id' => 99999, // 存在しないID
                    ],
                ],
            ],
        ];
        
        expect(fn() => $this->service->handleSubscriptionDeleted($payload))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});
