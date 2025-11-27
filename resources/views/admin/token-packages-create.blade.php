<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/purchase.css', 'resources/css/admin/common.css'])
    @endpush
    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        <x-layouts.sidebar />

        <div class="flex-1 flex flex-col overflow-y-auto relative z-10">
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
                                トークンパッケージ新規作成
                            </h1>
                            <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">新しいトークン商品を追加します</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 lg:px-6 py-8">
                <div class="max-w-2xl mx-auto admin-card p-8">
                    <form method="POST" action="{{ route('admin.token-packages-store') }}">
                        @csrf

                        <div class="admin-form-group">
                            <label class="admin-form-label" for="name">商品名</label>
                            <input type="text" name="name" id="name" class="admin-form-input" required maxlength="255" value="{{ old('name') }}">
                        </div>

                        <div class="admin-form-group">
                            <label class="admin-form-label" for="description">説明</label>
                            <textarea name="description" id="description" class="admin-form-input" rows="3" maxlength="1000">{{ old('description') }}</textarea>
                        </div>

                        <div class="admin-form-group">
                            <label class="admin-form-label" for="token_amount">トークン数</label>
                            <input type="number" name="token_amount" id="token_amount" class="admin-form-input" required min="1" value="{{ old('token_amount') }}">
                        </div>

                        <div class="admin-form-group">
                            <label class="admin-form-label" for="price">価格（円）</label>
                            <input type="number" name="price" id="price" class="admin-form-input" required min="0" value="{{ old('price') }}">
                        </div>

                        <div class="admin-form-group">
                            <label class="admin-form-label" for="sort_order">表示順</label>
                            <input type="number" name="sort_order" id="sort_order" class="admin-form-input" min="0" value="{{ old('sort_order', 0) }}">
                        </div>

                        <div class="admin-form-group">
                            <label class="admin-form-label" for="is_active">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                有効
                            </label>
                        </div>

                        <div class="flex gap-4 mt-8">
                            <button type="submit" class="admin-btn admin-btn-primary px-6 py-2">
                                登録する
                            </button>
                            <a href="{{ route('admin.token-packages') }}" class="admin-btn admin-btn-secondary px-6 py-2">
                                キャンセル
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>