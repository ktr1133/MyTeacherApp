<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Breeze + Cognito 並行運用ミドルウェア（Phase 1.5）
 * 
 * セッションベース認証（Breeze）またはJWT認証（Cognito）のいずれかで
 * 認証されていれば、リクエストを通過させます。
 * 
 * 【使用期間】Phase 1.5 並行運用期間（2週間）
 * 【目的】既存ユーザー（Breeze）と新規ユーザー（Cognito）の両方に対応
 * 【移行後】Phase 2完了後にBreezeルートを削除し、Cognitoに統一
 * 
 * @see https://docs.aws.amazon.com/ja_jp/cognito/latest/developerguide/amazon-cognito-user-pools-using-tokens-verifying-a-jwt.html
 */
class DualAuthMiddleware
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
     * @param  string|null  $guard  認証ガード（デフォルト: web）
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        // 認証方式の優先順位:
        // 1. Breeze セッション認証（既存ユーザー向け）
        // 2. Cognito JWT認証（新規ユーザー・API向け）

        // 1. Breezeセッション認証チェック
        if (Auth::guard($guard)->check()) {
            $user = Auth::guard($guard)->user();
            
            // 認証成功ログ（Phase 1.5監視用）
            Log::info('DualAuth: Breeze session authenticated', [
                'user_id' => $user->id,
                'auth_provider' => $user->auth_provider ?? 'breeze',
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return $next($request);
        }

        // 2. Cognito JWT認証チェック
        $token = $request->bearerToken();
        
        if ($token) {
            try {
                // JWT検証
                $decoded = $this->verifyToken($token);
                
                // リクエストにCognitoユーザー情報を追加
                $request->merge([
                    'cognito_user' => $decoded,
                    'cognito_sub' => $decoded['sub'] ?? null,
                    'cognito_email' => $decoded['email'] ?? null,
                    'cognito_username' => $decoded['username'] ?? null,
                ]);

                // 既存のLaravelユーザーとのマッピング
                if (isset($decoded['sub'])) {
                    $user = \App\Models\User::where('cognito_sub', $decoded['sub'])->first();
                    if ($user) {
                        $request->setUserResolver(fn() => $user);
                        
                        // 認証成功ログ（Phase 1.5監視用）
                        Log::info('DualAuth: Cognito JWT authenticated', [
                            'user_id' => $user->id,
                            'cognito_sub' => $decoded['sub'],
                            'auth_provider' => $user->auth_provider ?? 'cognito',
                            'ip' => $request->ip(),
                            'url' => $request->fullUrl(),
                        ]);

                        return $next($request);
                    } else {
                        // Cognitoユーザーが見つからない場合（移行漏れの可能性）
                        Log::warning('DualAuth: Cognito user not found in database', [
                            'cognito_sub' => $decoded['sub'],
                            'cognito_email' => $decoded['email'] ?? null,
                            'ip' => $request->ip(),
                        ]);
                    }
                }

            } catch (\Exception $e) {
                // JWT検証失敗（ログ記録のみ、次の認証方式へ）
                Log::debug('DualAuth: Cognito JWT verification failed', [
                    'error' => $e->getMessage(),
                    'token_preview' => substr($token, 0, 20) . '...',
                    'ip' => $request->ip()
                ]);
            }
        }

        // 3. いずれの認証も失敗
        Log::warning('DualAuth: Authentication failed', [
            'has_session' => Auth::guard($guard)->check(),
            'has_bearer_token' => !is_null($token),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        // 認証失敗時の処理
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required. Please login with Breeze session or provide a valid Cognito JWT token.'
            ], 401);
        }

        // Web リクエストの場合はログインページへリダイレクト
        return redirect()->guest(route('login'));
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
}
