<?php

namespace App\Repositories\Portal;

use App\Models\ContactSubmission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * お問い合わせEloquentリポジトリ
 */
class EloquentContactSubmissionRepository implements ContactSubmissionRepositoryInterface
{
    /**
     * 全てのお問い合わせを取得
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ContactSubmission::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * IDでお問い合わせを取得
     *
     * @param int $id
     * @return ContactSubmission|null
     */
    public function findById(int $id): ?ContactSubmission
    {
        return ContactSubmission::with('user')->find($id);
    }

    /**
     * ステータスでフィルタリング
     *
     * @param string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return ContactSubmission::where('status', $status)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByApp(string $appName, int $perPage = 15): LengthAwarePaginator
    {
        return ContactSubmission::where('app_name', $appName)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * お問い合わせを作成
     *
     * @param array $data
     * @return ContactSubmission
     */
    public function create(array $data): ContactSubmission
    {
        return ContactSubmission::create($data);
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
        $submission->update($data);
        return $submission->fresh();
    }

    /**
     * お問い合わせを削除
     *
     * @param ContactSubmission $submission
     * @return bool
     */
    public function delete(ContactSubmission $submission): bool
    {
        return $submission->delete();
    }

    /**
     * 管理画面用：複数条件でフィルタリング＋ページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ContactSubmission::with('user');

        // ステータスフィルター
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // アプリフィルター
        if (!empty($filters['app_name'])) {
            $query->where('app_name', $filters['app_name']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ステータスを更新
     *
     * @param ContactSubmission $submission
     * @param string $status
     * @param string|null $adminNote
     * @return ContactSubmission
     */
    public function updateStatus(ContactSubmission $submission, string $status, ?string $adminNote = null): ContactSubmission
    {
        $data = ['status' => $status];
        
        if ($adminNote !== null) {
            $data['admin_note'] = $adminNote;
        }

        $submission->update($data);
        return $submission->fresh();
    }
}
