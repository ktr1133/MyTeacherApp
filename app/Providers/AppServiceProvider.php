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
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Repositories\Profile\ProfileUserEloquentRepository;
use App\Repositories\Profile\GroupRepositoryInterface;
use App\Repositories\Profile\GroupRepository;
use App\Repositories\Profile\GroupUserRepositoryInterface;
use App\Repositories\Profile\GroupUserRepository;
use App\Repositories\Report\ReportRepositoryInterface;
use App\Repositories\Report\ReportEloquentRepository;
use App\Repositories\Tag\TagRepositoryInterface;
use App\Repositories\Tag\EloquentTagRepository;
use App\Repositories\Task\TaskEloquentRepository;
use App\Repositories\Task\TaskProposalRepositoryInterface;
use App\Repositories\Task\EloquentTaskProposalRepository;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Repositories\Token\TokenEloquentRepository;
use App\Repositories\Token\TokenPackageRepositoryInterface;
use App\Repositories\Token\TokenPackageEloquentRepository;
// サービスのインポート
use App\Services\Admin\UserServiceInterface;
use App\Services\Admin\UserService;
use App\Services\AI\AICostServiceInterface;
use App\Services\AI\AICostService;
use App\Services\AI\StableDiffusionServiceInterface;
use App\Services\AI\StableDiffusionService;
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
use App\Services\Report\PerformanceServiceInterface;
use App\Services\Report\PerformanceService;
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
use App\Services\Token\TokenServiceInterface;
use App\Services\Token\TokenService;
use App\Services\Token\TokenPackageServiceInterface;
use App\Services\Token\TokenPackageService;

// 外部APIサービスのインポート
use App\Services\AI\OpenAIService;

use Illuminate\Support\ServiceProvider;

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

        // --- Profile ---
        $this->app->bind(ProfileUserRepositoryInterface::class, ProfileUserEloquentRepository::class);

        // --- Report ---
        $this->app->bind(ReportRepositoryInterface::class, ReportEloquentRepository::class);

        // --- Batch ---
        $this->app->bind(ScheduledTaskRepositoryInterface::class, ScheduledTaskRepository::class);
        $this->app->bind(HolidayRepositoryInterface::class, HolidayRepository::class);

        // --- Token ---
        $this->app->bind(TokenRepositoryInterface::class, TokenEloquentRepository::class);
        $this->app->bind(TokenPackageRepositoryInterface::class, TokenPackageEloquentRepository::class);

        // ========================================
        // 2. サービスのバインド
        // ========================================

        // --- Admin ---
        $this->app->bind(UserServiceInterface::class, UserService::class);

        // --- AI Cost ---
        $this->app->bind(AICostServiceInterface::class, AICostService::class);

        // --- Auth ---
        $this->app->bind(ValidationServiceInterface::class, ValidationService::class);

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
        $this->app->bind(GroupRepositoryInterface::class, GroupRepository::class);
        $this->app->bind(GroupUserRepositoryInterface::class, GroupUserRepository::class);

        // --- Report ---
        $this->app->bind(PerformanceServiceInterface::class, PerformanceService::class);

        // --- Batch ---
        $this->app->bind(ScheduledTaskServiceInterface::class, ScheduledTaskService::class);

        // --- Payment ---
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);

        // --- Notification ---
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);

        // --- Token ---
        $this->app->bind(TokenServiceInterface::class, TokenService::class);
        $this->app->bind(TokenPackageServiceInterface::class, TokenPackageService::class);

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
        //
    }
}
