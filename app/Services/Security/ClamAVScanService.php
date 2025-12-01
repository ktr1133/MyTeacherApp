<?php

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

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
     * ClamAVデーモンスキャンコマンドパス
     */
    private string $clamdScanPath;

    /**
     * デーモンモード使用フラグ（テスト環境で高速化）
     */
    private bool $useDaemon;

    /**
     * タイムアウト（秒）
     */
    private int $timeout;

    public function __construct()
    {
        $this->clamScanPath = config('security.clamav.path', '/usr/bin/clamscan');
        $this->clamdScanPath = config('security.clamav.daemon_path', '/usr/bin/clamdscan');
        // テスト環境ではデーモンモードを優先（高速化）
        $this->useDaemon = config('security.clamav.use_daemon', false) || app()->environment('testing');
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
            // デーモンモード or 通常モードでスキャン実行
            if ($this->useDaemon && $this->isDaemonAvailable()) {
                $process = new Process([
                    $this->clamdScanPath,
                    '--no-summary',
                    '--infected',
                    $filePath
                ]);
                $process->setTimeout(5); // デーモンモードは高速（5秒で十分）
            } else {
                $process = new Process([
                    $this->clamScanPath,
                    '--no-summary',
                    '--infected',
                    $filePath
                ]);
                $process->setTimeout($this->timeout);
            }

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
            $path = $this->useDaemon && $this->isDaemonAvailable() ? $this->clamdScanPath : $this->clamScanPath;
            $process = new Process([$path, '--version']);
            $process->setTimeout(2);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::warning('ClamAV command failed', [
                    'path' => $path,
                    'exit_code' => $process->getExitCode(),
                    'output' => $process->getOutput(),
                    'error' => $process->getErrorOutput()
                ]);
                return false;
            }

            return true;
        } catch (ProcessTimedOutException $e) {
            Log::warning('ClamAV command timed out', ['error' => $e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            Log::warning('ClamAV is not available', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ClamAVデーモンが利用可能かチェック
     * 
     * @return bool
     */
    protected function isDaemonAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        try {
            $process = new Process([$this->clamdScanPath, '--version']);
            $process->setTimeout(1);
            $process->run();
            
            $available = $process->isSuccessful();
            return $available;
        } catch (\Exception $e) {
            $available = false;
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
