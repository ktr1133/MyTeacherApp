<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
    @endpush

    <div x-data="{ showSidebar: false }" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                @if (!$isChildTheme)
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        アカウント管理
                                    </h1>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">プロフィールとセキュリティ設定</p>
                                @else
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        アカウント管理
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
                    @if (session('status') === 'profile-updated')
                        <div 
                            x-data="{ show: true }" 
                            x-show="show" 
                            x-transition 
                            x-init="setTimeout(() => show = false, 3000)"
                            class="bento-card rounded-xl p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700"
                            role="alert">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-800 dark:text-green-200">保存しました</p>
                                    <p class="text-sm text-green-600 dark:text-green-300">プロフィール情報を更新しました。</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- プロフィール情報 --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter">
                        <div class="px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-purple-50/50 dark:from-blue-500/10 dark:to-purple-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-transparent">
                                    プロフィール情報
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="max-w-xl">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>
                    </div>

                    {{-- グループ管理 --}}
                    @if (!Auth::user()->isChild())
                        <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.1s;">
                            <div class="px-6 py-4 border-b border-purple-500/20 dark:border-purple-500/30 bg-gradient-to-r from-purple-500/5 to-pink-50/50 dark:from-purple-500/10 dark:to-pink-900/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-sm font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                        グループ管理
                                    </h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="max-w-xl">
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                        グループを作成・編集できます。
                                    </p>
                                    <a href="{{ route('group.edit') }}" 
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 shadow-lg hover:shadow-xl transition font-semibold text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                        グループ管理画面へ
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- タイムゾーン設定 --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.2s;">
                        <div class="px-6 py-4 border-b border-teal-500/20 dark:border-teal-500/30 bg-gradient-to-r from-teal-500/5 to-cyan-50/50 dark:from-teal-500/10 dark:to-cyan-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-600 to-cyan-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">
                                    タイムゾーン設定
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="max-w-xl">
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                    お住まいの地域に合わせてタイムゾーンを設定できます。すべての日時表示が選択したタイムゾーンに変換されます。
                                </p>
                                <a href="{{ route('profile.timezone') }}" 
                                   class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-white bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 shadow-lg hover:shadow-xl transition font-semibold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                    タイムゾーンを設定
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- パスワード更新 --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.3s;">
                        <div class="px-6 py-4 border-b border-orange-500/20 dark:border-orange-500/30 bg-gradient-to-r from-orange-500/5 to-yellow-50/50 dark:from-orange-500/10 dark:to-yellow-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-600 to-yellow-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-orange-600 to-yellow-600 bg-clip-text text-transparent">
                                    パスワード更新
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="max-w-xl">
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>
                    </div>

                    {{-- アカウント削除 --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.4s;">
                        <div class="px-6 py-4 border-b border-red-500/20 dark:border-red-500/30 bg-gradient-to-r from-red-500/5 to-pink-50/50 dark:from-red-500/10 dark:to-pink-900/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-600 to-pink-600 flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent">
                                    アカウント削除
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="max-w-xl">
                                @include('profile.partials.delete-user-form')
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush
</x-app-layout>