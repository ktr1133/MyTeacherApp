<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/profile/timezone.css'])
    @endpush

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        @php
            $isChildTheme = auth()->user()->isChild();
        @endphp
        @if(!$isChildTheme)
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
                <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            data-sidebar-toggle="mobile"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                @if (!$isChildTheme)
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        タイムゾーン設定
                                    </h1>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">日時表示の地域設定</p>
                                @else
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        タイムゾーン設定
                                    </h1>
                                @endif
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button 
                            type="submit"
                            id="logout-btn"
                            class="inline-flex items-center justify-center shrink-0 rounded-lg border border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/80 dark:hover:bg-gray-800/80 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition px-3 py-2 text-sm font-medium backdrop-blur-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden sm:inline">ログアウト</span>
                        </button>
                    </form>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-12 space-y-6">
                    
                    {{-- ステータスメッセージ --}}
                    @if (session('success'))
                        <div 
                            class="success-message bento-card rounded-xl p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 task-card-enter"
                            role="alert">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-800 dark:text-green-200">保存しました</p>
                                    <p class="text-sm text-green-600 dark:text-green-300">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 現在の設定 --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden timezone-card task-card-enter">
                        <div class="px-6 py-4 border-b border-teal-500/20 dark:border-teal-500/30 bg-gradient-to-r from-teal-500/5 to-cyan-50/50 dark:from-teal-500/10 dark:to-cyan-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-600 to-cyan-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">
                                    現在の設定
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">タイムゾーン</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $currentTimezone }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">現在時刻</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white timezone-current-time" id="current-time">
                                            {{ now()->timezone(auth()->user()->timezone ?? 'Asia/Tokyo')->format('Y-m-d H:i:s') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- タイムゾーン選択フォーム --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden timezone-card task-card-enter" style="animation-delay: 0.1s;">
                        <div class="px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-transparent">
                                    タイムゾーン変更
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="{{ route('profile.timezone.update') }}" class="space-y-6">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        地域を選択
                                    </label>
                                    
                                    <select 
                                        id="timezone" 
                                        name="timezone" 
                                        class="timezone-select block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 transition py-3 px-4"
                                        required
                                    >
                                        @foreach($timezonesGrouped as $region => $timezones)
                                            <optgroup label="{{ $region }}">
                                                @foreach($timezones as $identifier => $name)
                                                    <option 
                                                        value="{{ $identifier }}" 
                                                        @if(auth()->user()->timezone === $identifier) selected @endif
                                                    >
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>

                                    @error('timezone')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="timezone-info-box bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-l-4 border-blue-500 dark:border-blue-400 p-4 rounded-r-xl">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">タイムゾーン変更の影響</h4>
                                            <p class="text-sm text-blue-700 dark:text-blue-200">
                                                タイムゾーンを変更すると、すべての日時表示が選択したタイムゾーンに自動変換されます。タスクの期限、作成日時、通知時刻などが正しく表示されます。
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <a href="{{ route('profile.edit') }}" 
                                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition font-medium text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                        </svg>
                                        キャンセル
                                    </a>
                                    <button 
                                        type="submit" 
                                        class="timezone-save-btn w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition font-semibold text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        保存する
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
    <script>
        // リアルタイムで現在時刻を更新
        setInterval(() => {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = now.toLocaleString('ja-JP', options);
            }
        }, 1000);

        // 成功メッセージの自動非表示（3秒後にフェードアウト）
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.3s ease-out';
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 300);
            }, 3000);
        }
    </script>
    @endpush
</x-app-layout>
