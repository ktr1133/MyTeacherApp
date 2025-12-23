<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('メンバー追加') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('グループに新しいメンバーを追加できます。') }}
        </p>

        {{-- メンバー数制限情報 --}}
        @php
            $currentMemberCount = $group->users()->count();
            $maxMembers = $group->max_members;
            $remainingSlots = max(0, $maxMembers - $currentMemberCount);
            $subscriptionActive = $group->subscription_active;
        @endphp

        <div class="mt-3 p-3 rounded-lg {{ $remainingSlots > 0 ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700' }}">
            <div class="flex items-center gap-2 text-sm">
                @if($remainingSlots > 0)
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-blue-800 dark:text-blue-200">
                        現在 <strong>{{ $currentMemberCount }}名</strong> / <strong>{{ $maxMembers }}名</strong> （残り <strong>{{ $remainingSlots }}名</strong> 追加可能）
                    </span>
                @else
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 dark:text-red-200">
                        メンバー数が上限（<strong>{{ $maxMembers }}名</strong>）に達しています
                    </span>
                @endif
            </div>

            @if(!$subscriptionActive && $remainingSlots <= 0)
                <div class="mt-2 flex items-start gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-amber-800 dark:text-amber-200 font-medium">
                            さらにメンバーを追加するには、サブスクリプションプランへの加入が必要です。
                        </p>
                        <a href="{{ route('subscriptions.index') }}" 
                           class="mt-2 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 rounded-lg shadow-md hover:shadow-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            プランを確認する
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </header>

    <form id="add-member-form" method="post" action="{{ route('group.member.add', $group) }}" class="mt-6 space-y-6">
        @csrf

        <!-- ユーザー名 -->
        <div>
            <x-input-label for="username" :value="__('ユーザー名')" />
            <div class="relative">
                <x-text-input 
                    id="username" 
                    name="username" 
                    type="text" 
                    class="mt-1 block w-full pr-10" 
                    :value="old('username')" 
                    required 
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

        <!-- メールアドレス -->
        <div>
            <x-input-label for="email" :value="__('メールアドレス')" />
            <div class="relative">
                <x-text-input 
                    id="email" 
                    name="email" 
                    type="email" 
                    class="mt-1 block w-full pr-10" 
                    :value="old('email')" 
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

        <!-- 表示名（任意） -->
        <div>
            <x-input-label for="name" :value="__('表示名（任意）')" />
            <x-text-input 
                id="name" 
                name="name" 
                type="text" 
                class="mt-1 block w-full" 
                :value="old('name')" 
                autocomplete="name"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('表示名を設定しない場合、ユーザー名が使用されます。') }}
            </p>
        </div>

        <!-- パスワード -->
        <div>
            <x-input-label for="password" :value="__('パスワード')" />
            <div class="relative">
                <x-text-input 
                    id="password" 
                    name="password" 
                    type="password" 
                    class="mt-1 block w-full pr-10" 
                    required 
                    autocomplete="new-password"
                />
                <div id="password-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                    <div class="validation-spinner"></div>
                </div>
            </div>
            
            {{-- パスワード強度メーター --}}
            <div id="password-strength-meter" class="mt-2">
                <div class="strength-bar-container h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="strength-bar h-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div class="flex items-center justify-between mt-1">
                    <div class="strength-text text-xs text-gray-500 dark:text-gray-400"></div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">
                        パスワード強度
                    </div>
                </div>
                <div class="strength-errors mt-1 text-xs text-red-600 dark:text-red-400" style="display: none;"></div>
            </div>
            
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                ※ 8文字以上、英字（大文字・小文字）、数字、記号を含める必要があります
            </p>
            
            <div id="password-error" class="validation-message validation-error hidden mt-2">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span></span>
            </div>
            <div id="password-success" class="validation-message validation-success hidden mt-2">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                </svg>
                <span></span>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        {{-- 保護者による同意（代理同意） --}}
        <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-3">
                {{ __('保護者による同意（代理同意）') }}
            </h3>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-4">
                {{ __('お子様のアカウントを作成する場合、保護者としてプライバシーポリシーおよび利用規約に同意する必要があります。') }}
            </p>

            {{-- プライバシーポリシーへの同意 --}}
            <div class="mb-4">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input 
                        type="checkbox" 
                        id="privacy_policy_consent" 
                        name="privacy_policy_consent" 
                        value="1" 
                        class="mt-0.5 w-4 h-4 text-indigo-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:ring-2"
                        {{ old('privacy_policy_consent') ? 'checked' : '' }}
                        required
                    >
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                        <a href="{{ route('privacy-policy') }}" 
                           target="_blank" 
                           class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 underline font-medium">
                            {{ __('プライバシーポリシー') }}
                        </a>
                        {{ __('に保護者として同意します') }}
                        <span class="text-red-600 dark:text-red-400 ml-1">*</span>
                    </span>
                </label>
                <x-input-error class="mt-2" :messages="$errors->get('privacy_policy_consent')" />
            </div>

            {{-- 利用規約への同意 --}}
            <div>
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input 
                        type="checkbox" 
                        id="terms_consent" 
                        name="terms_consent" 
                        value="1" 
                        class="mt-0.5 w-4 h-4 text-indigo-600 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:ring-2"
                        {{ old('terms_consent') ? 'checked' : '' }}
                        required
                    >
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                        <a href="{{ route('terms-of-service') }}" 
                           target="_blank" 
                           class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 underline font-medium">
                            {{ __('利用規約') }}
                        </a>
                        {{ __('に保護者として同意します') }}
                        <span class="text-red-600 dark:text-red-400 ml-1">*</span>
                    </span>
                </label>
                <x-input-error class="mt-2" :messages="$errors->get('terms_consent')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button 
                id="add-member-button"
                :disabled="$remainingSlots <= 0"
                class="{{ $remainingSlots <= 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
                {{ __('メンバーを追加') }}
            </x-primary-button>

            @if (session('status') === 'member-added')
                <p id="member-added-message" class="text-sm text-gray-600 dark:text-gray-400">{{ __('追加しました。') }}</p>
            @endif
        </div>
    </form>
</section>