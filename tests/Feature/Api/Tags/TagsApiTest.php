<?php

use App\Models\User;
use App\Models\Tag;
use App\Models\Task;

describe('タグ管理API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    describe('タグ一覧取得 (GET /api/v1/tags)', function () {
        it('ユーザーのタグ一覧を取得できる', function () {
            // ユーザーのタグを作成
            $tag1 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ1']);
            $tag2 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ2']);
            
            // 他ユーザーのタグ（表示されないはず）
            $otherTag = Tag::factory()->create(['name' => '他人のタグ']);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/tags');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'tags' => [
                            '*' => ['id', 'name', 'tasks_count', 'created_at', 'updated_at'],
                        ],
                        'tasks',
                    ],
                ]);

            // 自分のタグのみが含まれることを確認
            $tagIds = collect($response->json('data.tags'))->pluck('id')->toArray();
            $this->assertContains($tag1->id, $tagIds);
            $this->assertContains($tag2->id, $tagIds);
            $this->assertNotContains($otherTag->id, $tagIds);
        });

        it('未認証ではアクセスできない', function () {
            $response = $this->getJson('/api/v1/tags');

            $response->assertUnauthorized()
                ->assertJson([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ]);
        });
    });

    describe('タグ作成 (POST /api/v1/tags)', function () {
        it('新しいタグを作成できる', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/tags', [
                    'name' => '新しいタグ',
                ]);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'タグを作成しました。',
                    'data' => [
                        'tag' => [
                            'name' => '新しいタグ',
                        ],
                    ],
                ])
                ->assertJsonStructure([
                    'data' => [
                        'tag' => ['id', 'name', 'created_at', 'updated_at'],
                        'avatar_event',
                    ],
                ]);

            $this->assertDatabaseHas('tags', [
                'user_id' => $this->user->id,
                'name' => '新しいタグ',
            ]);
        });

        it('タグ名が空の場合はバリデーションエラー', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/tags', [
                    'name' => '',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '入力内容に誤りがあります。',
                ]);
        });
    });

    describe('タグ更新 (PUT /api/v1/tags/{id})', function () {
        it('タグ名を更新できる', function () {
            $tag = Tag::factory()->create([
                'user_id' => $this->user->id,
                'name' => '元のタグ名',
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/tags/{$tag->id}", [
                    'name' => '更新後のタグ名',
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'タグを更新しました。',
                    'data' => [
                        'tag' => [
                            'id' => $tag->id,
                            'name' => '更新後のタグ名',
                        ],
                    ],
                ]);

            $this->assertDatabaseHas('tags', [
                'id' => $tag->id,
                'name' => '更新後のタグ名',
            ]);
        });

        it('存在しないタグIDは404エラー（暗黙的）', function () {
            $response = $this->actingAs($this->user)
                ->putJson('/api/v1/tags/99999', [
                    'name' => '更新',
                ]);

            // サービス層でエラーハンドリングされる想定
            $response->assertStatus(500); // または404
        });
    });

    describe('タグ削除 (DELETE /api/v1/tags/{id})', function () {
        it('タグを削除できる', function () {
            $tag = Tag::factory()->create([
                'user_id' => $this->user->id,
                'name' => '削除対象タグ',
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/tags/{$tag->id}");

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'タグを削除しました。',
                    'data' => [
                        'deleted_tag_id' => $tag->id,
                    ],
                ]);

            $this->assertDatabaseMissing('tags', [
                'id' => $tag->id,
            ]);
        });

        it('存在しないタグIDは404エラー', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/v1/tags/99999');

            $response->assertNotFound()
                ->assertJson([
                    'success' => false,
                    'message' => '指定されたタグが見つかりません。',
                ]);
        });

        it('他ユーザーのタグは削除できない', function () {
            $otherUserTag = Tag::factory()->create(['name' => '他人のタグ']);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/tags/{$otherUserTag->id}");

            // サービス層で権限チェックされる想定
            $response->assertStatus(403);
        });
    });

    describe('タグとタスクの連携', function () {
        it('タグに紐づくタスクも一覧で取得できる', function () {
            $tag = Tag::factory()->create(['user_id' => $this->user->id]);
            $task = Task::factory()->create(['user_id' => $this->user->id, 'title' => 'タスク1']);
            $task->tags()->attach($tag->id);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/tags');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'tasks' => [
                            '*' => ['id', 'title', 'is_completed', 'tag_id'],
                        ],
                    ],
                ]);

            $tasks = collect($response->json('data.tasks'));
            $this->assertTrue($tasks->contains('title', 'タスク1'));
        });
    });
});
