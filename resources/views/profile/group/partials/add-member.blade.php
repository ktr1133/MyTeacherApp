<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('メンバー追加') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('グループに新しいメンバーを追加できます。') }}
        </p>
    </header>

    <form id="add-member-form" method="post" action="{{ route('group.member.add', $group) }}" class="mt-6 space-y-6">
        @csrf

        <!-- ユーザー名 -->
        <div>
            <x-input-label for="username" :value="__('ユーザー名')" />
            <x-text-input 
                id="username" 
                name="username" 
                type="text" 
                class="mt-1 block w-full" 
                :value="old('username')" 
                required 
                autocomplete="username"
            />
            
            {{-- スピナー（JSで制御） --}}
            <div id="username-spinner" class="mt-2 flex items-center gap-2" style="display: none;">
                <div class="validation-spinner"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">確認中...</span>
            </div>
            
            {{-- バリデーションメッセージ（JSで制御） --}}
            <div id="username-validation" class="validation-message" style="display: none;"></div>
            
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <!-- パスワード -->
        <div>
            <x-input-label for="password" :value="__('パスワード')" />
            <x-text-input 
                id="password" 
                name="password" 
                type="password" 
                class="mt-1 block w-full" 
                required 
                autocomplete="new-password"
            />
            
            {{-- スピナー（JSで制御） --}}
            <div id="password-spinner" class="mt-2 flex items-center gap-2" style="display: none;">
                <div class="validation-spinner"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">確認中...</span>
            </div>
            
            {{-- バリデーションメッセージ（JSで制御） --}}
            <div id="password-validation" class="validation-message" style="display: none;"></div>
            
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button 
                id="add-member-button"
                disabled
                class="opacity-50 cursor-not-allowed">
                {{ __('メンバーを追加') }}
            </x-primary-button>

            @if (session('status') === 'member-added')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('追加しました。') }}</p>
            @endif
        </div>
    </form>
</section>