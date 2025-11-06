<x-app-layout>
    {{-- メイン背景色: #F3F3F2 を適用 --}}
    <div x-data="{ showSidebar: false }" class="flex min-h-[100dvh] bg-[#F3F3F2]">

        {{-- A. サイドバー (デスクトップ版とモバイル版) --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- B. ヘッダー --}}
            <header class="sticky top-0 z-20 border-b bg-white shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <!-- ハンバーガー（モバイルのみ表示） -->
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-md border border-gray-200 hover:bg-gray-50 shrink-0"
                            @click="showSidebar = true"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <!-- ページタイトル -->
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('アカウント') }}
                        </h2>
                    </div>

                    <!-- ログアウトボタン -->
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button 
                            type="submit"
                            class="inline-flex items-center justify-center shrink-0 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition
                                   px-3 py-2 text-sm font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('ログアウト') }}</span>
                            <span class="sm:hidden">{{ __('ログアウト') }}</span>
                        </button>
                    </form>
                </div>
            </header>

            {{-- C. メインコンテンツ --}}
            <main class="flex-1">
                <div class="py-12">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                        
                        {{-- ステータスメッセージの表示 --}}
                        @if (session('status') === 'profile-updated')
                            <div 
                                x-data="{ show: true }" 
                                x-show="show" 
                                x-transition 
                                x-init="setTimeout(() => show = false, 2000)"
                                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                                role="alert">
                                <strong class="font-bold">{{ __('Success!') }}</strong>
                                <span class="block sm:inline">{{ __('Profile information updated successfully.') }}</span>
                            </div>
                        @endif

                        {{-- 1. ユーザー情報更新フォーム (ユーザー名のみ) --}}
                        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                            <div class="max-w-xl">
                                {{-- ユーザー名更新コンポーネントをインクルード --}}
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>

                        {{-- グループ管理へのリンク --}}
                        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                            <div class="max-w-xl">
                                <header>
                                    <h2 class="text-lg font-medium text-gray-900">{{ __('グループ管理') }}</h2>
                                    <p class="mt-1 text-sm text-gray-600">{{ __('グループを作成・編集できます。') }}</p>
                                </header>
                                <div class="mt-6">
                                    <a href="{{ route('group.edit') }}" class="inline-flex items-center px-4 py-2 bg-[#59B9C6] rounded-md text-xs font-semibold text-white hover:bg-[#4AA0AB]">
                                        {{ __('グループ管理画面へ') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- 2. パスワード更新フォーム --}}
                        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                            <div class="max-w-xl">
                                {{-- パスワード更新コンポーネントをインクルード --}}
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>

                        {{-- 3. アカウント削除フォーム --}}
                        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                            <div class="max-w-xl">
                                {{-- ユーザー削除コンポーネントをインクルード --}}
                                @include('profile.partials.delete-user-form')
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>