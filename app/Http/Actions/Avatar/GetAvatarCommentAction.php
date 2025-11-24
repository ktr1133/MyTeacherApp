<?php

namespace App\Http\Actions\Avatar;

use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 教師アバターコメント取得アクション
 */
class GetAvatarCommentAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $teacherAvatarService,
    ) {}

    public function __invoke(Request $request, string $eventType): JsonResponse
    {
        $startTime = microtime(true);
        
        logger()->info('[GetAvatarCommentAction] START', [
            'event_type' => $eventType,
            'user_id' => $request->user()->id,
            'timestamp' => $startTime,
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_path' => $request->path(),
        ]);
        
        $data = $this->teacherAvatarService->getCommentForEvent($request->user(), $eventType);

        if (!$data) {
            logger()->warning('[GetAvatarCommentAction] No data returned', [
                'event_type' => $eventType,
                'user_id' => $request->user()->id,
            ]);
            
            return response()->json(['comment' => null], 404);
        }

        logger()->info('[GetAvatarCommentAction] SUCCESS', [
            'event_type' => $eventType,
            'data' => $data,
            'total_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
        ]);

        return response()->json($data);
    }
}