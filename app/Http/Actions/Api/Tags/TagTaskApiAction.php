<?php

namespace App\Http\Actions\Api\Tags;

use App\Responders\Tags\TagTaskResponder;
use App\Models\Tag;
use App\Services\Tag\TagServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * モバイルAPI用: タグとタスクの紐付け管理を行う統合アクションクラス
 * 
 * このクラスは以下の3つの操作を処理します:
 * - タグに紐づくタスク一覧の取得
 * - タスクのタグへの紐付け
 * - タスクのタグからの解除
 * 
 * 認証: Laravel Sanctum (Bearer Token)
 * 
 * 責務:
 * - HTTPリクエストの受付とバリデーション
 * - 認可チェック
 * - サービス層への処理委譲
 * - レスポンダーを通じたレスポンス返却
 */
class TagTaskApiAction
{
    /**
     * コンストラクタ
     * 
     * @param TagServiceInterface $tagService タグビジネスロジック
     * @param TagTaskResponder $responder レスポンス生成
     */
    public function __construct(
        private TagServiceInterface $tagService,
        private TagTaskResponder $responder
    ) {}

    /**
     * タグに紐づくタスク一覧と未紐付けタスク一覧を取得
     * 
     * GET /api/tags/{tag}/tasks
     * 
     * @param Tag $tag ルートモデルバインディングで取得されたタグ
     * @param Request $request HTTPリクエスト
     * @return JsonResponse JSON形式のレスポンス
     * 
     * レスポンス例:
     * {
     *   "linked": [
     *     {"id": 1, "title": "タスクA", "description": "説明", "status": 0},
     *     {"id": 2, "title": "タスクB", "description": "説明", "status": 1}
     *   ],
     *   "available": [
     *     {"id": 3, "title": "タスクC", "description": "説明", "status": 0},
     *     {"id": 4, "title": "タスクD", "description": "説明", "status": 0}
     *   ]
     * }
     */
    public function index(Tag $tag, Request $request): JsonResponse
    {
        try {
            // Sanctum認証: $request->user()で認証済みユーザー取得
            $user = $request->user();
            
            if (!$user) {
                return $this->responder->forbidden();
            }

            // 権限チェック: タグの所有者のみアクセス可能
            if (!$this->tagService->isOwner($tag, $user->id)) {
                return $this->responder->forbidden();
            }

            // サービス層でタスク一覧を取得・整形
            $data = $this->tagService->getTagTasks($tag, $user->id);

            return $this->responder->index($data);

        } catch (\Exception $e) {
            // エラーログに記録
            Log::error('Failed to get tag tasks (API)', [
                'tag_id' => $tag->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->serverError('タスク一覧の取得に失敗しました。');
        }
    }

    /**
     * タスクをタグに紐付ける
     * 
     * POST /api/tags/{tag}/tasks/attach
     * 
     * リクエストボディ:
     * {
     *   "task_id": 5
     * }
     * 
     * @param Tag $tag ルートモデルバインディングで取得されたタグ
     * @param Request $request HTTPリクエスト
     * @return JsonResponse JSON形式のレスポンス
     * 
     * 成功時:
     * {
     *   "message": "タスクを紐付けました。"
     * }
     * 
     * エラー時:
     * {
     *   "message": "入力内容に誤りがあります。",
     *   "errors": {
     *     "task_id": ["指定されたタスクが見つかりません。"]
     *   }
     * }
     */
    public function attach(Tag $tag, Request $request): JsonResponse
    {
        try {
            // Sanctum認証: $request->user()で認証済みユーザー取得
            $user = $request->user();
            
            if (!$user) {
                return $this->responder->forbidden();
            }

            // 権限チェック: タグの所有者のみ操作可能
            if (!$this->tagService->isOwner($tag, $user->id)) {
                return $this->responder->forbidden();
            }

            // バリデーション
            $validator = Validator::make($request->all(), [
                'task_id' => [
                    'required',
                    'integer',
                    'exists:tasks,id',
                ],
            ], [
                'task_id.required' => 'タスクIDは必須です。',
                'task_id.integer' => 'タスクIDは整数である必要があります。',
                'task_id.exists' => '指定されたタスクが見つかりません。',
            ]);

            if ($validator->fails()) {
                return $this->responder->validationError($validator->errors()->toArray());
            }

            // サービス層で紐付け処理を実行
            $this->tagService->attachTaskToTag(
                $tag,
                $request->input('task_id'),
                $user->id
            );

            return $this->responder->attached();

        } catch (ModelNotFoundException $e) {
            // タスクが見つからない、または所有者でない場合
            return $this->responder->validationError([
                'task_id' => ['指定されたタスクが見つからないか、アクセス権限がありません。'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to attach task to tag (API)', [
                'tag_id' => $tag->id,
                'task_id' => $request->input('task_id'),
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->serverError('タスクの紐付けに失敗しました。');
        }
    }

    /**
     * タスクからタグを解除する
     * 
     * DELETE /api/tags/{tag}/tasks/detach
     * 
     * リクエストボディ:
     * {
     *   "task_id": 5
     * }
     * 
     * @param Tag $tag ルートモデルバインディングで取得されたタグ
     * @param Request $request HTTPリクエスト
     * @return JsonResponse JSON形式のレスポンス
     * 
     * 成功時:
     * {
     *   "message": "タスクを解除しました。"
     * }
     * 
     * エラー時:
     * {
     *   "message": "入力内容に誤りがあります。",
     *   "errors": {
     *     "task_id": ["指定されたタスクが見つかりません。"]
     *   }
     * }
     */
    public function detach(Tag $tag, Request $request): JsonResponse
    {
        try {
            // Sanctum認証: $request->user()で認証済みユーザー取得
            $user = $request->user();
            
            if (!$user) {
                return $this->responder->forbidden();
            }

            // 権限チェック: タグの所有者のみ操作可能
            if (!$this->tagService->isOwner($tag, $user->id)) {
                return $this->responder->forbidden();
            }

            // バリデーション
            $validator = Validator::make($request->all(), [
                'task_id' => [
                    'required',
                    'integer',
                    'exists:tasks,id',
                ],
            ], [
                'task_id.required' => 'タスクIDは必須です。',
                'task_id.integer' => 'タスクIDは整数である必要があります。',
                'task_id.exists' => '指定されたタスクが見つかりません。',
            ]);

            if ($validator->fails()) {
                return $this->responder->validationError($validator->errors()->toArray());
            }

            // サービス層で解除処理を実行
            $this->tagService->detachTaskFromTag(
                $tag,
                $request->input('task_id'),
                $user->id
            );

            return $this->responder->detached();

        } catch (ModelNotFoundException $e) {
            // タスクが見つからない、または所有者でない場合
            return $this->responder->validationError([
                'task_id' => ['指定されたタスクが見つからないか、アクセス権限がありません。'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to detach task from tag (API)', [
                'tag_id' => $tag->id,
                'task_id' => $request->input('task_id'),
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->serverError('タスクの解除に失敗しました。');
        }
    }
}
