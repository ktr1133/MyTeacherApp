<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'master_user_id',
    ];

    /**
     * このグループに所属するユーザーを取得する。
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * このグループのマスターユーザーを取得する。
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_user_id');
    }

    /**
     * このグループで編集権限を持つユーザーを取得する。
     */
    public function editors()
    {
        return $this->users()->where('group_edit_flg', true);
    }
}