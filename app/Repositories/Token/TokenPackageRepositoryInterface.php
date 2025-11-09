<?php

namespace App\Repositories\Token;

use App\Models\TokenPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TokenPackageRepositoryInterface
{
    /**
     * トークンパッケージの一覧をページネートで取得する
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    /**
     * 指定IDのトークンパッケージを取得する
     *
     * @param int $id
     * @return TokenPackage|null
     */
    public function find(int $id): ?TokenPackage;

    /**
     * トークンパッケージを作成する
     *
     * @param array $data
     * @return TokenPackage
     */
    public function create(array $data): TokenPackage;

    /**
     * トークンパッケージを更新する
     *
     * @param TokenPackage $package
     * @param array $data
     * @return bool
     */
    public function update(TokenPackage $package, array $data): bool;

    /**
     * トークンパッケージを削除する
     *
     * @param TokenPackage $package
     * @return bool
     */
    public function delete(TokenPackage $package): bool;

    /**
     * 無料トークンの残高を取得する
     *
     * @return int
     */
    public function getFreeTokenAmount(): int;

    /**
     * 無料トークンの残高を更新する
     *
     * @param int $amount
     * @return void
     */
    public function updateFreeTokenAmount(int $amount): void;
}