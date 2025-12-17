<?php

namespace App\Http\Actions\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responders\Auth\RegisterResponder;
use App\Services\Profile\ProfileManagementServiceInterface;
use App\Services\Profile\GroupServiceInterface;
use App\Notifications\ParentConsentRequestNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ユーザー登録Action
 */
class RegisterAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private ProfileManagementServiceInterface $profileService,
        private GroupServiceInterface $groupService,
        private RegisterResponder $responder
    ) {}

    /**
     * 登録画面を表示
     *
     * @return View
     */
    public function create(): View
    {
        return $this->responder->create();
    }

    /**
     * ユーザー登録処理
     *
     * @param RegisterRequest $request
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // TODO: 登録一時停止中は404を返す
        // abort(404);
        try {
            // Phase 5-2拡張: 保護者招待トークン経由の登録チェック
            $parentInviteToken = $request->query('parent_invite');
            $childUser = null;

            if ($parentInviteToken) {
                // 招待トークンでユーザーを検索
                $childUser = \App\Models\User::where('parent_invitation_token', $parentInviteToken)
                    ->where('is_minor', true)
                    ->first();

                // トークン検証
                if (!$childUser || $childUser->isParentInvitationExpired()) {
                    return $this->responder->errorRedirect('招待リンクが無効または期限切れです。お子様の登録から30日以内に保護者アカウントを作成してください。');
                }
            }

            // Phase 5-2: 年齢判定（生年月日が入力されている場合）
            $birthdate = $request->input('birthdate');
            $isMinor = false;
            $parentEmail = $request->input('parent_email');

            if ($birthdate) {
                $age = \Carbon\Carbon::parse($birthdate)->age;
                $isMinor = $age < 13;
            }

            // ユーザーデータ準備
            $userData = [
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'name' => $request->input('username'), // 表示名として使用
                'password' => Hash::make($request->input('password')),
                'timezone' => $request->input('timezone', 'Asia/Tokyo'),
                'birthdate' => $birthdate,
                'is_minor' => $isMinor,
            ];

            // Phase 5-2: 13歳未満の場合は仮登録（保護者同意待ち）
            if ($isMinor && $parentEmail) {
                // 保護者同意トークン生成
                $consentToken = Str::random(64);
                $consentExpiresAt = now()->addDays(7);
                
                // 保護者招待トークン生成（30日間有効）
                $invitationToken = Str::random(64);
                $invitationExpiresAt = now()->addDays(30);

                $userData = array_merge($userData, [
                    'parent_email' => $parentEmail,
                    'parent_consent_token' => $consentToken,
                    'parent_consent_expires_at' => $consentExpiresAt,
                    'parent_invitation_token' => $invitationToken,
                    'parent_invitation_expires_at' => $invitationExpiresAt,
                    // 同意記録は保護者同意後に設定（仮登録段階では未設定）
                    'privacy_policy_version' => null,
                    'terms_version' => null,
                    'privacy_policy_agreed_at' => null,
                    'terms_agreed_at' => null,
                ]);

                // 仮登録ユーザー作成
                $user = $this->profileService->createUser($userData);

                // 保護者に同意依頼メール送信（エラーハンドリング付き）
                try {
                    Notification::route('mail', $parentEmail)
                        ->notify(new ParentConsentRequestNotification($user, $consentToken));
                    
                    Log::info('Minor user registered (pending parent consent)', [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'age' => $age ?? null,
                        'parent_email' => $parentEmail,
                        'consent_expires_at' => $consentExpiresAt,
                        'invitation_expires_at' => $invitationExpiresAt,
                    ]);

                    // 保護者同意待ち画面にリダイレクト（ログインせず）
                    return redirect()->route('login')
                        ->with('status', 'アカウント作成のご依頼を受け付けました。保護者の方のメールアドレス（' . $parentEmail . '）に同意依頼メールを送信しました。保護者の方の同意後、ログインできるようになります。');
                        
                } catch (\Exception $e) {
                    // メール送信失敗時のエラーログ
                    Log::error('Failed to send parent consent email', [
                        'user_id' => $user->id,
                        'parent_email' => $parentEmail,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // ユーザーには登録完了を通知（メール送信失敗は内部で処理）
                    return redirect()->route('login')
                        ->with('status', 'アカウント作成のご依頼を受け付けました。保護者の方のメールアドレスに同意依頼メールを送信しています。メールが届かない場合は、管理者にお問い合わせください。');
                }
            }

            // 13歳以上または生年月日未入力の場合は通常登録
            $userData = array_merge($userData, [
                // 同意記録（法的要件）
                'privacy_policy_version' => config('legal.current_versions.privacy_policy'),
                'terms_version' => config('legal.current_versions.terms_of_service'),
                'privacy_policy_agreed_at' => now(),
                'terms_agreed_at' => now(),
            ]);

            $user = $this->profileService->createUser($userData);

            // Phase 5-2拡張: 招待トークン経由の場合は親子紐付け + グループ作成
            if ($childUser) {
                // 子アカウントの既存グループチェック
                if ($childUser->group_id !== null) {
                    return $this->responder->errorRedirect('お子様は既に別のグループに所属しています。');
                }

                // 家族グループ作成（ランダム8文字名、保護者をマスター、親子紐付け）
                try {
                    $group = $this->groupService->createFamilyGroup($user, $childUser);

                    Log::info('Parent account linked to child account via invitation with group creation', [
                        'parent_user_id' => $user->id,
                        'child_user_id' => $childUser->id,
                        'child_username' => $childUser->username,
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                    ]);
                } catch (\RuntimeException $e) {
                    Log::error('Failed to create family group during registration', [
                        'parent_user_id' => $user->id,
                        'child_user_id' => $childUser->id,
                        'error' => $e->getMessage(),
                    ]);

                    return $this->responder->errorRedirect('グループの作成に失敗しました。もう一度お試しください。');
                }
            }

            // 登録イベント発火
            event(new Registered($user));

            // ログイン
            Auth::login($user);

            Log::info('New user registered', [
                'user_id' => $user->id,
                'username' => $user->username,
                'timezone' => $user->timezone,
                'age' => $birthdate ? \Carbon\Carbon::parse($birthdate)->age : null,
                'is_minor' => $isMinor,
                'privacy_policy_version' => $user->privacy_policy_version,
                'terms_version' => $user->terms_version,
                'linked_child' => $childUser ? $childUser->id : null,
            ]);

            return $this->responder->successRedirect();

        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->errorRedirect('アカウントの作成に失敗しました。もう一度お試しください。');
        }
    }
}
