<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ログイン試行履歴モデル
 * 
 * 不正ログイン対策・監視用
 * 
 * @property int $id
 * @property string $email
 * @property string $ip_address
 * @property bool $successful
 * @property string|null $user_agent
 * @property string|null $error_message
 * @property \Carbon\Carbon $attempted_at
 */
class LoginAttempt extends Model
{
    /**
     * テーブル名
     */
    protected $table = 'login_attempts';

    /**
     * タイムスタンプ無効化（attempted_atのみ使用）
     */
    public $timestamps = false;

    /**
     * 複数代入可能な属性
     */
    protected $fillable = [
        'email',
        'ip_address',
        'successful',
        'user_agent',
        'error_message',
        'attempted_at',
    ];

    /**
     * キャストする属性
     */
    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];
}
