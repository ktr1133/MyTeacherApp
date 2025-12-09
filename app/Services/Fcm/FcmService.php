<?php

namespace App\Services\Fcm;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging サービス実装
 * 
 * Firebase Admin SDK を使用してPush通知を送信。
 * エラーハンドリング、ログ記録を実装。
 * 
 * @package App\Services\Fcm
 */
class FcmService implements FcmServiceInterface
{
    /**
     * Firebase Messaging インスタンス
     */
    private $messaging;

    /**
     * コンストラクタ
     * 
     * Firebase Admin SDKを初期化。
     * config/services.php の firebase 設定を使用。
     */
    public function __construct()
    {
        try {
            $credentialsPath = config('services.firebase.credentials');
            
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::error('Firebase credentials file not found', [
                    'path' => $credentialsPath,
                ]);
                throw new \RuntimeException('Firebase credentials file not found: ' . $credentialsPath);
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
            
            Log::info('Firebase Messaging initialized successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to initialize Firebase Messaging', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 単一デバイスにPush通知を送信
     *
     * @param string $deviceToken FCMデバイストークン
     * @param array $payload 通知ペイロード（notification, data）
     * @return array 送信結果（success, error）
     */
    public function sendToDevice(string $deviceToken, array $payload): array
    {
        try {
            $notification = Notification::create(
                $payload['notification']['title'],
                $payload['notification']['body']
            );

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);

            if (!empty($payload['data'])) {
                $message = $message->withData($payload['data']);
            }

            $this->messaging->send($message);

            Log::info('FCM push notification sent successfully', [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'title' => $payload['notification']['title'],
            ]);

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (MessagingException $e) {
            $errorCode = $e->errors()['error'] ?? 'unknown';
            
            Log::warning('FCM push notification failed', [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'error_code' => $errorCode,
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $errorCode,
            ];
        } catch (\Throwable $e) {
            Log::error('Unexpected error while sending FCM notification', [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'unexpected_error',
            ];
        }
    }

    /**
     * 複数デバイスにPush通知を送信（バッチ処理）
     *
     * @param array $deviceTokens FCMデバイストークンの配列
     * @param array $payload 通知ペイロード（notification, data）
     * @return array 送信結果の配列（['success' => int, 'failed' => int, 'errors' => array]）
     */
    public function sendToMultipleDevices(array $deviceTokens, array $payload): array
    {
        if (empty($deviceTokens)) {
            Log::warning('sendToMultipleDevices called with empty device tokens');
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // FCM Multicast APIは最大500件まで（公式ドキュメント）
        $chunks = array_chunk($deviceTokens, 500);

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                $notification = Notification::create(
                    $payload['notification']['title'],
                    $payload['notification']['body']
                );

                $message = CloudMessage::new()->withNotification($notification);

                if (!empty($payload['data'])) {
                    $message = $message->withData($payload['data']);
                }

                $sendReport = $this->messaging->sendMulticast($message, $chunk);

                $results['success'] += $sendReport->successes()->count();
                $results['failed'] += $sendReport->failures()->count();

                // 失敗したトークンのエラー情報を収集
                foreach ($sendReport->failures()->getItems() as $failure) {
                    $token = $failure->target()->value();
                    $error = $failure->error();
                    
                    $results['errors'][] = [
                        'device_token' => substr($token, 0, 20) . '...',
                        'error_code' => $error->getMessage(),
                    ];
                }

                Log::info('FCM multicast push notification sent', [
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => count($chunk),
                    'success' => $sendReport->successes()->count(),
                    'failed' => $sendReport->failures()->count(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send FCM multicast notification', [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $results['failed'] += count($chunk);
            }
        }

        Log::info('FCM multicast push notification completed', [
            'total_tokens' => count($deviceTokens),
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * 通知ペイロードを構築
     *
     * @param string $title タイトル
     * @param string $body メッセージ本文
     * @param array $data カスタムデータ
     * @return array FCM送信用のペイロード
     */
    public function buildPayload(string $title, string $body, array $data = []): array
    {
        return [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ];
    }

    /**
     * FCM送信エラーの種別を判定
     *
     * @param string $errorCode FCMエラーコード
     * @return string エラー種別（invalid_token, unavailable, other）
     */
    public function getErrorType(string $errorCode): string
    {
        // Firebase Admin SDK のエラーメッセージから判定
        if (str_contains($errorCode, 'InvalidRegistration') || 
            str_contains($errorCode, 'NotRegistered') ||
            str_contains($errorCode, 'invalid-registration-token')) {
            return 'invalid_token';
        }

        if (str_contains($errorCode, 'Unavailable') || 
            str_contains($errorCode, 'Internal') ||
            str_contains($errorCode, 'timeout')) {
            return 'unavailable';
        }

        return 'other';
    }
}
