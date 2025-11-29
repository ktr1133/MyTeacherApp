<x-guest-layout>
    @push('styles')
        @vite(['resources/css/auth/register-validation.css'])
    @endpush

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
                新しいアカウントを作成
            </p>
        </div>

        <!-- 登録フォーム -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md auth-fade-in-delay">
            <div class="auth-card rounded-2xl px-8 py-10 shadow-xl">
                <form id="register-form" method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    <!-- ユーザー名 -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ユーザー名
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
                                class="input-glow block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="ユーザー名を入力"
                            />
                            <!-- スピナー -->
                            <div id="username-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                                <div class="validation-spinner"></div>
                            </div>
                        </div>

                        <!-- サーバーサイドエラー -->
                        @error('username')
                            <div class="validation-message validation-error validation-error-slide-in">
                                <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <!-- 非同期バリデーションエラー -->
                        <div id="username-error" class="validation-message validation-error hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>

                        <!-- 非同期バリデーション成功 -->
                        <div id="username-success" class="validation-message validation-success hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>
                    </div>

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
                                autocomplete="email"
                                class="input-glow block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="メールアドレスを入力"
                            />
                            <!-- スピナー -->
                            <div id="email-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                                <div class="validation-spinner"></div>
                            </div>
                        </div>

                        <!-- サーバーサイドエラー -->
                        @error('email')
                            <div class="validation-message validation-error validation-error-slide-in">
                                <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <!-- 非同期バリデーションエラー -->
                        <div id="email-error" class="validation-message validation-error hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span></span>
                            </div>
                        @enderror

                        <!-- 非同期バリデーション成功 -->
                        <div id="email-success" class="validation-message validation-success hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>
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
                                autocomplete="new-password"
                                class="input-glow block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="8文字以上のパスワード"
                            />
                            <!-- スピナー -->
                            <div id="password-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                                <div class="validation-spinner"></div>
                            </div>
                        </div>

                        <!-- サーバーサイドエラー -->
                        @error('password')
                            <div class="validation-message validation-error validation-error-slide-in">
                                <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <!-- 非同期バリデーションエラー -->
                        <div id="password-error" class="validation-message validation-error hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>

                        <!-- 非同期バリデーション成功 -->
                        <div id="password-success" class="validation-message validation-success hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>
                    </div>

                    <!-- パスワード確認 -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            パスワード確認
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input 
                                id="password_confirmation" 
                                type="password" 
                                name="password_confirmation" 
                                required 
                                autocomplete="new-password"
                                class="input-glow block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                                placeholder="パスワードを再入力"
                            />
                        </div>

                        <!-- サーバーサイドエラー -->
                        @error('password_confirmation')
                            <div class="validation-message validation-error validation-error-slide-in">
                                <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <!-- 非同期バリデーションエラー -->
                        <div id="password_confirmation-error" class="validation-message validation-error hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>

                        <!-- 非同期バリデーション成功 -->
                        <div id="password_confirmation-success" class="validation-message validation-success hidden">
                            <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span></span>
                        </div>
                    </div>

                    <!-- タイムゾーン -->
                    <div>
                        <label for="timezone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            タイムゾーン
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <select 
                                id="timezone" 
                                name="timezone"
                                class="input-glow block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent dark:bg-gray-700 dark:text-white transition duration-200"
                            >
                                @php
                                    $timezones = config('const.timezones');
                                    $oldTimezone = old('timezone', 'Asia/Tokyo');
                                    $groupedTimezones = collect($timezones)->groupBy('region');
                                @endphp
                                
                                @foreach($groupedTimezones as $region => $tzList)
                                    <optgroup label="{{ $region }}">
                                        @foreach($tzList as $tz => $info)
                                            <option value="{{ $tz }}" {{ $oldTimezone === $tz ? 'selected' : '' }}>
                                                {{ $info['name'] }} ({{ $info['offset'] }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <!-- サーバーサイドエラー -->
                        @error('timezone')
                            <div class="validation-message validation-error validation-error-slide-in">
                                <svg class="validation-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            あなたの地域のタイムゾーンを選択してください。タスクの期限などが選択したタイムゾーンで表示されます。
                        </p>
                    </div>

                    <!-- 登録ボタン -->
                    <div>
                        <button 
                            id="register-button"
                            type="submit" 
                            disabled
                            class="auth-button w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA0AB] hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition-all duration-200 opacity-50 cursor-not-allowed"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            アカウント登録
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

                    <!-- ログインリンク -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            既にアカウントをお持ちですか？
                            <a href="{{ route('login') }}" class="auth-link font-semibold text-[#59B9C6] hover:text-purple-600 dark:text-[#59B9C6] dark:hover:text-purple-400 ml-1 transition-colors duration-200">
                                ログイン
                            </a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Welcomeページへ戻る -->
            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" class="auth-link inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    トップページへ戻る
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/auth/register-validation.js'])
    @endpush
</x-guest-layout>