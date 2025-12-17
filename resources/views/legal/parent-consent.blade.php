<x-guest-layout>
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
        <!-- ヘッダー -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                保護者の同意確認
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                お子様のMyTeacherアカウント作成には保護者の同意が必要です
            </p>
        </div>

        <!-- メインコンテンツ -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 space-y-6">
            <!-- お子様の情報 -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    お子様の登録情報
                </h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ユーザー名</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">{{ $user->username }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">メールアドレス</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email }}</dd>
                    </div>
                    @if($user->birthdate)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">生年月日</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->birthdate->format('Y年m月d日') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">年齢</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->birthdate->age }}歳</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">登録日時</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('Y年m月d日 H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">同意期限</dt>
                        <dd class="mt-1 text-sm font-semibold {{ $user->isParentConsentExpired() ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $user->parent_consent_expires_at->format('Y年m月d日 H:i') }}まで
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- 同意内容 -->
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    同意事項
                </h2>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">
                        13歳未満のお子様の利用について
                    </h3>
                    <p class="text-sm text-blue-800 dark:text-blue-400 leading-relaxed">
                        MyTeacherは、13歳未満のお子様がご利用になる場合、保護者の方の同意が必要です。
                        以下の内容をご確認の上、同意いただける場合は下記のボタンをクリックしてください。
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            お子様が本サービスを利用することに同意します
                        </p>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            <a href="{{ route('privacy-policy') }}" target="_blank" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 underline">プライバシーポリシー</a>の内容を確認し、同意します
                        </p>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            <a href="{{ route('terms-of-service') }}" target="_blank" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 underline">利用規約</a>の内容を確認し、同意します
                        </p>
                    </div>
                </div>
            </div>

            <!-- 注意事項 -->
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-300">
                            重要なお知らせ
                        </h3>
                        <p class="mt-2 text-sm text-amber-800 dark:text-amber-400 leading-relaxed">
                            同意期限（{{ $user->parent_consent_expires_at->format('Y年m月d日 H:i') }}）までに同意いただけない場合、
                            お子様のアカウントは自動的に削除されます。削除されたアカウントは復元できませんのでご注意ください。
                        </p>
                    </div>
                </div>
            </div>

            <!-- 同意ボタン -->
            <form method="POST" action="{{ route('legal.parent-consent.store', ['token' => $token]) }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                    上記内容に同意してアカウントを有効化する
                </button>
            </form>

            <!-- キャンセル -->
            <div class="text-center mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    同意されない場合は、このページを閉じてください。
                    <br>
                    アカウントは期限到達時に自動的に削除されます。
                </p>
            </div>
        </div>

        <!-- フッター -->
        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
            <p>このメールは {{ $user->parent_email }} 宛に送信されました</p>
            <p class="mt-1">このリンクは一度のみ有効です</p>
        </div>
    </div>
</div>
</x-guest-layout>
