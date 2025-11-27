<x-app-layout>
    @push('styles')
        @vite(['resources/css/admin/common.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl admin-floating-decoration"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl admin-floating-decoration" style="animation-delay: -10s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
            
            {{-- ヘッダー --}}
            <header class="admin-header shrink-0 shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
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
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                    FAQ登録
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">新しいFAQを登録</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline-block px-3 py-1.5 bg-gradient-to-r from-purple-500 to-indigo-600 text-white text-xs font-bold rounded-full">
                            管理者
                        </span>
                    </div>
                </div>
            </header>

            {{-- コンテンツエリア --}}
            <main class="flex-1 overflow-y-auto">
                <div class="p-4 lg:p-6">
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-card p-6">
            <form method="POST" action="{{ route('admin.portal.faqs.store') }}" class="space-y-6">
                @csrf

                <!-- アプリ名 -->
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        アプリ名 <span class="text-red-500">*</span>
                    </label>
                    <select name="app_name" id="app_name" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">選択してください</option>
                        <option value="MyTeacher" {{ old('app_name') === 'MyTeacher' ? 'selected' : '' }}>MyTeacher</option>
                        <option value="KeepItSimple" {{ old('app_name') === 'KeepItSimple' ? 'selected' : '' }}>KeepItSimple</option>
                    </select>
                </div>

                <!-- カテゴリ -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        カテゴリ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="category" id="category" value="{{ old('category') }}" required maxlength="50" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">例: 基本操作、機能について、トラブルシューティング など</p>
                </div>

                <!-- 質問 -->
                <div>
                    <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        質問 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="question" id="question" value="{{ old('question') }}" required maxlength="255" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 回答 -->
                <div>
                    <label for="answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        回答 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer" id="answer" rows="8" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('answer') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">HTMLタグが使用できます。</p>
                </div>

                <!-- 表示順 -->
                <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        表示順 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 0) }}" required min="0" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">数値が小さいほど上位に表示されます。</p>
                </div>

                <!-- 公開状態 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        公開状態 <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="radio" name="is_published" value="1" {{ old('is_published', '0') === '1' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">公開</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_published" value="0" {{ old('is_published', '0') === '0' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">非公開</span>
                        </label>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex items-center space-x-4">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition">
                        登録
                    </button>
                    <a href="{{ route('admin.portal.faqs.index') }}" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md transition">
                        キャンセル
                    </a>
                </div>
            </form>
        </div>

                </div>
            </main>
        </div>
    </div>
</x-app-layout>
