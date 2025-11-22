<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Redis健全性監視コマンド
 * 
 * メモリ使用率、キャッシュヒット率をチェックし、
 * 閾値を超えた場合に警告ログを出力する
 */
class MonitorRedisHealth extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'redis:monitor';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'Monitor Redis health and send alerts';

    /**
     * コマンド実行
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $info = Redis::info();
            
            $usedMemory = $info['used_memory_human'] ?? 'unknown';
            $connectedClients = $info['connected_clients'] ?? 0;
            $hitRate = $this->calculateHitRate($info);
            
            // メモリ使用率チェック（80%超えで警告）
            $maxMemory = 512 * 1024 * 1024; // 512MB（redis.confと同期）
            $usedMemoryBytes = $info['used_memory'] ?? 0;
            $memoryUsagePercent = ($usedMemoryBytes / $maxMemory) * 100;
            
            if ($memoryUsagePercent > 80) {
                Log::warning('Redis memory usage high', [
                    'used_memory' => $usedMemory,
                    'percentage' => round($memoryUsagePercent, 2),
                ]);
                $this->warn("Redis memory usage is high: {$usedMemory} ({$memoryUsagePercent}%)");
            }
            
            // キャッシュヒット率チェック（70%未満で警告）
            if ($hitRate < 70 && $hitRate > 0) {
                Log::warning('Redis hit rate low', [
                    'hit_rate' => round($hitRate, 2),
                ]);
                $this->warn("Redis hit rate is low: {$hitRate}%");
            }
            
            Log::info('Redis health check', [
                'used_memory' => $usedMemory,
                'memory_percentage' => round($memoryUsagePercent, 2),
                'connected_clients' => $connectedClients,
                'hit_rate' => round($hitRate, 2),
            ]);
            
            $this->info('Redis Health Check:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Used Memory', $usedMemory],
                    ['Memory Usage', round($memoryUsagePercent, 2) . '%'],
                    ['Connected Clients', $connectedClients],
                    ['Cache Hit Rate', round($hitRate, 2) . '%'],
                ]
            );
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            Log::error('Redis health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->error('Redis health check failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
    
    /**
     * キャッシュヒット率を計算
     *
     * @param array $info Redis INFO出力
     * @return float ヒット率（0-100）
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
}
