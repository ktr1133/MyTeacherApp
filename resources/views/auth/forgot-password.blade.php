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
                パスワードをお忘れですか？
            </p>
        </div>

        <!-- パスワードリセットリクエストフォーム -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md auth-fade-in-delay">
            <div class="auth-card rounded-2xl px-8 py-10 shadow-xl">
                <div class="mb-6 text-sm text-gray-600 dark:text-gray-400 text-center">
                    メールアドレスを入力してください。パスワードリセット用のリンクをお送りします。
                </div>

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

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <!-- メールアドレス -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            メールアドレス
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                required 
                                autofocus
                                class="input-glow block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="メールアドレスを入力"
                            />
                        </div>
                        @error('email')
                            <p class="auth-error mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- 送信ボタン -->
                    <div>
                        <button 
                            type="submit" 
                            class="auth-button w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA0AB] hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6]"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            リセットリンクを送信
                        </button>
                    </div>

                    <!-- ログインへ戻る -->
                    <div class="text-center pt-4">
                        <a href="{{ route('login') }}" class="auth-link inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            ログインページへ戻る
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
