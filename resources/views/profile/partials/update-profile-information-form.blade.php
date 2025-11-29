<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('プロフィール情報') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("プロフィール情報を更新します。") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Username Field -->
        <div>
            <x-input-label for="username" :value="__('ユーザー名')" />
            <div class="relative">
                <x-text-input 
                    id="username" 
                    name="username" 
                    type="text" 
                    class="mt-1 block w-full pr-10" 
                    :value="old('username', $user->username)" 
                    data-user-id="{{ $user->id }}"
                    required 
                    autofocus 
                    autocomplete="username" 
                />
                <div id="username-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                    <div class="validation-spinner"></div>
                </div>
            </div>
            <div id="username-error" class="validation-message validation-error hidden mt-2">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span></span>
            </div>
            <div id="username-success" class="validation-message validation-success hidden mt-2">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <span></span>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <!-- Email Field -->
        <div>
            <x-input-label for="email" :value="__('メールアドレス')" />
            <div class="relative">
                <x-text-input 
                    id="email" 
                    name="email" 
                    type="email" 
                    class="mt-1 block w-full pr-10" 
                    :value="old('email', $user->email)" 
                    data-user-id="{{ $user->id }}"
                    required 
                    autocomplete="email" 
                />
                <div id="email-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                    <div class="validation-spinner"></div>
                </div>
            </div>
            <div id="email-error" class="validation-message validation-error hidden mt-2">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span></span>
            </div>
            <div id="email-success" class="validation-message validation-success hidden mt-2">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <span></span>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <!-- Name Field (Optional) -->
        <div>
            <x-input-label for="name" :value="__('表示名（任意）')" />
            <x-text-input 
                id="name" 
                name="name" 
                type="text" 
                class="mt-1 block w-full" 
                :value="old('name', $user->name)" 
                autocomplete="name" 
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('表示名を設定しない場合、ユーザー名が使用されます。') }}
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button id="profile-update-btn">{{ __('保存') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p id="profile-success-message" class="text-sm text-gray-600 dark:text-gray-400">{{ __('保存しました') }}</p>
            @endif
        </div>
    </form>
</section>
