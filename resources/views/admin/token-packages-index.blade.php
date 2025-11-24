<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/purchase.css', 'resources/css/admin/common.css'])
    @endpush
    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div x-data="adminPage()" x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-screen token-gradient-bg relative overflow-hidden">
        {{-- 背景装飾など必要なら追加 --}}
        <x-layouts.sidebar />

        <div class="flex-1 flex flex-col overflow-y-auto">
            {{-- ヘッダー --}}
            <header class="admin-header shrink-0 shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center gap-3">
                    <button
                        type="button"
                        class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                        data-sidebar-toggle="mobile"
                        aria-label="メニューを開く">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-yellow-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-amber-600 to-yellow-600 bg-clip-text text-transparent">
                                トークンパッケージ設定
                            </h1>
                            <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">販売中のトークン商品一覧</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.token-packages-create') }}" class="admin-btn admin-btn-primary px-4 py-2 rounded-lg font-bold shadow ml-auto">
                        新規追加
                    </a>
                </div>
            </header>

            <main class="flex-1 px-6 py-8">
                <div class="max-w-5xl mx-auto space-y-8">
                    {{-- 無料トークン設定エリア --}}
                    <div class="admin-card p-6 mb-8">
                        <form method="POST" action="{{ route('admin.token-packages.free-token-update') }}" class="flex flex-col sm:flex-row items-center gap-4">
                            @csrf
                            <div class="flex-1">
                                <label for="free_token_amount" class="admin-form-label mb-2 block">ユーザに付与する無料トークン数</label>
                                <input type="number" name="free_token_amount" id="free_token_amount"
                                    class="admin-form-input w-full max-w-xs"
                                    min="0"
                                    value="{{ old('free_token_amount', $freeTokenAmount ?? config('const.token.default_free_amount', 10000)) }}"
                                    required>
                            </div>
                            <button type="submit" class="admin-btn admin-btn-primary px-6 py-2">
                                保存
                            </button>
                        </form>
                        @if(session('success'))
                            <div class="mt-3 text-green-600 text-sm font-bold">{{ session('success') }}</div>
                        @endif
                    </div>

                    {{-- トークンパッケージ一覧 --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($packages as $package)
                            <div class="token-package-card rounded-2xl shadow-lg overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="token-icon w-12 h-12 rounded-xl flex items-center justify-center">
                                            <svg class="w-6 h-6 text-amber-700 dark:text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $package->name }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($package->token_amount) }} トークン</p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $package->description }}</p>
                                    <div class="price-tag text-center py-4 rounded-xl mb-4">
                                        <div class="text-3xl font-bold text-white">¥{{ number_format($package->price) }}</div>
                                        <div class="text-sm text-white/80">税込</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.token-packages-edit', $package) }}" class="btn-purchase px-4 py-2 rounded-lg text-white font-bold shadow">
                                            編集
                                        </a>
                                        <form action="{{ route('admin.token-packages-delete', $package) }}" method="POST" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('削除しますか？', () => { event.target.submit(); }); } else { if (confirm('削除しますか？')) { event.target.submit(); } }">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-purchase px-4 py-2 rounded-lg text-white font-bold shadow bg-red-600 hover:bg-red-700">
                                                削除
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">
                        {{ $packages->links() }}
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>