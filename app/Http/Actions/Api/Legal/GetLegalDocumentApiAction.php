<?php

namespace App\Http\Actions\Api\Legal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

            // Bladeファイルから直接HTMLを取得
            $viewName = "legal.{$type}";
            
            if (!view()->exists($viewName)) {
                Log::error('法的文書ビューが見つかりません', [
                    'type' => $type,
                    'view' => $viewName,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => '文書が見つかりませんでした。',
                ], 404);
            }

            // HTMLをレンダリング
            $html = view($viewName)->render();
            
            // style/scriptタグとその内容を削除（モバイル用）
            $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
            $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
            
            // DOCTYPE, html, head, body タグを削除（本文のみ抽出）
            $html = preg_replace('/<!DOCTYPE[^>]*>/is', '', $html);
            $html = preg_replace('/<html[^>]*>/is', '', $html);
            $html = preg_replace('/<\/html>/is', '', $html);
            $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
            $html = preg_replace('/<body[^>]*>/is', '', $html);
            $html = preg_replace('/<\/body>/is', '', $html);
            
            // header/footerタグを削除（ナビゲーション不要）
            $html = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $html);
            $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $html);
            
            // main タグの中身のみ抽出
            if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $matches)) {
                $html = $matches[1];
            }
            
            // ページタイトル（h1）と最終更新日を削除（ヘッダーに表示済み）
            $html = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $html);
            $html = preg_replace('/<p[^>]*>最終更新日:.*?<\/p>/is', '', $html);
            
            // overflow-x-auto divとその閉じタグをペアで削除（中身のtableは残す）
            // class属性の順序や前後の空白に柔軟に対応
            $html = preg_replace('/<div[^>]*class="[^"]*overflow-x-auto[^"]*"[^>]*>(.*?)<\/div>/is', '$1', $html);
            
            // 不要な空白を削除
            $html = trim($html);

            $configKey = str_replace('-', '_', $type);
            $version = config("legal.current_versions.{$configKey}");

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => $type,
                    'html' => $html,  // HTMLとして返す
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
