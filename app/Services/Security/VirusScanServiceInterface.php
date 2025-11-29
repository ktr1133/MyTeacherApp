<?php

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;

/**
 * ウイルススキャンサービスインターフェース
 * 
 * Stripe要件対応:
 * - アップロードファイルのウイルススキャン
 * - ClamAVによるマルウェア検知
 */
interface VirusScanServiceInterface
{
    /**
     * ファイルをスキャン
     * 
     * @param UploadedFile|string $file アップロードファイルまたはファイルパス
     * @return bool ウイルスが検出されなければtrue
     */
    public function scan(UploadedFile|string $file): bool;

    /**
     * スキャン結果の詳細を取得
     * 
     * @return array スキャン結果
     */
    public function getScanResult(): array;

    /**
     * ClamAVの動作確認
     * 
     * @return bool ClamAVが正常に動作していればtrue
     */
    public function isAvailable(): bool;
}
