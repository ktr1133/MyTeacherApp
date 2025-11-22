<?php

namespace App\Repositories\Portal;

use App\Models\ContactSubmission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * お問い合わせリポジトリインターフェース
 */
interface ContactSubmissionRepositoryInterface
{
    /**
     * 全てのお問い合わせを取得
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * IDでお問い合わせを取得
     *
     * @param int $id
     * @return ContactSubmission|null
     */
    public function findById(int $id): ?ContactSubmission;

    /**
     * ステータスでフィルタリング
     *
     * @param string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByApp(string $appName, int $perPage = 15): LengthAwarePaginator;

    /**
     * お問い合わせを作成
     *
     * @param array $data
     * @return ContactSubmission
     */
    public function create(array $data): ContactSubmission;

    /**
     * お問い合わせを更新
     *
     * @param ContactSubmission $submission
     * @param array $data
     * @return ContactSubmission
     */
    public function update(ContactSubmission $submission, array $data): ContactSubmission;

    /**
     * お問い合わせを削除
     *
     * @param ContactSubmission $submission
     * @return bool
     */
    public function delete(ContactSubmission $submission): bool;

    /**
     * 管理画面用：複数条件でフィルタリング＋ページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * ステータスを更新
     *
     * @param ContactSubmission $submission
     * @param string $status
     * @param string|null $adminNote
     * @return ContactSubmission
     */
    public function updateStatus(ContactSubmission $submission, string $status, ?string $adminNote = null): ContactSubmission;
}
