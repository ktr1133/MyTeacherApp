<?php

namespace App\Services\Fcm;

/**
 * Firebase Cloud Messaging サービスのインターフェース
 * 
 * FCM経由でPush通知を送信するビジネスロジックを担当。
 * 
 * @package App\Services\Fcm
 */
interface FcmServiceInterface
{
    /**
     * 単一デバイスにPush通知を送信
     *
     * @param string $deviceToken FCMデバイストークン
     * @param array $payload 通知ペイロード（notification, data）
     * @return array 送信結果（success, error）
     */
    public function sendToDevice(string $deviceToken, array $payload): array;

    /**
     * 複数デバイスにPush通知を送信（バッチ処理）
     *
     * @param array $deviceTokens FCMデバイストークンの配列
     * @param array $payload 通知ペイロード（notification, data）
     * @return array 送信結果の配列（['success' => int, 'failed' => int, 'errors' => array]）
     */
    public function sendToMultipleDevices(array $deviceTokens, array $payload): array;

    /**
     * 通知ペイロードを構築
     *
     * @param string $title タイトル
     * @param string $body メッセージ本文
     * @param array $data カスタムデータ
     * @return array FCM送信用のペイロード
     */
    public function buildPayload(string $title, string $body, array $data = []): array;

    /**
     * FCM送信エラーの種別を判定
     *
     * @param string $errorCode FCMエラーコード
     * @return string エラー種別（invalid_token, unavailable, other）
     */
    public function getErrorType(string $errorCode): string;
}
