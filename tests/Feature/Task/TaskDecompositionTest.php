<?php

use App\Models\User;
use App\Models\Task;
use App\Models\TaskProposal;
use App\Models\TokenBalance;
use Illuminate\Support\Facades\Http;

/**
 * タスク分解機能テスト (Phase 2)
 * 
 * ⚠️ 重要な注意事項:
 * - OpenAI Mock: Http::fake() でOpenAI API呼び出しをモック
 * - トークン消費: レスポンスに usage フィールドを含める
 * - キャッシュ: テスト環境は CACHE_STORE=array を使用（Redis 回避）
 * - span指定: 採用テストでは必ず明示的に span 値を指定
 */

describe('ProposeTaskAction - タスク提案生成', function () {
    /**
     * 正常系: タスク提案を生成できる
     */
    test('必須項目のみでタスク提案を生成できる', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        // OpenAI APIレスポンスをモック
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => "- サブタスク1\n- サブタスク2\n- サブタスク3"]],
                ],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 50,
                    'total_tokens' => 150,
                ],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => 'テストタスク',
            'span' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'proposal_id',
            'original_task',
            'proposed_tasks',
            'model_used',
            'tokens_used' => ['prompt', 'completion', 'total'],
        ]);

        // DBに提案が保存されたことを確認
        $this->assertDatabaseHas('task_proposals', [
            'user_id' => $user->id,
            'original_task_text' => 'テストタスク',
            'was_adopted' => false,
        ]);

        // トークンが消費されたことを確認（推定1000トークン）
        $tokenBalance = TokenBalance::where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->first();
        expect($tokenBalance->balance)->toBeLessThan(10000);
    });

    test('全項目を指定してタスク提案を生成できる', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => "- 詳細なサブタスク1\n- 詳細なサブタスク2"]],
                ],
                'usage' => [
                    'prompt_tokens' => 150,
                    'completion_tokens' => 80,
                    'total_tokens' => 230,
                ],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => '詳細なテストタスク',
            'span' => 2,
            'due_date' => '2025-12-31',
            'context' => '追加のコンテキスト情報',
            'is_refinement' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // コンテキストが保存されたことを確認
        $this->assertDatabaseHas('task_proposals', [
            'user_id' => $user->id,
            'original_task_text' => '詳細なテストタスク',
            'proposal_context' => '追加のコンテキスト情報',
        ]);
    });

    test('is_refinementフラグがtrueの場合、細分化プロンプトが使用される', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => "- より細かいサブタスク1\n- より細かいサブタスク2\n- より細かいサブタスク3\n- より細かいサブタスク4"]],
                ],
                'usage' => [
                    'prompt_tokens' => 120,
                    'completion_tokens' => 100,
                    'total_tokens' => 220,
                ],
                'model' => 'gpt-4o-mini',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => '細分化対象タスク',
            'span' => 1,
            'is_refinement' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // リクエスト内容を検証（is_refinement=trueで細分化プロンプトが使用される）
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            $systemContent = $body['messages'][0]['content'] ?? '';
            return str_contains($systemContent, '細分化');
        });
    });

    /**
     * 異常系: バリデーションエラー
     */
    test('タイトルが未指定の場合、422エラーを返す', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'span' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    });

    test('spanが未指定の場合、422エラーを返す', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => 'テストタスク',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['span']);
    });

    test('spanが範囲外の場合、422エラーを返す', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => 'テストタスク',
            'span' => 4, // 1-3の範囲外
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['span']);
    });

    test('タイトルが255文字を超える場合、422エラーを返す', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => str_repeat('あ', 256),
            'span' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    });

    /**
     * 異常系: トークン残高不足
     */
    test('トークン残高不足の場合、402エラーを返す', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 100, // 推定1000トークン不足
            'free_balance' => 100,
            'paid_balance' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => 'テストタスク',
            'span' => 1,
        ]);

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'error' => 'トークン残高不足',
        ]);
        $response->assertJsonPath('action_url', route('tokens.purchase'));
    });

    /**
     * 異常系: OpenAI APIエラー
     */
    test('OpenAI APIエラーの場合、500エラーを返す', function () {
        $user = User::factory()->create();
        TokenBalance::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'balance' => 10000,
            'free_balance' => 10000,
            'paid_balance' => 0,
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'API rate limit exceeded'],
            ], 429),
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.propose'), [
            'title' => 'テストタスク',
            'span' => 1,
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => 'AI提案の生成に失敗しました',
        ]);

        // トークンは消費されていないことを確認
        $tokenBalance = TokenBalance::where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->first();
        expect($tokenBalance->balance)->toBe(10000);
    });

    /**
     * 異常系: 認証エラー
     */
    test('未認証の場合、302リダイレクトを返す', function () {
        $response = $this->postJson(route('tasks.propose'), [
            'title' => 'テストタスク',
            'span' => 1,
        ]);

        $response->assertStatus(401); // JSON APIの場合は401
    });
});

