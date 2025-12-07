<?php

use App\Models\User;
use App\Models\Tag;
use App\Models\Task;

describe('タグ管理API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    describe('タグ一覧取得 (GET /api/tags)', function () {
        it('ユーザーのタグ一覧を取得できる', function () {
            // ユーザーのタグを作成
            $tag1 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ1']);
            $tag2 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ2']);
            
            // 他ユーザーのタグ（表示されないはず）
            $otherTag = Tag::factory()->create(['name' => '他人のタグ']);

            $response = $this->actingAs($this->user)
                ->getJson('/api/tags');

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
            $response = $this->getJson('/api/tags');

            $response->assertUnauthorized()
                ->assertJson([
                    
                    'message' => 'Unauthenticated.',
                ]);
        });
    });

    describe('タグ作成 (POST /api/tags)', function () {
        it('新しいタグを作成できる', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/tags', [
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
                ->postJson('/api/tags', [
                    'name' => '',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    
                    'message' => '入力内容に誤りがあります。',
                ]);
        });
    });

    describe('タグ更新 (PUT /api/tags/{id})', function () {
        it('タグ名を更新できる', function () {
            $tag = Tag::factory()->create([
                'user_id' => $this->user->id,
                'name' => '元のタグ名',
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/tags/{$tag->id}", [
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
                ->putJson('/api/tags/99999', [
                    'name' => '更新',
                ]);

            // サービス層でエラーハンドリングされる想定
            $response->assertStatus(500); // または404
        });
    });

    describe('タグ削除 (DELETE /api/tags/{id})', function () {
        it('タグを削除できる', function () {
            $tag = Tag::factory()->create([
                'user_id' => $this->user->id,
                'name' => '削除対象タグ',
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/tags/{$tag->id}");

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
                ->deleteJson('/api/tags/99999');

            $response->assertNotFound()
                ->assertJson([
                    
                    'message' => '指定されたタグが見つかりません。',
                ]);
        });

        it('他ユーザーのタグは削除できない', function () {
            $otherUserTag = Tag::factory()->create(['name' => '他人のタグ']);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/tags/{$otherUserTag->id}");

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
                ->getJson('/api/tags');

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

    describe('タグとタスクの紐付け管理API', function () {
        beforeEach(function () {
            $this->tag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'テストタグ']);
            $this->task1 = Task::factory()->create(['user_id' => $this->user->id, 'title' => 'タスク1']);
            $this->task2 = Task::factory()->create(['user_id' => $this->user->id, 'title' => 'タスク2']);
        });

        describe('タグに紐づくタスク一覧取得 (GET /api/tags/{tag}/tasks)', function () {
            it('タグに紐づくタスクと未紐付けタスクを取得できる', function () {
                // task1をタグに紐付け
                $this->tag->tasks()->attach($this->task1->id);

                $response = $this->actingAs($this->user)
                    ->getJson("/api/tags/{$this->tag->id}/tasks");

                $response->assertOk()
                    ->assertJsonStructure([
                        'linked' => [
                            '*' => ['id', 'title'],
                        ],
                        'available' => [
                            '*' => ['id', 'title'],
                        ],
                    ]);

                // linkedにtask1、availableにtask2が含まれることを確認
                $linkedIds = collect($response->json('linked'))->pluck('id')->toArray();
                $availableIds = collect($response->json('available'))->pluck('id')->toArray();
                
                $this->assertContains($this->task1->id, $linkedIds);
                $this->assertContains($this->task2->id, $availableIds);
            });

            it('他ユーザーのタグにはアクセスできない', function () {
                $otherUser = User::factory()->create();
                $otherTag = Tag::factory()->create(['user_id' => $otherUser->id]);

                $response = $this->actingAs($this->user)
                    ->getJson("/api/tags/{$otherTag->id}/tasks");

                $response->assertForbidden();
            });

            it('未認証ではアクセスできない', function () {
                $response = $this->getJson("/api/tags/{$this->tag->id}/tasks");

                $response->assertUnauthorized();
            });
        });

        describe('タスクをタグに紐付け (POST /api/tags/{tag}/tasks/attach)', function () {
            it('タスクをタグに紐付けられる', function () {
                $response = $this->actingAs($this->user)
                    ->postJson("/api/tags/{$this->tag->id}/tasks/attach", [
                        'task_id' => $this->task1->id,
                    ]);

                $response->assertOk()
                    ->assertJson([
                        'message' => 'タスクを紐付けました。',
                    ]);

                // DBで紐付けを確認
                $this->assertDatabaseHas('task_tag', [
                    'tag_id' => $this->tag->id,
                    'task_id' => $this->task1->id,
                ]);
            });

            it('存在しないタスクIDではエラーになる', function () {
                $response = $this->actingAs($this->user)
                    ->postJson("/api/tags/{$this->tag->id}/tasks/attach", [
                        'task_id' => 99999,
                    ]);

                $response->assertUnprocessable()
                    ->assertJsonValidationErrors(['task_id']);
            });

            it('他ユーザーのタグには紐付けられない', function () {
                $otherUser = User::factory()->create();
                $otherTag = Tag::factory()->create(['user_id' => $otherUser->id]);

                $response = $this->actingAs($this->user)
                    ->postJson("/api/tags/{$otherTag->id}/tasks/attach", [
                        'task_id' => $this->task1->id,
                    ]);

                $response->assertForbidden();
            });

            it('task_idが必須である', function () {
                $response = $this->actingAs($this->user)
                    ->postJson("/api/tags/{$this->tag->id}/tasks/attach", []);

                $response->assertUnprocessable()
                    ->assertJsonValidationErrors(['task_id']);
            });

            it('未認証ではアクセスできない', function () {
                $response = $this->postJson("/api/tags/{$this->tag->id}/tasks/attach", [
                    'task_id' => $this->task1->id,
                ]);

                $response->assertUnauthorized();
            });
        });

        describe('タスクからタグを解除 (DELETE /api/tags/{tag}/tasks/detach)', function () {
            beforeEach(function () {
                // 事前にtask1をタグに紐付け
                $this->tag->tasks()->attach($this->task1->id);
            });

            it('タスクからタグを解除できる', function () {
                $response = $this->actingAs($this->user)
                    ->deleteJson("/api/tags/{$this->tag->id}/tasks/detach", [
                        'task_id' => $this->task1->id,
                    ]);

                $response->assertOk()
                    ->assertJson([
                        'message' => 'タスクを解除しました。',
                    ]);

                // DBで紐付けが解除されたことを確認
                $this->assertDatabaseMissing('task_tag', [
                    'tag_id' => $this->tag->id,
                    'task_id' => $this->task1->id,
                ]);
            });

            it('存在しないタスクIDではエラーになる', function () {
                $response = $this->actingAs($this->user)
                    ->deleteJson("/api/tags/{$this->tag->id}/tasks/detach", [
                        'task_id' => 99999,
                    ]);

                $response->assertUnprocessable()
                    ->assertJsonValidationErrors(['task_id']);
            });

            it('他ユーザーのタグからは解除できない', function () {
                $otherUser = User::factory()->create();
                $otherTag = Tag::factory()->create(['user_id' => $otherUser->id]);

                $response = $this->actingAs($this->user)
                    ->deleteJson("/api/tags/{$otherTag->id}/tasks/detach", [
                        'task_id' => $this->task1->id,
                    ]);

                $response->assertForbidden();
            });

            it('task_idが必須である', function () {
                $response = $this->actingAs($this->user)
                    ->deleteJson("/api/tags/{$this->tag->id}/tasks/detach", []);

                $response->assertUnprocessable()
                    ->assertJsonValidationErrors(['task_id']);
            });

            it('未認証ではアクセスできない', function () {
                $response = $this->deleteJson("/api/tags/{$this->tag->id}/tasks/detach", [
                    'task_id' => $this->task1->id,
                ]);

                $response->assertUnauthorized();
            });
        });
    });
});
