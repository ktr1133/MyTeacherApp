<x-guest-layout>
    <div class="min-h-screen auth-gradient-bg flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- 背景装飾 -->
        <div class="absolute inset-0 -z-10 overflow-hidden">
            <div class="floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="floating-decoration absolute top-1/2 left-1/3 w-64 h-64 bg-pink-500/5 rounded-full blur-3xl"></div>
        </div>

        <!-- ロゴとタイトル -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md auth-fade-in">
            <div class="flex justify-center mb-6">
                <div class="auth-logo relative">
                    <svg class="w-16 h-16 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-purple-600 rounded-full animate-ping"></div>
                </div>
            </div>
            <h2 class="text-center text-3xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
                MyTeacher
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                アカウントにログイン
            </p>
        </div>

        <!-- ログインフォーム -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md auth-fade-in-delay">
            <div class="auth-card rounded-2xl px-8 py-10 shadow-xl">
                <!-- Session Status -->
                @if (session('status'))
                    <div class="auth-status mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-600 dark:text-green-400 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ session('status') }}
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- ユーザー名またはメールアドレス -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ユーザー名またはメールアドレス
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input 
                                id="username" 
                                type="text" 
                                name="username" 
                                value="{{ old('username') }}"
                                required 
                                autofocus 
                                autocomplete="username"
                                class="input-glow block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="ユーザー名またはメールアドレスを入力"
                            />
                        </div>
                        @error('username')
                            <p class="auth-error mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- パスワード -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            パスワード
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                class="input-glow block w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="パスワードを入力"
                            />
                            <button
                                type="button"
                                id="toggle-password"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                            >
                                <svg class="h-5 w-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg class="h-5 w-5 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="auth-error mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <script>
                        function togglePasswordVisibility() {
                            const passwordInput = document.getElementById('password');
                            const showIcon = document.getElementById('password-show-icon');
                            const hideIcon = document.getElementById('password-hide-icon');
                            
                            if (passwordInput.type === 'password') {
                                passwordInput.type = 'text';
                                showIcon.classList.add('hidden');
                                hideIcon.classList.remove('hidden');
                            } else {
                                passwordInput.type = 'password';
                                showIcon.classList.remove('hidden');
                                hideIcon.classList.add('hidden');
                            }
                        }
                    </script>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center group cursor-pointer">
                            <input 
                                id="remember_me" 
                                type="checkbox" 
                                name="remember"
                                class="auth-checkbox rounded border-gray-300 dark:border-gray-600 text-[#59B9C6] shadow-sm focus:ring-[#59B9C6] dark:bg-gray-700"
                            />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition">
                                ログイン状態を保持
                            </span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="auth-link text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6]">
                                パスワードを忘れた?
                            </a>
                        @endif
                    </div>

                    <!-- ログインボタン -->
                    <div>
                        <button 
                            type="submit" 
                            class="auth-button w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA0AB] hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6]"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            ログイン
                        </button>
                    </div>

                    <!-- 区切り線 -->
                    <div class="relative my-6">
                        <div class="auth-divider"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="px-4 bg-white dark:bg-gray-800 text-sm text-gray-500 dark:text-gray-400">
                                または
                            </span>
                        </div>
                    </div>

                    <!-- 新規登録リンク -->
                    @if (Route::has('register'))
                        <div class="text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                アカウントをお持ちでないですか？
                                <a href="{{ route('register') }}" class="auth-link font-semibold text-[#59B9C6] hover:text-purple-600 dark:text-[#59B9C6] dark:hover:text-purple-400 ml-1">
                                    新規登録
                                </a>
                            </p>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Welcomeページへ戻る -->
            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" class="auth-link inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6]">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    トップページへ戻る
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>

@vite(['resources/js/auth/login.js'])