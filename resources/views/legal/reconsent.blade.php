<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('プライバシーポリシー・利用規約への再同意') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- 通知メッセージ -->
                    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    プライバシーポリシーまたは利用規約が更新されました。<br>
                                    最新版への同意が必要です。以下の内容をご確認の上、同意してください。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- バージョン情報 -->
                    <div class="mb-6 space-y-2">
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">プライバシーポリシー</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    現在: {{ auth()->user()->privacy_policy_version ?? '未同意' }}
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">→</span>
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                    最新: {{ config('legal.current_versions.privacy_policy') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">利用規約</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    現在: {{ auth()->user()->terms_version ?? '未同意' }}
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">→</span>
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                    最新: {{ config('legal.current_versions.terms_of_service') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 同意フォーム -->
                    <form method="POST" action="{{ route('legal.reconsent.submit') }}" id="reconsentForm">
                        @csrf

                        <!-- 同意チェックボックス -->
                        <div class="mb-6 space-y-4 border-t border-b border-gray-200 dark:border-gray-700 py-6">
                            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                ✅ 同意が必要な項目
                            </h3>

                            <!-- プライバシーポリシー -->
                            <div class="flex items-start space-x-3">
                                <input 
                                    type="checkbox" 
                                    name="privacy_policy_consent" 
                                    id="privacy_policy_consent"
                                    value="1"
                                    required
                                    class="mt-1 w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700"
                                >
                                <label for="privacy_policy_consent" class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                    <a href="{{ route('privacy-policy') }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        プライバシーポリシー（v{{ config('legal.current_versions.privacy_policy') }}）
                                    </a>
                                    に同意します
                                    <span class="text-red-500">*</span>
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
                                    class="mt-1 w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-gray-700"
                                >
                                <label for="terms_consent" class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                    <a href="{{ route('terms-of-service') }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                        利用規約（v{{ config('legal.current_versions.terms_of_service') }}）
                                    </a>
                                    に同意します
                                    <span class="text-red-500">*</span>
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
                                class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled
                            >
                                同意して続ける
                            </button>
                        </div>
                    </form>

                    <!-- 注意事項 -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-medium mb-2">⚠️ ご注意</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>同意いただけない場合、サービスの継続利用ができません。</li>
                            <li>プライバシーポリシー・利用規約は別タブで開いて内容をご確認ください。</li>
                            <li>同意後はダッシュボードに戻り、通常通りサービスをご利用いただけます。</li>
                        </ul>
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
