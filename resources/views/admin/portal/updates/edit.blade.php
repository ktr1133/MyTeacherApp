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
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                                    アプリ更新履歴編集
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">更新履歴の編集</p>
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
            <form method="POST" action="{{ route('admin.portal.updates.update', $update) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- アプリ名 -->
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        アプリ名 <span class="text-red-500">*</span>
                    </label>
                    <select name="app_name" id="app_name" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">選択してください</option>
                        <option value="MyTeacher" {{ old('app_name', $update->app_name) === 'MyTeacher' ? 'selected' : '' }}>MyTeacher</option>
                        <option value="KeepItSimple" {{ old('app_name', $update->app_name) === 'KeepItSimple' ? 'selected' : '' }}>KeepItSimple</option>
                    </select>
                </div>

                <!-- バージョン -->
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        バージョン <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="version" id="version" value="{{ old('version', $update->version) }}" required maxlength="20" placeholder="例: 1.2.3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- タイトル -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        タイトル <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title', $update->title) }}" required maxlength="255" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 説明 -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        説明 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" id="description" rows="8" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $update->description) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">HTMLタグが使用できます。</p>
                </div>

                <!-- リリース日 -->
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        リリース日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="release_date" id="release_date" value="{{ old('release_date', \Carbon\Carbon::parse($update->release_date)->format('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- メジャーアップデート -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        メジャーアップデート <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="radio" name="is_major" value="1" {{ old('is_major', $update->is_major) == '1' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">はい</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_major" value="0" {{ old('is_major', $update->is_major) == '0' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">いいえ</span>
                        </label>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex items-center space-x-4">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition">
                        更新
                    </button>
                    <a href="{{ route('admin.portal.updates.index') }}" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md transition">
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
