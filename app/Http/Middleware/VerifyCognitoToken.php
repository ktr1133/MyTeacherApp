<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\AuthHelper;

/**
 * Amazon Cognito JWT トークン検証ミドルウェア
 * 
 * Cognito User Poolから発行されたJWTアクセストークンを検証し、
 * 有効なトークンの場合はユーザー情報をリクエストに追加します。
 * 
 * @see https://docs.aws.amazon.com/ja_jp/cognito/latest/developerguide/amazon-cognito-user-pools-using-tokens-verifying-a-jwt.html
 */
class VerifyCognitoToken
{
    /**
     * AWSリージョン
     */
    private string $region;

    /**
     * Cognito User Pool ID
     */
    private string $userPoolId;

    /**
     * Cognito Client ID
     */
    private string $clientId;

    /**
     * JWKS URL（JSON Web Key Set）
     */
    private string $jwksUrl;

    /**
     * Issuer URL（発行者URL）
     */
    private string $issuerUrl;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->region = config('services.cognito.region', 'ap-northeast-1');
        $this->userPoolId = config('services.cognito.user_pool_id');
        $this->clientId = config('services.cognito.client_id');
        $this->jwksUrl = "https://cognito-idp.{$this->region}.amazonaws.com/{$this->userPoolId}/.well-known/jwks.json";
        $this->issuerUrl = "https://cognito-idp.{$this->region}.amazonaws.com/{$this->userPoolId}";
    }

    /**
     * リクエストを処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Authorizationヘッダーからトークンを取得
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Bearer token is required'
            ], 401);
        }

        try {
            // JWT検証
            $decoded = $this->verifyToken($token);
            
            // リクエストにCognito属性を設定
            $request->attributes->set('cognito_sub', $decoded['sub'] ?? null);
            $request->attributes->set('email', $decoded['email'] ?? null);
            $request->attributes->set('username', $decoded['username'] ?? null);

            // CognitoユーザーをLaravelのUserモデルにマッピング
            if (isset($decoded['sub'])) {
                $user = AuthHelper::getOrCreateCognitoUser(
                    $decoded['sub'],
                    $decoded['email'] ?? '',
                    $decoded['username'] ?? null
                );
                
                // Laravelの認証システムにユーザーを注入
                // これにより $request->user() でユーザーを取得可能
                $request->setUserResolver(fn() => $user);
                
                Log::info('Cognito認証成功', [
                    'cognito_sub' => $decoded['sub'],
                    'user_id' => $user->id,
                    'email' => $decoded['email'] ?? null,
                ]);
            }

            return $next($request);

        } catch (\Exception $e) {
            Log::warning('Cognito token verification failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token'
            ], 401);
        }
    }

    /**
     * JWTトークンを検証
     *
     * @param string $token JWTトークン
     * @return array デコードされたクレーム
     * @throws \Exception 検証失敗時
     */
    private function verifyToken(string $token): array
    {
        // JWKSを取得（1時間キャッシュ）
        $jwks = $this->getJwks();

        // JWTデコード＆署名検証
        try {
            $keys = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keys);
        } catch (\Exception $e) {
            throw new \Exception("JWT decode failed: {$e->getMessage()}");
        }

        // 配列に変換
        $claims = (array) $decoded;

        // クレーム検証
        $this->validateClaims($claims);

        return $claims;
    }

    /**
     * JWKSを取得（キャッシュ付き）
     *
     * @return array JWKS（JSON Web Key Set）
     * @throws \Exception JWKS取得失敗時
     */
    private function getJwks(): array
    {
        try {
            return Cache::remember("cognito_jwks_{$this->userPoolId}", 3600, function () {
                $response = Http::timeout(5)->get($this->jwksUrl);

                if ($response->failed()) {
                    throw new \Exception("Failed to fetch JWKS: HTTP {$response->status()}");
                }

                $jwks = $response->json();

                if (!isset($jwks['keys']) || empty($jwks['keys'])) {
                    throw new \Exception('Invalid JWKS structure');
                }

                return $jwks;
            });
        } catch (\Exception $e) {
            Log::error('Failed to retrieve JWKS', [
                'error' => $e->getMessage(),
                'jwks_url' => $this->jwksUrl
            ]);
            throw new \Exception("JWKS retrieval failed: {$e->getMessage()}");
        }
    }

    /**
     * JWTクレームを検証
     *
     * @param array $claims JWTクレーム
     * @throws \Exception 検証失敗時
     */
    private function validateClaims(array $claims): void
    {
        // token_use検証（accessトークンのみ許可）
        if (($claims['token_use'] ?? '') !== 'access') {
            throw new \Exception('Invalid token_use. Expected "access" token.');
        }

        // iss（発行者）検証
        if (($claims['iss'] ?? '') !== $this->issuerUrl) {
            throw new \Exception('Invalid issuer (iss)');
        }

        // exp（有効期限）検証（JWT::decodeで自動検証済み）
        if (!isset($claims['exp'])) {
            throw new \Exception('Missing exp claim');
        }

        // client_id検証
        if (($claims['client_id'] ?? '') !== $this->clientId) {
            throw new \Exception('Invalid client_id');
        }

        // sub（ユーザー識別子）存在確認
        if (!isset($claims['sub'])) {
            throw new \Exception('Missing sub claim');
        }
    }

    /**
     * クレームから管理者フラグを取得
     *
     * @param array $claims JWTクレーム
     * @return bool 管理者フラグ
     */
    public static function isAdmin(array $claims): bool
    {
        return ($claims['custom:is_admin'] ?? 'false') === 'true';
    }
}
