<?php

/**
 * Userモデル Unit Test
 * 
 * @package Tests\Unit\Models
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('親招待トークン有効期限チェック', function () {
    test('parent_invitation_expires_atがnullの場合、falseを返す', function () {
        $user = User::factory()->create([
            'parent_invitation_token' => null,
            'parent_invitation_expires_at' => null,
        ]);

        expect($user->isParentInvitationExpired())->toBeFalse();
    });

    test('トークンが有効期限内の場合、falseを返す', function () {
        $user = User::factory()->create([
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30), // 30日後（有効）
        ]);

        expect($user->isParentInvitationExpired())->toBeFalse();
    });

    test('トークンが有効期限切れの場合、trueを返す', function () {
        $user = User::factory()->create([
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->subDay(), // 1日前（期限切れ）
        ]);

        expect($user->isParentInvitationExpired())->toBeTrue();
    });

    test('トークンが期限ギリギリ（1秒前）の場合、falseを返す', function () {
        $user = User::factory()->create([
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addSecond(),
        ]);

        expect($user->isParentInvitationExpired())->toBeFalse();
    });

    test('トークンが期限ギリギリ（1秒後）の場合、trueを返す', function () {
        $user = User::factory()->create([
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->subSecond(),
        ]);

        expect($user->isParentInvitationExpired())->toBeTrue();
    });
});
