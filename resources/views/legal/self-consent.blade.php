<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('本人同意のお願い') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- 通知メッセージ -->
                    <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700 dark:text-green-300">
                                    <strong>おめでとうございます！ あなたは13歳になりました 🎉</strong><br>
                                    これからは、あなた自身で同意を行う必要があります。<br>
                                    プライバシーポリシーと利用規約をご確認の上、同意してください。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ユーザー情報 -->
                    @if(auth()->user()->birthdate)
                    <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-700/50 rounded">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">あなたの年齢:</span>
                            <span class="text-lg font-bold text-gray-800 dark:text-gray-200 ml-2">
                                {{ auth()->user()->birthdate->age }}歳
                            </span>
                            <span class="text-xs ml-2">({{ auth()->user()->birthdate->format('Y年m月d日') }}生まれ)</span>
                        </div>
                    </div>
                    @endif

                    <!-- 説明セクション -->
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded">
                        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">
                            📝 これまでの経緯
                        </h3>
                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                            <p>
                                ✅ これまでは、保護者の方（{{ auth()->user()->consentGiver?->username ?? '保護者' }}さん）が代わりに同意していました。
                            </p>
                            <p>
                                ✅ 13歳になったため、これからは<strong>あなた自身で同意する必要があります</strong>。
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                ※ 保護者の方の同意日: {{ auth()->user()->privacy_policy_agreed_at?->format('Y年m月d日') ?? '未記録' }}
                            </p>
                        </div>
                    </div>

                    <!-- 同意フォーム -->
                    <form method="POST" action="{{ route('legal.self-consent.submit') }}" id="selfConsentForm">
                        @csrf

                        <!-- 同意チェックボックス -->
                        <div class="mb-6 space-y-4 border-t border-b border-gray-200 dark:border-gray-700 py-6">
                            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                ✅ 本人同意が必要な項目
                            </h3>

                            <!-- プライバシーポリシー -->
                            <div class="flex items-start space-x-3">
                                <input 
                                    type="checkbox" 
                                    name="privacy_policy_consent" 
                                    id="privacy_policy_consent"
                                    value="1"
                                    required
                                    class="mt-1 w-5 h-5 text-green-600 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:bg-gray-700"
                                >
                                <label for="privacy_policy_consent" class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                    <a href="{{ route('privacy-policy') }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        プライバシーポリシー（v{{ config('legal.current_versions.privacy_policy') }}）
                                    </a>
                                    を読み、内容を理解しました
                                    <span class="text-red-500">*</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        個人情報の取り扱いについての規約です
                                    </p>
                                </label>
                            </div>
                            @error('privacy_policy_consent')
                                <p class="text-red-500 text-xs mt-1 ml-8">{{ $message }}</p>
                            @enderror

                            <!-- 利用規約 -->
                            <div class="flex items-start space-x-3">
                                <input 
                                    type="checkbox" 
                                    name="terms_consent" 
                                    id="terms_consent"
                                    value="1"
                                    required
                                    class="mt-1 w-5 h-5 text-green-600 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:bg-gray-700"
                                >
                                <label for="terms_consent" class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                    <a href="{{ route('terms-of-service') }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        利用規約（v{{ config('legal.current_versions.terms_of_service') }}）
                                    </a>
                                    を読み、内容を理解しました
                                    <span class="text-red-500">*</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        サービスの使い方とルールについての規約です
                                    </p>
                                </label>
                            </div>
                            @error('terms_consent')
                                <p class="text-red-500 text-xs mt-1 ml-8">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 送信ボタン -->
                        <div class="flex items-center justify-between mt-6">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="text-red-500">*</span> は必須項目です
                            </p>
                            <button 
                                type="submit" 
                                id="submitBtn"
                                class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled
                            >
                                本人として同意する
                            </button>
                        </div>
                    </form>

                    <!-- 注意事項 -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-medium mb-2">⚠️ ご注意</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>同意いただけない場合、サービスの継続利用ができません。</li>
                            <li>プライバシーポリシー・利用規約は別タブで開いて内容をよく読んでください。</li>
                            <li>わからない部分があれば、保護者の方に相談してください。</li>
                            <li>同意後はダッシュボードに戻り、通常通りサービスをご利用いただけます。</li>
                        </ul>
                    </div>

                    <!-- 保護者へのメッセージ -->
                    <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            <strong>👨‍👩‍👧 保護者の方へ</strong><br>
                            お子様が13歳になられましたので、本人同意が必要となりました。<br>
                            お子様と一緒に内容をご確認の上、ご本人に同意していただくようお願いいたします。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const privacyCheckbox = document.getElementById('privacy_policy_consent');
            const termsCheckbox = document.getElementById('terms_consent');
            const submitBtn = document.getElementById('submitBtn');

            function updateSubmitButton() {
                if (privacyCheckbox.checked && termsCheckbox.checked) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            privacyCheckbox.addEventListener('change', updateSubmitButton);
            termsCheckbox.addEventListener('change', updateSubmitButton);
        });
    </script>
    @endpush
</x-app-layout>
