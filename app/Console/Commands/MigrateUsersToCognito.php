<?php

namespace App\Console\Commands;

use App\Models\User;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 既存ユーザーをCognito User Poolへ移行するコマンド
 * 
 * Phase 1: JWT認証への移行
 * - 既存のusersテーブルからCognitoへユーザーを移行
 * - 一時パスワードを生成し、メール送信
 * - cognito_sub、auth_providerを更新
 * 
 * 使用方法:
 *   php artisan cognito:migrate-users          # 全ユーザー移行
 *   php artisan cognito:migrate-users --user=1 # 特定ユーザー移行
 *   php artisan cognito:migrate-users --dry-run # ドライラン
 */
class MigrateUsersToCognito extends Command
{
    /**
     * コマンド名
     */
    protected $signature = 'cognito:migrate-users
                            {--user= : 特定のユーザーIDのみ移行}
                            {--dry-run : 実際の移行は行わず、プレビューのみ}
                            {--force : 確認なしで実行}';

    /**
     * コマンド説明
     */
    protected $description = '既存ユーザーをCognito User Poolへ移行';

    /**
     * Cognito クライアント
     */
    private CognitoIdentityProviderClient $cognitoClient;

    /**
     * User Pool ID
     */
    private string $userPoolId;

    /**
     * 移行統計
     */
    private array $stats = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    /**
     * コマンド実行
     */
    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  Cognito User Migration Tool');
        $this->info('===========================================');
        $this->newLine();

        // Cognito設定チェック
        if (!$this->checkCognitoConfig()) {
            return Command::FAILURE;
        }

        // Cognitoクライアント初期化
        $this->initializeCognitoClient();

        // 移行対象ユーザー取得
        $users = $this->getTargetUsers();

        if ($users->isEmpty()) {
            $this->warn('移行対象のユーザーが見つかりませんでした。');
            return Command::SUCCESS;
        }

        $this->stats['total'] = $users->count();
        $this->info("移行対象: {$this->stats['total']} ユーザー");
        $this->newLine();

        // ドライラン
        if ($this->option('dry-run')) {
            $this->dryRun($users);
            return Command::SUCCESS;
        }

        // 確認
        if (!$this->option('force') && !$this->confirm('移行を開始しますか？')) {
            $this->warn('移行がキャンセルされました。');
            return Command::SUCCESS;
        }

        // プログレスバー表示
        $this->newLine();
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        // ユーザーごとに移行
        foreach ($users as $user) {
            $this->migrateUser($user);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // 結果表示
        $this->displayResults();

        return Command::SUCCESS;
    }

    /**
     * Cognito設定をチェック
     */
    private function checkCognitoConfig(): bool
    {
        $this->userPoolId = config('services.cognito.user_pool_id');
        $region = config('services.cognito.region');

        if (empty($this->userPoolId)) {
            $this->error('COGNITO_USER_POOL_IDが設定されていません。');
            $this->info('.envファイルを確認してください。');
            return false;
        }

        if (empty($region)) {
            $this->error('COGNITO_REGIONが設定されていません。');
            return false;
        }

        $this->info("✓ User Pool ID: {$this->userPoolId}");
        $this->info("✓ Region: {$region}");
        $this->newLine();

        return true;
    }

    /**
     * Cognitoクライアント初期化
     */
    private function initializeCognitoClient(): void
    {
        $config = [
            'version' => 'latest',
            'region' => config('services.cognito.region'),
        ];
        
        // AWS認証情報を明示的に指定（.envのAWS_*がMinIO用のため）
        // Cognitoへのアクセスには別の認証情報を使用
        $cognitoAccessKey = env('COGNITO_ACCESS_KEY_ID');
        $cognitoSecretKey = env('COGNITO_SECRET_ACCESS_KEY');
        
        if ($cognitoAccessKey && $cognitoSecretKey) {
            $config['credentials'] = [
                'key' => $cognitoAccessKey,
                'secret' => $cognitoSecretKey,
            ];
        } else {
            // デフォルトのクレデンシャルチェーン（EC2インスタンスプロファイル、環境変数等）を使用
            $this->warn('COGNITO_ACCESS_KEY_ID/COGNITO_SECRET_ACCESS_KEYが設定されていません。');
            $this->warn('デフォルトのAWS認証情報を使用します。');
        }
        
        $this->cognitoClient = new CognitoIdentityProviderClient($config);
    }

    /**
     * 移行対象ユーザーを取得
     */
    private function getTargetUsers()
    {
        $query = User::query()
            ->where('auth_provider', 'breeze')
            ->whereNull('cognito_sub');

        // 特定ユーザー指定
        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        return $query->get();
    }

    /**
     * ドライラン（プレビュー）
     */
    private function dryRun($users): void
    {
        $this->warn('--- DRY RUN MODE ---');
        $this->info('以下のユーザーが移行されます:');
        $this->newLine();

        $headers = ['ID', 'Username', 'Email', 'Name', 'Created'];
        $rows = $users->map(fn($user) => [
            $user->id,
            $user->username,
            $user->email ?? $user->username . '@myteacher.local',
            $user->name ?? $user->username,
            $user->created_at->format('Y-m-d H:i'),
        ]);

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('実際に移行するには --dry-run オプションを外して実行してください。');
    }

