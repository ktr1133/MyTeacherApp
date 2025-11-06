<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username', 
        'password',
        'group_id',
        'group_edit_flg',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'group_edit_flg' => 'boolean',
    ];

    /**
     * このユーザーが所有するタスクを取得する。
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    /**
     * このユーザーが作成したAI提案を取得する。
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(TaskProposal::class);
    }

    /**
     * このユーザーが所属するグループを取得する。
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * このユーザーが割り当てたタスクを取得する。
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by_user_id');
    }

    /**
     * このユーザーが承認したタスクを取得する。
     */
    public function approvedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'approved_by_user_id');
    }

    /**
     * このユーザーがグループマスターかどうかを確認する。
     */
    public function isGroupMaster(): bool
    {
        if (!$this->group) {
            return false;
        }
        return $this->group->master_user_id === $this->id;
    }

    /**
     * このユーザーがグループ編集権限を持つかどうかを確認する。
     */
    public function canEditGroup(): bool
    {
        return $this->group_edit_flg || $this->isGroupMaster();
    }
}