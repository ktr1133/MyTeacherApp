<?php

namespace App\Http\Actions\Api\Legal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * API: 法的文書取得アクション
 * 
 * プライバシーポリシー・利用規約のテキストを取得
 * モバイルアプリでのモーダル表示用
 * 
 * @package App\Http\Actions\Api\Legal
 */
class GetLegalDocumentApiAction
{
    /**
     * 法的文書を取得
     * 
     * @param Request $request
     * @param string $type privacy-policy | terms-of-service
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $type): JsonResponse
    {
        try {
            // 許可された文書タイプのみ
            if (!in_array($type, ['privacy-policy', 'terms-of-service'])) {
                return response()->json([
                    'success' => false,
                    'message' => '無効な文書タイプです。',
                ], 400);
            }

            $configKey = str_replace('-', '_', $type);
            $filePath = config("legal.file_paths.{$configKey}");

            if (!$filePath || !File::exists($filePath)) {
                Log::error('法的文書ファイルが見つかりません', [
                    'type' => $type,
                    'path' => $filePath,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => '文書が見つかりませんでした。',
                ], 404);
            }

            $content = File::get($filePath);
            $version = config("legal.current_versions.{$configKey}");

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => $type,
                    'content' => $content,
                    'version' => $version,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('法的文書取得エラー', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '文書の取得に失敗しました。',
            ], 500);
        }
    }
}
