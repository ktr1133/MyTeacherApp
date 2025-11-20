<?php

namespace App\Services\Token;

use App\Repositories\Token\TokenPackageRepositoryInterface;
use App\Models\TokenPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * トークンパッケージサービス
 */
class TokenPackageService implements TokenPackageServiceInterface
{
    /**
     * コンストラクタ
     *
     * @param TokenPackageRepositoryInterface $repo
     * 
     * @param TokenServiceInterface $tokenService
     */
    public function __construct(
        private TokenPackageRepositoryInterface $repo,
        private TokenServiceInterface $tokenService // 依存注入
    ) {}

    /**
     * {@inheritdoc}
     */
    public function list(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repo->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?TokenPackage
    {
        return $this->repo->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): TokenPackage
    {
        return $this->repo->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(TokenPackage $package, array $data): bool
    {
        return $this->repo->update($package, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(TokenPackage $package): bool
    {
        return $this->repo->delete($package);
    }

    /**
     * {@inheritdoc}
     */
    public function getFreeTokenAmount(): int
    {
        return $this->repo->getFreeTokenAmount();
    }

    /**
     * {@inheritdoc}
     */
    public function updateFreeTokenAmount(int $amount): void
    {
        $this->repo->updateFreeTokenAmount($amount);
    }
}