    /**
     * ユーザーをCognitoへ移行
     */
    private function migrateUser(User $user): void
    {
        try {
            // 既にCognito移行済みの場合はスキップ
            if ($user->cognito_sub) {
                $this->stats['skipped']++;
                Log::info("User already migrated to Cognito", ['user_id' => $user->id]);
                return;
            }

            // メールアドレスと表示名の準備（nullの場合は疑似値）
            $email = $user->email ?? $user->username . '@myteacher.local';
            $name = $user->name ?? $user->username;
            
            // 一時パスワード生成
            $temporaryPassword = $this->generateTemporaryPassword();

            // Cognitoにユーザー作成
            $result = $this->cognitoClient->adminCreateUser([
                'UserPoolId' => $this->userPoolId,
                'Username' => $email,
                'UserAttributes' => [
                    ['Name' => 'email', 'Value' => $email],
                    ['Name' => 'email_verified', 'Value' => 'true'],
                    ['Name' => 'name', 'Value' => $name],
                    ['Name' => 'custom:timezone', 'Value' => $user->timezone ?? 'Asia/Tokyo'],
                    ['Name' => 'custom:is_admin', 'Value' => $user->is_admin ? 'true' : 'false'],
                ],
                'TemporaryPassword' => $temporaryPassword,
                'MessageAction' => 'SUPPRESS', // メール送信を抑制（手動で送信）
                'DesiredDeliveryMediums' => ['EMAIL'],
            ]);

            // Cognito Sub取得
            $cognitoSub = null;
            foreach ($result['User']['Attributes'] as $attribute) {
                if ($attribute['Name'] === 'sub') {
                    $cognitoSub = $attribute['Value'];
                    break;
                }
            }

            if (!$cognitoSub) {
                throw new \Exception('Cognito Sub not found in response');
            }

            // DBを更新
            $user->update([
                'cognito_sub' => $cognitoSub,
                'auth_provider' => 'cognito',
            ]);

            // TODO: メール送信（一時パスワードを通知）
            // $this->sendMigrationEmail($user, $temporaryPassword);

            $this->stats['success']++;

            Log::info('User migrated to Cognito successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'cognito_sub' => $cognitoSub,
            ]);

        } catch (AwsException $e) {
            $this->stats['failed']++;
            
            $this->error("AWS Error for user {$user->id}: " . $e->getMessage());
            $this->error("AWS Error Code: " . $e->getAwsErrorCode());
            
            Log::error('Cognito migration failed', [
                'user_id' => $user->id,
                'email' => $email ?? 'N/A',
                'error' => $e->getMessage(),
                'aws_error_code' => $e->getAwsErrorCode(),
            ]);

            // ユーザー既存エラーの場合は情報を取得して更新
            if ($e->getAwsErrorCode() === 'UsernameExistsException') {
                $this->handleExistingUser($user);
            }

        } catch (\Exception $e) {
            $this->stats['failed']++;
            
            $this->error("Error for user {$user->id}: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            Log::error('User migration failed', [
                'user_id' => $user->id,
                'email' => $email ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 既存Cognitoユーザーの処理
     */
    private function handleExistingUser(User $user): void
    {
        try {
            // Cognitoからユーザー情報取得
            $result = $this->cognitoClient->adminGetUser([
                'UserPoolId' => $this->userPoolId,
                'Username' => $user->email,
            ]);

            // Cognito Sub取得
            $cognitoSub = null;
            foreach ($result['UserAttributes'] as $attribute) {
                if ($attribute['Name'] === 'sub') {
                    $cognitoSub = $attribute['Value'];
                    break;
                }
            }

            if ($cognitoSub) {
                $user->update([
                    'cognito_sub' => $cognitoSub,
                    'auth_provider' => 'cognito',
                ]);

                $this->stats['success']++;
                $this->stats['failed']--;

                Log::info('Existing Cognito user linked', [
                    'user_id' => $user->id,
                    'cognito_sub' => $cognitoSub,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to link existing Cognito user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 一時パスワード生成
     * 
     * Cognitoのパスワードポリシーに準拠:
     * - 8文字以上
     * - 大文字、小文字、数字、記号を含む
     */
    private function generateTemporaryPassword(): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%^&*';

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // 残りの文字をランダムに追加（合計12文字）
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 0; $i < 8; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // シャッフル
        return str_shuffle($password);
    }

    /**
     * 移行結果を表示
     */
    private function displayResults(): void
    {
        $this->info('===========================================');
        $this->info('  Migration Results');
        $this->info('===========================================');
        $this->newLine();

        $this->info("総数:     {$this->stats['total']}");
        $this->info("<fg=green>成功:     {$this->stats['success']}</>");
        
        if ($this->stats['failed'] > 0) {
            $this->error("失敗:     {$this->stats['failed']}");
        } else {
            $this->info("失敗:     {$this->stats['failed']}");
        }
        
        $this->info("スキップ: {$this->stats['skipped']}");
        $this->newLine();

        if ($this->stats['failed'] > 0) {
            $this->warn('⚠ 失敗したユーザーがいます。ログを確認してください。');
            $this->info('ログファイル: storage/logs/laravel.log');
        } else {
            $this->info('✓ 全ユーザーの移行が完了しました！');
        }

        $this->newLine();
        $this->info('次のステップ:');
        $this->info('1. ユーザーに一時パスワードをメールで通知');
        $this->info('2. 初回ログイン時にパスワード変更を促す');
        $this->info('3. 並行運用期間中はBreezeとCognito両方のログインをサポート');
    }

    /**
     * メール送信（TODO: 実装）
     * 
     * @param User $user
     * @param string $temporaryPassword
     */
    private function sendMigrationEmail(User $user, string $temporaryPassword): void
    {
        // TODO: メール送信実装
        // Mail::to($user->email)->send(new CognitoMigrationMail($user, $temporaryPassword));
    }
}
