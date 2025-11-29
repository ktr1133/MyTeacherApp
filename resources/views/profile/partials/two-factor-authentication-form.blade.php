<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('二要素認証') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('認証アプリを使用してアカウントのセキュリティを強化します。') }}
        </p>
    </header>

    @if (session('status') == 'two-factor-authentication-enabled')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('二要素認証が有効になりました。') }}
        </div>
    @endif

    @if (session('status') == 'two-factor-authentication-disabled')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('二要素認証が無効になりました。') }}
        </div>
    @endif

    <div class="mt-6 space-y-6">
        @if (! auth()->user()->two_factor_secret)
            {{-- 二要素認証が無効な状態 --}}
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('二要素認証は無効です') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Google Authenticator などの認証アプリを使用してアカウントを保護します。') }}
                    </p>
                </div>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    <x-primary-button type="submit">
                        {{ __('有効化') }}
                    </x-primary-button>
                </form>
            </div>
        @else
            {{-- 二要素認証が有効な状態 --}}
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-green-900 dark:text-green-100">
                            {{ __('二要素認証は有効です') }}
                        </h3>
                        <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                            {{ __('アカウントは二要素認証で保護されています。') }}
                        </p>
                    </div>
                </div>

                @if (session('status') == 'two-factor-authentication-enabled' || ! auth()->user()->two_factor_confirmed_at)
                    {{-- QRコード表示 --}}
                    <div class="mt-4 pt-4 border-t border-green-200 dark:border-green-800">
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            {{ __('認証アプリでQRコードをスキャン') }}
                        </h4>
                        <div class="bg-white p-4 rounded-lg inline-block">
                            {!! auth()->user()->twoFactorQrCodeSvg() !!}
                        </div>

                        <div class="mt-4">
                            <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ __('または、セットアップキーを手動入力:') }}
                            </h5>
                            <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded font-mono text-sm break-all">
                                {{ decrypt(auth()->user()->two_factor_secret) }}
                            </div>
                        </div>

                        {{-- リカバリーコード表示 --}}
                        @if (auth()->user()->two_factor_recovery_codes)
                            <div class="mt-6">
                                <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ __('リカバリーコード') }}
                                </h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    {{ __('これらのコードを安全な場所に保管してください。認証アプリにアクセスできない場合に使用できます。') }}
                                </p>
                                <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded">
                                    <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                                        @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                                            <div class="text-gray-900 dark:text-gray-100">{{ $code }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}" class="mt-3">
                                    @csrf
                                    <x-secondary-button type="submit">
                                        {{ __('リカバリーコードを再生成') }}
                                    </x-secondary-button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- 無効化ボタン --}}
                <form method="POST" action="{{ url('/user/two-factor-authentication') }}" class="mt-6">
                    @csrf
                    @method('DELETE')

                    <x-danger-button 
                        type="submit"
                        onclick="return confirm('{{ __('二要素認証を無効にしてもよろしいですか？') }}')">
                        {{ __('無効化') }}
                    </x-danger-button>
                </form>
            </div>
        @endif

        {{-- 説明セクション --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <p class="font-semibold mb-2">{{ __('推奨される認証アプリ') }}</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Google Authenticator (iOS / Android)</li>
                        <li>Microsoft Authenticator (iOS / Android)</li>
                        <li>Authy (iOS / Android / Desktop)</li>
                        <li>1Password (iOS / Android / Desktop)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
