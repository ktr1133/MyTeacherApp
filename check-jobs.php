<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 本番環境の設定
putenv('DB_HOST=myteacher-production-db.cnosuqkgko37.ap-northeast-1.rds.amazonaws.com');
putenv('DB_DATABASE=myteacher_production');
putenv('DB_USERNAME=myteacher_admin');

// パスワードを環境変数または直接設定
// putenv('DB_PASSWORD=your-password-here');

echo "Connecting to production database...\n";
echo "Host: " . env('DB_HOST') . "\n";
echo "Database: " . env('DB_DATABASE') . "\n\n";

try {
    // Jobsテーブルを確認
    $jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(10)->get();
    echo "===== Pending Jobs (最新10件) =====\n";
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo sprintf(
            "ID: %d | Queue: %s | Attempts: %d | Created: %s | Job: %s\n",
            $job->id,
            $job->queue,
            $job->attempts,
            date('Y-m-d H:i:s', $job->created_at),
            $displayName
        );
    }
    
    echo "\n===== Failed Jobs (最新10件) =====\n";
    $failedJobs = DB::table('failed_jobs')->orderBy('id', 'desc')->limit(10)->get();
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo sprintf(
            "ID: %d | Queue: %s | Failed at: %s | Job: %s\n",
            $job->id,
            $job->queue,
            $job->failed_at,
            $displayName
        );
        echo "Exception: " . substr($job->exception, 0, 200) . "...\n\n";
    }
    
    echo "\n===== Statistics =====\n";
    echo "Pending jobs: " . DB::table('jobs')->count() . "\n";
    echo "Failed jobs: " . DB::table('failed_jobs')->count() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
