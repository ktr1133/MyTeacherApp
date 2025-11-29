<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('認証アプリに表示されている6桁のコードを入力してください。コードにアクセスできない場合は、リカバリーコードを使用できます。') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ url('/two-factor-challenge') }}">
        @csrf

        {{-- 認証コード入力 --}}
        <div id="codeSection">
            <x-input-label for="code" :value="__('認証コード')" />
            <x-text-input
                id="code"
                name="code"
                type="text"
                class="mt-1 block w-full"
                inputmode="numeric"
                autofocus
                autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        {{-- リカバリーコード入力 --}}
        <div id="recoverySection" style="display: none;">
            <x-input-label for="recovery_code" :value="__('リカバリーコード')" />
            <x-text-input
                id="recovery_code"
                name="recovery_code"
                type="text"
                class="mt-1 block w-full"
                autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
        </div>

        {{-- 切り替えボタン --}}
        <div class="flex items-center justify-end mt-4">
            <button type="button"
                    id="useRecoveryBtn"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] rounded">
                {{ __('リカバリーコードを使用') }}
            </button>

            <button type="button"
                    id="useCodeBtn"
                    style="display: none;"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] rounded">
                {{ __('認証コードを使用') }}
            </button>
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('ログイン') }}
            </x-primary-button>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const codeSection = document.getElementById('codeSection');
                const recoverySection = document.getElementById('recoverySection');
                const useRecoveryBtn = document.getElementById('useRecoveryBtn');
                const useCodeBtn = document.getElementById('useCodeBtn');
                const codeInput = document.getElementById('code');
                const recoveryInput = document.getElementById('recovery_code');

                useRecoveryBtn.addEventListener('click', function() {
                    codeSection.style.display = 'none';
                    recoverySection.style.display = 'block';
                    useRecoveryBtn.style.display = 'none';
                    useCodeBtn.style.display = 'inline';
                    recoveryInput.focus();
                });

                useCodeBtn.addEventListener('click', function() {
                    recoverySection.style.display = 'none';
                    codeSection.style.display = 'block';
                    useCodeBtn.style.display = 'none';
                    useRecoveryBtn.style.display = 'inline';
                    codeInput.focus();
                });
            });
        </script>
    @endpush
</x-guest-layout>
