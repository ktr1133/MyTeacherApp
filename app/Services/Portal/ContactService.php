<?php

namespace App\Services\Portal;

use App\Models\ContactSubmission;
use App\Repositories\Portal\ContactSubmissionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * お問い合わせサービス
 */
class ContactService implements ContactServiceInterface
{
    public function __construct(
        private ContactSubmissionRepositoryInterface $repository
    ) {}

    /**
     * お問い合わせを作成
     *
     * @param array $data
     * @return ContactSubmission
     */
    public function create(array $data): ContactSubmission
    {
        $submission = $this->repository->create($data);
        
        Log::info('お問い合わせを受け付けました', [
            'id' => $submission->id,
            'name' => $submission->name,
            'email' => $submission->email,
            'app_name' => $submission->app_name,
        ]);
        
        // TODO: 確認メール送信、管理者通知
        
        return $submission;
    }

    /**
     * お問い合わせ一覧を取得
     *
     * @param string|null $status
     * @param string|null $appName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(?string $status = null, ?string $appName = null, int $perPage = 15): LengthAwarePaginator
    {
        if ($status) {
            return $this->repository->getByStatus($status, $perPage);
        }
        
        if ($appName) {
            return $this->repository->getByApp($appName, $perPage);
        }
        
        return $this->repository->paginate($perPage);
    }

    /**
     * お問い合わせを更新
     *
     * @param ContactSubmission $submission
     * @param array $data
     * @return ContactSubmission
     */
    public function update(ContactSubmission $submission, array $data): ContactSubmission
    {
        return $this->repository->update($submission, $data);
    }

    /**
     * ステータスを変更
     *
     * @param ContactSubmission $submission
     * @param string $status
     * @return ContactSubmission
     */
    public function changeStatus(ContactSubmission $submission, string $status): ContactSubmission
    {
        return $this->update($submission, ['status' => $status]);
    }

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $perPage);
    }

    /**
     * IDで取得
     *
     * @param int $id
     * @return ContactSubmission|null
     */
    public function findById(int $id): ?ContactSubmission
    {
        return $this->repository->findById($id);
    }

    /**
     * ステータス更新（管理者メモ付き）
     *
     * @param ContactSubmission $submission
     * @param string $status
     * @param string|null $adminNote
     * @return ContactSubmission
     */
    public function updateStatusWithNote(ContactSubmission $submission, string $status, ?string $adminNote = null): ContactSubmission
    {
        return $this->repository->updateStatus($submission, $status, $adminNote);
    }
}
