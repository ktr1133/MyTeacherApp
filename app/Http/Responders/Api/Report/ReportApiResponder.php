<?php

namespace App\Http\Responders\Api\Report;

use Illuminate\Http\JsonResponse;

/**
 * レポートAPI用レスポンダー
 * 
 * レポート・実績関連のAPIレスポンスを整形
 */
class ReportApiResponder
{
    /**
     * パフォーマンス実績レスポンス
     * 
     * @param array $data
     * @return JsonResponse
     */
    public function performance(array $data): JsonResponse
    {
        return response()->json([
            'message' => 'パフォーマンスデータを取得しました。',
            'data' => $data,
        ], 200);
    }

    /**
     * 月次レポート詳細レスポンス
     * 
     * @param array $data
     * @return JsonResponse
     */
    public function monthlyReport(array $data): JsonResponse
    {
        return response()->json([
            'message' => '月次レポートを取得しました。',
            'data' => $data,
        ], 200);
    }

    /**
     * メンバー別概況レスポンス
     * 
     * @param array $data
     * @return JsonResponse
     */
    public function memberSummary(array $data): JsonResponse
    {
        return response()->json([
            'message' => 'メンバー別概況レポートを生成しました。',
            'data' => $data,
        ], 200);
    }

    /**
     * エラーレスポンス
     * 
     * @param string $message
     * @param int $code
     * @param array $additionalData
     * @return JsonResponse
     */
    public function error(string $message, int $code = 400, array $additionalData = []): JsonResponse
    {
        $response = [
            'message' => $message,
        ];

        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }

        return response()->json($response, $code);
    }
}
