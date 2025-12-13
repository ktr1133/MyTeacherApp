<?php

namespace App\Http\Actions\Storage;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;

/**
 * S3/MinIO画像プロキシアクション
 * 
 * モバイルアプリからngrok経由でMinIO画像にアクセスするためのプロキシ
 * パス: /mtdev-app-bucket/{path}
 */
class ProxyS3ImageAction
{
    /**
     * S3/MinIOから画像を取得してストリーミング
     *
     * @param Request $request
     * @param string $path S3パス（例: avatars/2/full_body_happy_1765344451.png）
     * @return StreamedResponse
     */
    public function __invoke(Request $request, string $path): StreamedResponse
    {
        try {
            // S3から画像を取得
            if (!Storage::disk('s3')->exists($path)) {
                abort(404, 'Image not found');
            }

            $file = Storage::disk('s3')->get($path);
            $mimeType = Storage::disk('s3')->mimeType($path);

            // ストリーミングレスポンスを返す
            return response()->stream(
                function () use ($file) {
                    echo $file;
                },
                200,
                [
                    'Content-Type' => $mimeType,
                    'Content-Length' => strlen($file),
                    'Content-Disposition' => 'inline',
                    'Cache-Control' => 'public, max-age=31536000', // 1年キャッシュ
                    'Access-Control-Allow-Origin' => '*', // CORS許可
                    'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );

        } catch (\Exception $e) {
            Log::error('[ProxyS3ImageAction] Failed to proxy image', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Failed to load image');
        }
    }
}
