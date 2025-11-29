<?php

namespace App\Http\Middleware;

use App\Services\Auth\LoginAttemptServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理者エリアIP制限ミドルウェア
 * 
 * Stripe要件対応:
 * - 管理者のアクセス可能なIPアドレスを制限
 * - Basic認証フォールバック
 */
class AdminIpRestriction
{
    public function __construct(
        protected LoginAttemptServiceInterface $loginAttemptService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // IP制限が有効な場合
        if (config('admin.ip_restriction_enabled', false)) {
            $allowedIps = config('admin.allowed_ips', []);
            $clientIp = $request->ip();

            // 許可IPリストに含まれているか確認
            if (!in_array($clientIp, $allowedIps) && !$this->isIpInRange($clientIp, $allowedIps)) {
                Log::warning('Admin access denied: IP not allowed', [
                    'ip' => $clientIp,
                    'path' => $request->path(),
                ]);

                abort(403, 'Access denied. Your IP address is not allowed.');
            }
        }

        // Basic認証が有効な場合（IP制限のフォールバック）
        if (config('admin.basic_auth_enabled', false)) {
            $username = config('admin.basic_auth_username');
            $password = config('admin.basic_auth_password');

            // Basic認証チェック
            if ($request->getUser() !== $username || $request->getPassword() !== $password) {
                return response('Unauthorized', 401, [
                    'WWW-Authenticate' => 'Basic realm="Admin Area"'
                ]);
            }
        }

        // 疑わしいIPをブロック
        if ($this->loginAttemptService->isSuspiciousIp($request->ip())) {
            Log::warning('Admin access denied: Suspicious IP', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            abort(429, 'Too many requests. Please try again later.');
        }

        return $next($request);
    }

    /**
     * IPアドレスがCIDR範囲内にあるか確認
     * 
     * @param string $ip チェック対象IP
     * @param array $ranges CIDR範囲リスト
     * @return bool
     */
    private function isIpInRange(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            // CIDR表記でない場合はスキップ
            if (!str_contains($range, '/')) {
                continue;
            }

            [$subnet, $mask] = explode('/', $range);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int)$mask);

            if (($ipLong & $maskLong) === ($subnetLong & $maskLong)) {
                return true;
            }
        }

        return false;
    }
}
