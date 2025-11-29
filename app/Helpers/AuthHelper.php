<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Cognito認証ヘルパー
 * 
 * Cognito JWTトークンからLaravelのUserモデルへのマッピングを提供
 */
class AuthHelper
{
    /**
     * Cognitoユーザー情報からLaravelユーザーを取得または作成
     * 
     * @param string $cognitoSub Cognito User Sub (UUID)
     * @param string $email ユーザーのメールアドレス
     * @param string|null $username ユーザー名（オプション）
     * @return User Laravelユーザーモデル
     */
    public static function getOrCreateCognitoUser(string $cognitoSub, string $email, ?string $username = null): User
    {
        // cognito_subで既存ユーザーを検索
        $user = User::where('cognito_sub', $cognitoSub)->first();

        if ($user) {
            Log::info('Cognito認証: 既存ユーザーを取得', [
                'cognito_sub' => $cognitoSub,
                'user_id' => $user->id,
                'email' => $email,
            ]);
            return $user;
        }

        // 新規ユーザーを作成
        $finalUsername = $username ?? self::generateUsernameFromEmail($email);
        
        // usernameが指定されている場合も重複チェックを実行
        if ($username) {
            $baseUsername = $username;
            $counter = 1;
            
            while (User::where('username', $finalUsername)->exists()) {
                $finalUsername = $baseUsername . $counter;
                $counter++;
            }
        }
        
        $user = User::create([
            'cognito_sub' => $cognitoSub,
            'email' => $email,
            'name' => $finalUsername, // 表示名としてusernameを使用
            'username' => $finalUsername,
            'auth_provider' => 'cognito',
            'password' => '', // Cognito認証ではパスワード不要
        ]);

        Log::info('Cognito認証: 新規ユーザーを作成', [
            'cognito_sub' => $cognitoSub,
            'user_id' => $user->id,
            'email' => $email,
            'username' => $user->username,
        ]);

        return $user;
    }

    /**
     * リクエストからCognito認証情報を取得
     * 
     * @param Request $request HTTPリクエスト
     * @return array{cognito_sub: string|null, email: string|null, username: string|null}
     */
    public static function getCognitoInfo(Request $request): array
    {
        return [
            'cognito_sub' => $request->attributes->get('cognito_sub'),
            'email' => $request->attributes->get('email'),
            'username' => $request->attributes->get('username'),
        ];
    }

    /**
     * ユーザーの認証プロバイダーを取得
     * 
     * @param User|null $user ユーザーモデル
     * @return string 'breeze', 'cognito', または 'unknown'
     */
    public static function getAuthProvider(?User $user): string
    {
        if (!$user) {
            return 'unknown';
        }

        return $user->auth_provider ?? 'breeze';
    }

    /**
     * メールアドレスからユーザー名を生成
     * 
     * @param string $email メールアドレス
     * @return string ユーザー名（@より前の部分）
     */
    private static function generateUsernameFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        
        // 重複チェック（同じusernameが存在する場合は連番を付与）
        $baseUsername = $username;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
}
