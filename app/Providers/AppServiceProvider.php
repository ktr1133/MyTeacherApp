<?php

namespace App\Providers;

// リポジトリのインポート
use App\Repositories\Admin\UserRepositoryInterface;
use App\Repositories\Admin\UserRepository;
use App\Repositories\AI\AICostRateRepositoryInterface;
use App\Repositories\AI\AICostRateRepository;
use App\Repositories\AI\AIUsageLogRepositoryInterface;
use App\Repositories\AI\AIUsageLogRepository;
use App\Repositories\Avatar\TeacherAvatarRepositoryInterface;
use App\Repositories\Avatar\TeacherAvatarRepository;
use App\Repositories\Batch\HolidayRepositoryInterface;
use App\Repositories\Batch\HolidayRepository;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Repositories\Batch\ScheduledTaskRepository;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Payment\PaymentHistoryRepositoryInterface;
use App\Repositories\Payment\PaymentHistoryEloquentRepository;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Repositories\Profile\ProfileUserEloquentRepository;
use App\Repositories\Profile\GroupRepositoryInterface;
use App\Repositories\Profile\GroupRepository;
use App\Repositories\Profile\GroupUserRepositoryInterface;
use App\Repositories\Profile\GroupUserRepository;
use App\Repositories\Report\ReportRepositoryInterface;
use App\Repositories\Report\ReportEloquentRepository;
use App\Repositories\Report\MonthlyReportRepositoryInterface;
use App\Repositories\Report\MonthlyReportEloquentRepository;
use App\Repositories\Tag\TagRepositoryInterface;
use App\Repositories\Tag\EloquentTagRepository;
use App\Repositories\Task\TaskEloquentRepository;
use App\Repositories\Task\TaskProposalRepositoryInterface;
use App\Repositories\Task\EloquentTaskProposalRepository;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Repositories\Token\TokenPurchaseRequestRepositoryInterface;
use App\Repositories\Token\TokenPurchaseRequestRepository;
use App\Repositories\Token\TokenEloquentRepository;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Repositories\Token\TokenPackageRepositoryInterface;
use App\Repositories\Token\TokenPackageEloquentRepository;
// Portal
use App\Repositories\Portal\MaintenanceRepositoryInterface;
use App\Repositories\Portal\EloquentMaintenanceRepository;
use App\Repositories\Portal\ContactSubmissionRepositoryInterface;
use App\Repositories\Portal\EloquentContactSubmissionRepository;
use App\Repositories\Portal\FaqRepositoryInterface;
use App\Repositories\Portal\EloquentFaqRepository;
use App\Repositories\Portal\AppUpdateRepositoryInterface;
use App\Repositories\Portal\EloquentAppUpdateRepository;
// サービスのインポート
use App\Services\Admin\UserServiceInterface;
use App\Services\Admin\UserService;
use App\Services\AI\AICostServiceInterface;
use App\Services\AI\AICostService;
use App\Services\AI\StableDiffusionServiceInterface;
use App\Services\AI\StableDiffusionService;
use App\Services\Approval\ApprovalMergeServiceInterface;
use App\Services\Approval\ApprovalMergeService;
use App\Services\Auth\ValidationServiceInterface;
use App\Services\Auth\ValidationService;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use App\Services\Avatar\TeacherAvatarService;
use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Services\Batch\ScheduledTaskService;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\Payment\PaymentService;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\NotificationService;
use App\Services\Profile\ProfileManagementService;
use App\Services\Profile\ProfileManagementServiceInterface;
use App\Services\Profile\GroupServiceInterface;
use App\Services\Profile\GroupService;
use App\Services\Group\GroupTaskLimitServiceInterface;
use App\Services\Group\GroupTaskLimitService;
use App\Services\Report\PerformanceServiceInterface;
use App\Services\Report\PerformanceService;
use App\Services\Report\MonthlyReportServiceInterface;
use App\Services\Report\MonthlyReportService;
use App\Services\Report\PdfGenerationServiceInterface;
use App\Services\Report\PdfGenerationService;
use App\Services\Tag\TagServiceInterface;
use App\Services\Tag\TagService;
use App\Services\Task\TaskApprovalServiceInterface;
use App\Services\Task\TaskApprovalService;
use App\Services\Task\TaskListService;
use App\Services\Task\TaskListServiceInterface;
use App\Services\Task\TaskManagementService;
use App\Services\Task\TaskManagementServiceInterface;
use App\Services\Task\TaskProposalServiceInterface;
use App\Services\Task\TaskProposalService;
use App\Services\Task\TaskSearchServiceInterface;
use App\Services\Task\TaskSearchService;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use App\Services\Token\TokenPurchaseApprovalService;
use App\Services\Token\TokenServiceInterface;
use App\Services\Token\TokenService;
use App\Services\Token\TokenPackageServiceInterface;
use App\Services\Token\TokenPackageService;
use App\Services\Timezone\TimezoneServiceInterface;
use App\Services\Timezone\TimezoneService;
use App\Services\User\UserDeletionServiceInterface;
use App\Services\User\UserDeletionService;
// Portal
use App\Services\Portal\MaintenanceServiceInterface;
use App\Services\Portal\MaintenanceService;
use App\Services\Portal\ContactServiceInterface;
use App\Services\Portal\ContactService;
use App\Services\Portal\FaqServiceInterface;
use App\Services\Portal\FaqService;
use App\Services\Portal\AppUpdateServiceInterface;
use App\Services\Portal\AppUpdateService;
// Subscription
use App\Services\Subscription\SubscriptionServiceInterface;
use App\Services\Subscription\SubscriptionService;
use App\Services\Subscription\SubscriptionWebhookServiceInterface;
use App\Services\Subscription\SubscriptionWebhookService;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Repositories\Subscription\SubscriptionEloquentRepository;
// Security
use App\Services\Security\VirusScanServiceInterface;
use App\Services\Security\ClamAVScanService;

