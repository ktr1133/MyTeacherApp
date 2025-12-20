<?php

namespace Tests\Feature\Profile\Group;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // ログを無効化（テスト高速化）
    Log::shouldReceive('info')->andReturnTrue();
    Log::shouldReceive('error')->andReturnTrue();
    Log::shouldReceive('withContext')->andReturnSelf(); // ミドルウェア用
});

describe('LinkChildren - メンバー数上限チェック', function () {
    
    it('無料プラン（subscription_active=false）の場合、6名を超える紐づけはスキップされる', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => false,
            'subscription_plan' => null,
            'max_members' => 6,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー4名を追加（親含めて5名）
        User::factory()->count(4)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント3名を作成
        $children = User::factory()->count(3)->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // 紐づけリクエスト（現在5名 + 3名 = 8名 → 1名のみ成功、2名はスキップ）
        $response = $this->actingAs($parent)->postJson('/profile/group/link-children', [
            'child_user_ids' => $children->pluck('id')->toArray(),
        ]);

        $response->assertStatus(206); // Partial Content
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(1); // 1名のみ紐づけ成功（合計6名に到達）
        expect($json['data']['summary']['skipped'])->toBe(2); // 2名はスキップ
        
        // スキップ理由の確認
        expect($json['data']['skipped_children'][0]['reason'])
            ->toContain('グループメンバーの上限（6名）に達しています');
        expect($json['data']['skipped_children'][0]['reason'])
            ->toContain('エンタープライズプランにアップグレード'); // 無料プランはアップグレード案内あり
    });

    it('Familyプラン（max_members=6）の場合、6名上限が適用される', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 6,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー5名を追加（親含めて6名 = 上限）
        User::factory()->count(5)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント2名を作成
        $children = User::factory()->count(2)->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // 紐づけリクエスト（現在6名 = 上限 → 全員スキップ）
        $response = $this->actingAs($parent)->postJson('/profile/group/link-children', [
            'child_user_ids' => $children->pluck('id')->toArray(),
        ]);

        $response->assertStatus(400); // Bad Request (全員スキップ)
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(0);
        expect($json['data']['summary']['skipped'])->toBe(2);
        
        // Familyプランなのでアップグレード案内なし
        expect($json['data']['skipped_children'][0]['reason'])
            ->toBe('グループメンバーの上限（6名）に達しています。');
    });

    it('Enterpriseプラン（max_members=20）の場合、20名上限が適用される', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => true,
            'subscription_plan' => 'enterprise',
            'max_members' => 20,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー19名を追加（親含めて20名 = 上限）
        User::factory()->count(19)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント2名を作成
        $children = User::factory()->count(2)->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // 紐づけリクエスト（現在20名 = 上限 → 全員スキップ）
        $response = $this->actingAs($parent)->postJson('/profile/group/link-children', [
            'child_user_ids' => $children->pluck('id')->toArray(),
        ]);

        $response->assertStatus(400); // Bad Request (全員スキップ)
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(0);
        expect($json['data']['summary']['skipped'])->toBe(2);
        
        // Enterpriseプランなのでアップグレード案内なし
        expect($json['data']['skipped_children'][0]['reason'])
            ->toBe('グループメンバーの上限（20名）に達しています。');
    });

    it('Enterpriseプラン（max_members=20）で19名の場合、1名のみ紐づけ成功する', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => true,
            'subscription_plan' => 'enterprise',
            'max_members' => 20,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー18名を追加（親含めて19名）
        User::factory()->count(18)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント3名を作成
        $children = User::factory()->count(3)->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // 紐づけリクエスト（現在19名 + 3名 → 1名のみ成功、2名スキップ）
        $response = $this->actingAs($parent)->postJson('/profile/group/link-children', [
            'child_user_ids' => $children->pluck('id')->toArray(),
        ]);

        $response->assertStatus(206); // Partial Content
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(1); // 1名のみ成功（合計20名）
        expect($json['data']['summary']['skipped'])->toBe(2); // 2名スキップ

        // データベース確認
        $linkedCount = User::where('group_id', $group->id)->count();
        expect($linkedCount)->toBe(20); // 親1 + 既存18 + 新規1 = 20
    });

    it('無料プランで5名のグループに1名紐づけ（上限ピッタリ）', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => false,
            'subscription_plan' => null,
            'max_members' => 6,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー4名を追加（親含めて5名）
        User::factory()->count(4)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント1名を作成
        $child = User::factory()->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // 紐づけリクエスト（現在5名 + 1名 = 6名 → 成功）
        $response = $this->actingAs($parent)->postJson('/profile/group/link-children', [
            'child_user_ids' => [$child->id],
        ]);

        $response->assertStatus(200); // 成功
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(1);
        expect($json['data']['summary']['skipped'])->toBe(0);

        // データベース確認
        $linkedCount = User::where('group_id', $group->id)->count();
        expect($linkedCount)->toBe(6);
    });
});

describe('LinkChildren API (Mobile) - メンバー数上限チェック', function () {
    
    it('無料プランの場合、6名を超える紐づけはスキップされる（API版）', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => false,
            'subscription_plan' => null,
            'max_members' => 6,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー5名を追加（親含めて6名 = 上限）
        User::factory()->count(5)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント2名を作成
        $children = User::factory()->count(2)->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // API紐づけリクエスト（現在6名 = 上限 → 全員スキップ）
        $response = $this->actingAs($parent)->postJson('/api/profile/group/link-children', [
            'child_user_ids' => $children->pluck('id')->toArray(),
        ]);

        $response->assertStatus(400); // Bad Request (全員スキップ)
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(0);
        expect($json['data']['summary']['skipped'])->toBe(2);
        
        // スキップ理由にアップグレード案内が含まれる
        expect($json['data']['skipped_children'][0]['reason'])
            ->toContain('グループメンバーの上限（6名）に達しています');
    });

    it('Enterpriseプラン（max_members=20）で部分成功する（API版）', function () {
        // 親アカウント（グループマスター）を作成
        $parent = User::factory()->create(['theme' => 'adult']);
        $group = Group::factory()->create([
            'master_user_id' => $parent->id,
            'subscription_active' => true,
            'subscription_plan' => 'enterprise',
            'max_members' => 20,
        ]);
        $parent->update(['group_id' => $group->id]);

        // 既存グループメンバー18名を追加（親含めて19名）
        User::factory()->count(18)->create([
            'group_id' => $group->id,
            'theme' => 'child',
        ]);

        // 紐づけしようとする子アカウント3名を作成
        $children = User::factory()->count(3)->create([
            'group_id' => null,
            'theme' => 'child',
        ]);

        // API紐づけリクエスト（現在19名 + 3名 → 1名のみ成功）
        $response = $this->actingAs($parent)->postJson('/api/profile/group/link-children', [
            'child_user_ids' => $children->pluck('id')->toArray(),
        ]);

        $response->assertStatus(206); // Partial Content
        
        $json = $response->json();
        expect($json['data']['summary']['linked'])->toBe(1);
        expect($json['data']['summary']['skipped'])->toBe(2);

        // スキップ理由にアップグレード案内なし（Enterprise）
        expect($json['data']['skipped_children'][0]['reason'])
            ->toBe('グループメンバーの上限（20名）に達しています。');
    });
});
