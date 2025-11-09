<?php

namespace App\Repositories\Token;

use App\Models\TokenPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TokenPackageEloquentRepository implements TokenPackageRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return TokenPackage::orderBy('sort_order')->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?TokenPackage
    {
        return TokenPackage::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): TokenPackage
    {
        return TokenPackage::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(TokenPackage $package, array $data): bool
    {
        return $package->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(TokenPackage $package): bool
    {
        return $package->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getFreeTokenAmount(): int
    {
        $setting = DB::table('free_token_settings')->first();
        return $setting ? (int)$setting->amount : 10000;
    }

    /**
     * {@inheritdoc}
     */
    public function updateFreeTokenAmount(int $amount): void
    {
        DB::table('free_token_settings')
            ->where('id', 1)
            ->update(['amount' => $amount, 'updated_at' => now()]);
    }
}