// 外部APIサービスのインポート
use App\Services\AI\OpenAIService;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションのサービスを登録する。
     */
    public function register(): void
    {
        // ========================================
        // 1. リポジトリのバインド
        // ========================================

        // --- Admin ---
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // --- AI Cost ---
        $this->app->bind(AICostRateRepositoryInterface::class, AICostRateRepository::class);
        $this->app->bind(AIUsageLogRepositoryInterface::class, AIUsageLogRepository::class);

        // --- Avatar ---
        $this->app->bind(TeacherAvatarRepositoryInterface::class, TeacherAvatarRepository::class);

        // --- Tag ---
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);

        // --- Task ---
        $this->app->bind(TaskRepositoryInterface::class, TaskEloquentRepository::class);
        $this->app->bind(TaskProposalRepositoryInterface::class, EloquentTaskProposalRepository::class);

        // --- Notification ---
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);

        // --- Payment ---
        $this->app->bind(PaymentHistoryRepositoryInterface::class, PaymentHistoryEloquentRepository::class);

        // --- Profile ---
        $this->app->bind(ProfileUserRepositoryInterface::class, ProfileUserEloquentRepository::class);

        // --- Report ---
        $this->app->bind(ReportRepositoryInterface::class, ReportEloquentRepository::class);
        $this->app->bind(MonthlyReportRepositoryInterface::class, MonthlyReportEloquentRepository::class);

        // --- Batch ---
        $this->app->bind(ScheduledTaskRepositoryInterface::class, ScheduledTaskRepository::class);
        $this->app->bind(HolidayRepositoryInterface::class, HolidayRepository::class);

        // --- Token ---
        $this->app->bind(TokenPurchaseRequestRepositoryInterface::class, TokenPurchaseRequestRepository::class);
        $this->app->bind(TokenRepositoryInterface::class, TokenEloquentRepository::class);
        $this->app->bind(TokenPackageRepositoryInterface::class, TokenPackageEloquentRepository::class);

        // --- Portal ---
        $this->app->bind(MaintenanceRepositoryInterface::class, EloquentMaintenanceRepository::class);
        $this->app->bind(ContactSubmissionRepositoryInterface::class, EloquentContactSubmissionRepository::class);
        $this->app->bind(FaqRepositoryInterface::class, EloquentFaqRepository::class);
        $this->app->bind(AppUpdateRepositoryInterface::class, EloquentAppUpdateRepository::class);

        // --- Subscription ---
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionEloquentRepository::class);

        // ========================================
        // 2. サービスのバインド
        // ========================================

        // --- Admin ---
        $this->app->bind(UserServiceInterface::class, UserService::class);

        // --- AI Cost ---
        $this->app->bind(AICostServiceInterface::class, AICostService::class);

        // --- Approval ---
        $this->app->bind(ApprovalMergeServiceInterface::class, ApprovalMergeService::class);

        // --- Auth ---
        $this->app->bind(ValidationServiceInterface::class, ValidationService::class);
        $this->app->bind(\App\Services\Auth\LoginAttemptServiceInterface::class, \App\Services\Auth\LoginAttemptService::class);

        // --- Security ---
        $this->app->bind(VirusScanServiceInterface::class, ClamAVScanService::class);

        // --- Avatar ---
        $this->app->bind(TeacherAvatarServiceInterface::class, TeacherAvatarService::class);

        // --- Tag ---
        $this->app->bind(TagServiceInterface::class, TagService::class);

        // --- Task ---
        $this->app->bind(TaskApprovalServiceInterface::class, TaskApprovalService::class);
        $this->app->bind(TaskListServiceInterface::class, TaskListService::class);
        $this->app->bind(TaskManagementServiceInterface::class, TaskManagementService::class);
        $this->app->bind(TaskProposalServiceInterface::class, TaskProposalService::class);
        $this->app->bind(TaskSearchServiceInterface::class, TaskSearchService::class);

        // --- Profile ---
        $this->app->bind(ProfileManagementServiceInterface::class, ProfileManagementService::class);
        $this->app->bind(GroupServiceInterface::class, GroupService::class);
        $this->app->bind(GroupTaskLimitServiceInterface::class, GroupTaskLimitService::class);
        $this->app->bind(GroupRepositoryInterface::class, GroupRepository::class);
        $this->app->bind(GroupUserRepositoryInterface::class, GroupUserRepository::class);

        // --- Report ---
        $this->app->bind(PerformanceServiceInterface::class, PerformanceService::class);
        $this->app->bind(MonthlyReportServiceInterface::class, MonthlyReportService::class);
        $this->app->bind(PdfGenerationServiceInterface::class, PdfGenerationService::class);

        // --- Batch ---
        $this->app->bind(ScheduledTaskServiceInterface::class, ScheduledTaskService::class);

        // --- Payment ---
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);

        // --- Notification ---
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);

        // --- Token ---
        $this->app->bind(TokenPurchaseApprovalServiceInterface::class, TokenPurchaseApprovalService::class);
        $this->app->bind(TokenServiceInterface::class, TokenService::class);
        $this->app->bind(TokenPackageServiceInterface::class, TokenPackageService::class);

        // --- Timezone ---
        $this->app->bind(TimezoneServiceInterface::class, TimezoneService::class);

        // --- User ---
        $this->app->bind(UserDeletionServiceInterface::class, UserDeletionService::class);

        // --- Portal ---
        $this->app->bind(MaintenanceServiceInterface::class, MaintenanceService::class);
        $this->app->bind(ContactServiceInterface::class, ContactService::class);
        $this->app->bind(FaqServiceInterface::class, FaqService::class);
        $this->app->bind(AppUpdateServiceInterface::class, AppUpdateService::class);

        // --- Subscription ---
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(SubscriptionWebhookServiceInterface::class, SubscriptionWebhookService::class);

        // --- AI ---
        $this->app->bind(StableDiffusionServiceInterface::class, StableDiffusionService::class);

        // ★ OpenAIService (外部API連携) のバインド
        // 依存性がないため、直接インスタンス化可能
        $this->app->singleton(OpenAIService::class, function ($app) {
            $apiKey = (string) $app['config']->get('services.openai.api_key', env('OPENAI_API_KEY', ''));
            if ($apiKey === '') {
                Log::warning('OpenAI API key is not set. Set OPENAI_API_KEY in .env or services.openai.api_key in config/services.php');
            }
            return new OpenAIService($apiKey);
        });
    }

    /**
     * アプリケーションの起動後にサービスを登録する。
     */
    public function boot(): void
    {
        // HTTPS強制設定（環境変数で制御可能）
        // 本番環境: デフォルトtrue（ALB経由でもHTTPSとして認識）
        // ローカル環境: デフォルトfalse（HTTP開発サーバーで動作）
        $forceHttps = env('FORCE_HTTPS', $this->app->environment('production'));
        
        if ($forceHttps) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            Log::info('HTTPS scheme forced for all URLs');
        }
    }
}
