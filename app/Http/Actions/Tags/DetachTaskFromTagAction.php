<?php

namespace App\Http\Actions\Tags;

use App\Http\Responders\Tags\TagTaskResponder;
use App\Models\Tag;
use App\Services\Tag\TagServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetachTaskFromTagAction
{
    public function __construct(
        private TagServiceInterface $tagService,
        private TagTaskResponder $responder
    ) {}

    public function __invoke(Tag $tag, Request $request): JsonResponse
    {
        try {
            // 権限チェック
            if (!$this->tagService->isOwner($tag, $request->user()->id)) {
                return $this->responder->forbidden();
            }

            // バリデーション
            $validator = Validator::make($request->all(), [
                'task_id' => ['required', 'integer', 'exists:tasks,id'],
            ]);

            if ($validator->fails()) {
                return $this->responder->validationError($validator->errors()->toArray());
            }

            $this->tagService->detachTaskFromTag(
                $tag,
                $request->input('task_id'),
                $request->user()->id
            );

            return $this->responder->detached();

        } catch (ModelNotFoundException $e) {
            return $this->responder->validationError([
                'task_id' => ['指定されたタスクが見つかりません。'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to detach task from tag', [
                'tag_id'  => $tag->id,
                'task_id' => $request->input('task_id'),
                'error'   => $e->getMessage(),
            ]);
            return $this->responder->serverError();
        }
    }
}