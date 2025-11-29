<?php

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * ClamAVウイルススキャンサービス
 * 
 * Stripe要件対応:
 * - アップロードファイルのウイルススキャン
 * - ClamAVによるマルウェア検知
 */
class ClamAVScanService implements VirusScanServiceInterface
{
    /**
     * スキャン結果
     */
    private array $scanResult = [];

    /**
     * ClamAVコマンドパス
     */
    private string $clamScanPath;

    /**
     * タイムアウト（秒）
     */
    private int $timeout;

    public function __construct()
    {
        $this->clamScanPath = config('security.clamav.path', '/usr/bin/clamscan');
        $this->timeout = config('security.clamav.timeout', 60);
    }

    /**
     * ファイルをスキャン
     * 
     * @param UploadedFile|string $file アップロードファイルまたはファイルパス
     * @return bool ウイルスが検出されなければtrue
     */
    public function scan(UploadedFile|string $file): bool
    {
        // ファイルパスを取得
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (!file_exists($filePath)) {
            Log::error('Virus scan failed: File not found', ['path' => $filePath]);
            $this->scanResult = [
                'status' => 'error',
                'message' => 'File not found',
                'file' => $filePath,
            ];
            return false;
        }

        try {
            // ClamAVでスキャン実行
            $process = new Process([
                $this->clamScanPath,
                '--no-summary',
                '--infected',
                $filePath
            ]);

            $process->setTimeout($this->timeout);
            $process->run();

            $output = $process->getOutput();
            $exitCode = $process->getExitCode();

            // 終了コード: 0=ウイルスなし, 1=ウイルス検出, 2=エラー
            if ($exitCode === 0) {
                // ウイルスなし
                $this->scanResult = [
                    'status' => 'clean',
                    'message' => 'No virus detected',
                    'file' => $filePath,
                    'output' => $output,
                ];

                Log::info('Virus scan: Clean', ['file' => $filePath]);
                return true;
            } elseif ($exitCode === 1) {
                // ウイルス検出
                $this->scanResult = [
                    'status' => 'infected',
                    'message' => 'Virus detected',
                    'file' => $filePath,
                    'output' => $output,
                    'details' => $this->parseInfectedOutput($output),
                ];

                Log::warning('Virus scan: Infected', [
                    'file' => $filePath,
                    'details' => $this->scanResult['details'],
                ]);
                return false;
            } else {
                // スキャンエラー
                $errorOutput = $process->getErrorOutput();
                $this->scanResult = [
                    'status' => 'error',
                    'message' => 'Scan error',
                    'file' => $filePath,
                    'exit_code' => $exitCode,
                    'output' => $output,
                    'error' => $errorOutput,
                ];

                Log::error('Virus scan: Error', [
                    'file' => $filePath,
                    'exit_code' => $exitCode,
                    'error' => $errorOutput,
                ]);
                return false;
            }
        } catch (ProcessFailedException $e) {
            Log::error('Virus scan: Process failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            $this->scanResult = [
                'status' => 'error',
                'message' => 'Process failed',
                'file' => $filePath,
                'error' => $e->getMessage(),
            ];

            return false;
        } catch (\Exception $e) {
            Log::error('Virus scan: Exception', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            $this->scanResult = [
                'status' => 'error',
                'message' => 'Unexpected error',
                'file' => $filePath,
                'error' => $e->getMessage(),
            ];

            return false;
        }
    }

    /**
     * スキャン結果の詳細を取得
     * 
     * @return array スキャン結果
     */
    public function getScanResult(): array
    {
        return $this->scanResult;
    }

    /**
     * ClamAVの動作確認
     * 
     * @return bool ClamAVが正常に動作していればtrue
     */
    public function isAvailable(): bool
    {
        try {
            $process = new Process([$this->clamScanPath, '--version']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (\Exception $e) {
            Log::warning('ClamAV is not available', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 感染ファイル情報をパース
     * 
     * @param string $output ClamAV出力
     * @return string ウイルス名
     */
    private function parseInfectedOutput(string $output): string
    {
        // 出力例: "/path/to/file: Virus.Name FOUND"
        if (preg_match('/: (.+) FOUND/', $output, $matches)) {
            return $matches[1];
        }

        return 'Unknown virus';
    }
}
