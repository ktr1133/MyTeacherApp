<?php

namespace App\Services\Portal;

use App\Models\ContactSubmission;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * お問い合わせサービスインターフェース
 */
interface ContactServiceInterface
{
    /**
     * お問い合わせを作成
     *
     * @param array $data
     * @return ContactSubmission
     */
    public function create(array $data): ContactSubmission;

    /**
     * お問い合わせ一覧を取得
     *
     * @param string|null $status
     * @param string|null $appName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(?string $status = null, ?string $appName = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * お問い合わせを更新
     *
     * @param ContactSubmission $submission
     * @param array $data
     * @return ContactSubmission
     */
    public function update(ContactSubmission $submission, array $data): ContactSubmission;

    /**
     * ステータスを変更
     *
     * @param ContactSubmission $submission
     * @param string $status
     * @return ContactSubmission
     */
    public function changeStatus(ContactSubmission $submission, string $status): ContactSubmission;

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * IDで取得
     *
     * @param int $id
     * @return ContactSubmission|null
     */
    public function findById(int $id): ?ContactSubmission;

    /**
     * ステータス更新（管理者メモ付き）
     *
     * @param ContactSubmission $submission
     * @param string $status
     * @param string|null $adminNote
     * @return ContactSubmission
     */
    public function updateStatusWithNote(ContactSubmission $submission, string $status, ?string $adminNote = null): ContactSubmission;
}
