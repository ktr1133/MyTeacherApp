<?php

namespace App\Http\Actions\Api\Notification;

use App\Http\Responders\Api\Notification\NotificationApiResponder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 通知検索アクション
 * 
 * GET /api/v1/notifications/search?keywords={keywords}&operator={operator}
 * 
 * @package App\Http\Actions\Api\Notification
 */
class SearchNotificationsApiAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationApiResponder $responder
     */
    public function __construct(
        protected NotificationApiResponder $responder
    ) {}

    /**
     * 通知検索処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 検索パラメータ取得
            $keywords = $request->input('keywords', '');
            $operator = $request->input('operator', 'AND'); // AND or OR

            if (empty($keywords)) {
                return $this->responder->error('検索キーワードを指定してください。', 400);
            }

            if (!in_array($operator, ['AND', 'OR'])) {
                return $this->responder->error('演算子はANDまたはORを指定してください。', 400);
            }

            // キーワード分割
            $searchTerms = array_filter(
                array_map('trim', explode(',', $keywords))
            );

            if (empty($searchTerms)) {
                return $this->responder->error('有効な検索キーワードがありません。', 400);
            }

            // 検索クエリ構築
            $query = UserNotification::where('user_id', $user->id)
                ->with('template')
                ->whereHas('template', function ($q) use ($searchTerms, $operator) {
                    if ($operator === 'AND') {
                        // AND検索: すべてのキーワードを含む
                        foreach ($searchTerms as $term) {
                            $q->where(function ($subQuery) use ($term) {
                                $subQuery->where('title', 'like', "%{$term}%")
                                    ->orWhere('message', 'like', "%{$term}%");
                            });
                        }
                    } else {
                        // OR検索: いずれかのキーワードを含む
                        $q->where(function ($subQuery) use ($searchTerms) {
                            foreach ($searchTerms as $term) {
                                $subQuery->orWhere('title', 'like', "%{$term}%")
                                    ->orWhere('message', 'like', "%{$term}%");
                            }
                        });
                    }
                });

            // ページネーション実行
            $notifications = $query->latest()->paginate(20);

            return $this->responder->searchResults($notifications, $searchTerms, $operator);

        } catch (\Exception $e) {
            Log::error('通知検索エラー', [
                'user_id' => $request->user()->id,
                'keywords' => $request->input('keywords'),
                'operator' => $request->input('operator'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('通知検索に失敗しました。', 500);
        }
    }
}