describe('AdoptProposalAction - 提案採用', function () {
    /**
     * 正常系: 提案を採用してタスクを作成
     */
    test('提案を採用して複数タスクを作成できる', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()
            ->forUser($user)
            ->withProposedTasks([
                ['title' => '採用タスク1'],
                ['title' => '採用タスク2'],
                ['title' => '採用タスク3'],
            ])
            ->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                ['title' => '採用タスク1', 'span' => 1, 'priority' => 2],
                ['title' => '採用タスク2', 'span' => 3, 'priority' => 3], // 中期（config/const.phpに合わせて修正）
                ['title' => '採用タスク3', 'span' => 1, 'priority' => 1],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('message', '3件のタスクを作成しました');

        // タスクが作成されたことを確認
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => '採用タスク1',
            'span' => 1,
        ]);
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => '採用タスク2',
            'span' => 3, // 中期（config/const.phpに合わせて修正）
        ]);
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => '採用タスク3',
            'span' => 1,
        ]);

        // 提案が採用済みにマークされたことを確認
        $proposal->refresh();
        expect($proposal->was_adopted)->toBeTrue();
    });

    test('タグ付きで提案を採用できる', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                [
                    'title' => 'タグ付きタスク',
                    'span' => 1,
                    'tags' => ['重要', '緊急'],
                ],
            ],
        ]);

        $response->assertStatus(200);

        // タスクとタグが作成されたことを確認
        $task = Task::where('title', 'タグ付きタスク')->first();
        expect($task)->not->toBeNull();
        expect($task->tags()->pluck('name')->toArray())->toContain('重要', '緊急');
    });

    test('due_dateを指定して提案を採用できる（短期タスク）', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                [
                    'title' => '短期タスク',
                    'span' => 1, // 短期
                    'due_date' => '2025-12-31',
                ],
            ],
        ]);

        $response->assertStatus(200);

        // 短期タスクのdue_dateはCarbonオブジェクトとして返される
        $task = Task::where('title', '短期タスク')->first();
        expect($task)->not->toBeNull();
        expect($task->due_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($task->due_date->format('Y-m-d'))->toBe('2025-12-31');
    });

    test('due_dateに任意文字列を指定して提案を採用できる（長期タスク）', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                [
                    'title' => '長期タスク',
                    'span' => 6, // 長期（任意文字列のdue_dateを使用）
                    'due_date' => '2年後',
                ],
            ],
        ]);

        $response->assertStatus(200);

        // 長期タスクのdue_dateは文字列のまま保持される
        $task = Task::where('title', '長期タスク')->first();
        expect($task)->not->toBeNull();
        expect($task->due_date)->toBe('2年後');
        expect($task->hasParsableDueDate())->toBeFalse();
    });

    /**
     * 異常系: バリデーションエラー
     */
    test('proposal_idが未指定の場合、422エラーを返す', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'tasks' => [
                ['title' => 'テストタスク', 'span' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'バリデーションエラー');
        $response->assertJsonStructure([
            'success',
            'error',
            'messages' => ['proposal_id'],
        ]);
    });

    test('proposal_idが存在しない場合、422エラーを返す', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => 99999, // 存在しないID
            'tasks' => [
                ['title' => 'テストタスク', 'span' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'バリデーションエラー');
        $response->assertJsonStructure([
            'success',
            'error',
            'messages' => ['proposal_id'],
        ]);
    });

    test('tasksが空配列の場合、422エラーを返す', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [], // 空配列
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'バリデーションエラー');
        $response->assertJsonStructure([
            'success',
            'error',
            'messages' => ['tasks'],
        ]);
    });

    test('tasksの各要素にtitleが未指定の場合、422エラーを返す', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                ['span' => 1], // titleなし
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'バリデーションエラー');
        $response->assertJsonStructure([
            'success',
            'error',
            'messages' => ['tasks.0.title'],
        ]);
    });

    test('tasksの各要素のspanが範囲外の場合、422エラーを返す', function () {
        $user = User::factory()->create();
        $proposal = TaskProposal::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                ['title' => 'テストタスク', 'span' => 4], // 1-3の範囲外
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'バリデーションエラー');
        $response->assertJsonStructure([
            'success',
            'error',
            'messages' => ['tasks.0.span'],
        ]);
    });

    /**
     * 異常系: 認証エラー
     */
    test('未認証の場合、401エラーを返す', function () {
        $proposal = TaskProposal::factory()->create();

        $response = $this->postJson(route('tasks.adopt'), [
            'proposal_id' => $proposal->id,
            'tasks' => [
                ['title' => 'テストタスク', 'span' => 1],
            ],
        ]);

        $response->assertStatus(401);
    });
});
