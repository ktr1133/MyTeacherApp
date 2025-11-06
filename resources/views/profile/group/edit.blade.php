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
                            {{ __('グループ管理') }}
                        </h2>
                    </div>

                    <!-- ログアウトボタン -->
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button 
                            type="submit"
                            class="inline-flex items-center justify-center shrink-0 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition px-3 py-2 text-sm font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            {{ __('ログアウト') }}
                        </button>
                    </form>
                </div>
            </header>

            {{-- C. メインコンテンツ --}}
            <main class="flex-1">
                <div class="py-12">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                        
                        {{-- ステータスメッセージの表示 --}}
                        @if (session('status'))
                            <div 
                                x-data="{ show: true }" 
                                x-show="show" 
                                x-transition 
                                x-init="setTimeout(() => show = false, 3000)"
                                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                                role="alert">
                                <strong class="font-bold">{{ __('Success!') }}</strong>
                                <span class="block sm:inline">
                                    @if (session('status') === 'group-updated')
                                        {{ __('グループ情報を更新しました。') }}
                                    @elseif (session('status') === 'member-added')
                                        {{ __('メンバーを追加しました。') }}
                                    @elseif (session('status') === 'permission-updated')
                                        {{ __('権限を更新しました。') }}
                                    @elseif (session('status') === 'master-transferred')
                                        {{ __('グループマスターを譲渡しました。') }}
                                    @elseif (session('status') === 'member-removed')
                                        {{ __('メンバーを削除しました。') }}
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- 1. グループ基本情報 --}}
                        @include('profile.group.partials.update-group-information')

                        {{-- 2. メンバー一覧 --}}
                        @if ($group)
                            @include('profile.group.partials.member-list')
                        @endif

                        {{-- 3. メンバー追加 --}}
                        @if ($group && Auth::user()->canEditGroup())
                            @include('profile.group.partials.add-member')
                        @endif

                        {{-- 戻るボタン --}}
                        <div class="flex justify-start">
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('アカウント管理に戻る') }}
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>