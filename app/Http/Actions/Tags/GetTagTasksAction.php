<?php

namespace App\Http\Actions\Tags;

use App\Http\Responders\Tags\TagTaskResponder;
use App\Models\Tag;
use App\Services\Tag\TagServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 指定されたタグに関連付けられたタスクを取得する処理
 */
class GetTagTasksAction
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

            $data = $this->tagService->getTagTasks($tag, $request->user()->id);

            return $this->responder->index($data);

        } catch (\Exception $e) {
            \Log::error('Failed to get tag tasks', [
                'tag_id' => $tag->id,
                'error'  => $e->getMessage(),
            ]);
            return $this->responder->serverError();
        }
    }
}