<?php

use App\Models\User;
use App\Models\Task;
use App\Models\Tag;

describe('タスク更新API (PUT /api/tasks/{task})', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->task = Task::factory()->create(['user_id' => $this->user->id]);
    });

    it('タスクの基本情報を更新できる', function () {
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$this->task->id}", [
                'title' => '更新後タイトル',
                'description' => '更新後説明',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'タスクを更新しました。',
            ]);

        // DBで更新を確認
        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'title' => '更新後タイトル',
            'description' => '更新後説明',
        ]);
    });

    it('タグを紐付けられる', function () {
        $tag1 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ1']);
        $tag2 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ2']);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$this->task->id}", [
                'tag_ids' => [$tag1->id, $tag2->id],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'task' => [
                        'id' => $this->task->id,
                        'tags' => [
                            ['id' => $tag1->id, 'name' => 'タグ1'],
                            ['id' => $tag2->id, 'name' => 'タグ2'],
                        ],
                    ],
                ],
            ]);

        // DBで紐付けを確認
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $this->task->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $this->task->id,
            'tag_id' => $tag2->id,
        ]);
    });

    it('既存のタグを更新できる', function () {
        $tag1 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ1']);
        $tag2 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ2']);
        $tag3 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ3']);

        // 初期状態: tag1, tag2を紐付け
        $this->task->tags()->attach([$tag1->id, $tag2->id]);

        // tag2を削除し、tag3を追加
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$this->task->id}", [
                'tag_ids' => [$tag1->id, $tag3->id],
            ]);

        $response->assertOk();

        // tag1, tag3が紐付いていることを確認
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $this->task->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $this->task->id,
            'tag_id' => $tag3->id,
        ]);

        // tag2が削除されたことを確認
        $this->assertDatabaseMissing('task_tag', [
            'task_id' => $this->task->id,
            'tag_id' => $tag2->id,
        ]);
    });

    it('タグを全て解除できる', function () {
        $tag1 = Tag::factory()->create(['user_id' => $this->user->id]);
        $this->task->tags()->attach($tag1->id);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$this->task->id}", [
                'tag_ids' => [],
            ]);

        $response->assertOk();

        // タグが全て解除されたことを確認
        $this->assertDatabaseMissing('task_tag', [
            'task_id' => $this->task->id,
        ]);
    });

    it('存在しないタグIDではエラーになる', function () {
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$this->task->id}", [
                'tag_ids' => [99999],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_ids.0']);
    });

    it('他ユーザーのタスクは更新できない', function () {
        $otherUser = User::factory()->create();
        $otherTask = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$otherTask->id}", [
                'title' => '更新後タイトル',
            ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'このタスクを更新する権限がありません。',
            ]);
    });

    it('未認証ではアクセスできない', function () {
        $response = $this->putJson("/api/tasks/{$this->task->id}", [
            'title' => '更新後タイトル',
        ]);

        $response->assertUnauthorized();
    });

    it('tag_idsが配列でない場合はエラーになる', function () {
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$this->task->id}", [
                'tag_ids' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_ids']);
    });
});
