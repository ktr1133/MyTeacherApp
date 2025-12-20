<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Services\Security\ClamAVScanService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VirusScanServiceTest extends TestCase
{
    protected ClamAVScanService $scanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanService = app(ClamAVScanService::class);
    }

    /**
     * ClamAVが利用可能かテスト
     */
    public function test_clamav_is_available(): void
    {
        $this->assertTrue($this->scanService->isAvailable());
    }

    /**
     * クリーンファイルのスキャンテスト
     */
    public function test_clean_file_passes_scan(): void
    {
        // テスト用クリーンファイルを作成
        Storage::fake('local');
        $file = UploadedFile::fake()->create('clean.txt', 10);

        // スキャン実行
        $isClean = $this->scanService->scan($file);
        $result = $this->scanService->getScanResult();

        // 検証
        $this->assertTrue($isClean, 'Clean file should pass virus scan');
        $this->assertEquals('clean', $result['status']);
        $this->assertEquals('No virus detected', $result['message']);
    }

    /**
     * EICAR テストファイルの検出テスト
     * 
     * EICARは標準的なウイルステスト用疑似ウイルス
     * https://www.eicar.org/download-anti-malware-testfile/
     */
    public function test_eicar_test_file_detected(): void
    {
        // EICARテストファイルを作成（/tmpにclamdが読める権限で作成）
        // 注意: これは実際のウイルスではなく、アンチウイルスソフトのテスト用文字列
        $eicarString = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
        $testFilePath = '/tmp/test_eicar_' . uniqid() . '.txt';
        file_put_contents($testFilePath, $eicarString);
        chmod($testFilePath, 0644); // clamdが読めるように設定

        try {
            // スキャン実行
            $isClean = $this->scanService->scan($testFilePath);
            $result = $this->scanService->getScanResult();

            // 検証
            $this->assertFalse($isClean, 'EICAR test file should be detected as infected');
            $this->assertEquals('infected', $result['status']);
            // ClamAVのバージョンにより検出結果が異なる（"Eicar-Test-Signature", "Win.Test.EICAR_HDB-1" 等）
            $this->assertMatchesRegularExpression('/EICAR|Eicar/i', $result['details'] ?? '');
        } finally {
            // クリーンアップ
            if (file_exists($testFilePath)) {
                unlink($testFilePath);
            }
        }
    }

    /**
     * 存在しないファイルのスキャンテスト
     */
    public function test_nonexistent_file_returns_error(): void
    {
        $isClean = $this->scanService->scan('/nonexistent/file.txt');
        $result = $this->scanService->getScanResult();

        $this->assertFalse($isClean, 'Nonexistent file should return false');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('File not found', $result['message']);
    }

    /**
     * UploadedFileとファイルパスの両方でスキャン可能かテスト
     */
    public function test_scan_accepts_uploaded_file_and_path(): void
    {
        // UploadedFileでスキャン
        Storage::fake('local');
        $uploadedFile = UploadedFile::fake()->create('test1.txt', 10);
        $result1 = $this->scanService->scan($uploadedFile);

        // ファイルパスでスキャン（/tmpにclamdが読める権限で作成）
        $testFilePath = '/tmp/test_scan_' . uniqid() . '.txt';
        file_put_contents($testFilePath, 'test content');
        chmod($testFilePath, 0644);
        $result2 = $this->scanService->scan($testFilePath);

        // クリーンアップ
        if (file_exists($testFilePath)) {
            unlink($testFilePath);
        }

        // 両方とも成功すること
        $this->assertTrue($result1, 'UploadedFile should be scannable');
        $this->assertTrue($result2, 'File path should be scannable');
    }

    /**
     * スキャン結果の詳細情報テスト
     */
    public function test_scan_result_contains_expected_fields(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.txt', 10);

        $this->scanService->scan($file);
        $result = $this->scanService->getScanResult();

        // 必須フィールドの存在確認
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('output', $result);
    }
}
