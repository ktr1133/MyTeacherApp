<x-app-layout>
    @push('styles')
        @vite(['resources/css/admin/common.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div x-data="adminPage()" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        
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
                                    FAQ管理
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">よくある質問の管理</p>
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
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- フィルター -->
        <div class="mb-6 admin-card p-4">
            <form method="GET" action="{{ route('admin.portal.faqs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">アプリ名</label>
                    <select name="app_name" id="app_name" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">全て</option>
                        <option value="MyTeacher" {{ request('app_name') === 'MyTeacher' ? 'selected' : '' }}>MyTeacher</option>
                        <option value="KeepItSimple" {{ request('app_name') === 'KeepItSimple' ? 'selected' : '' }}>KeepItSimple</option>
                    </select>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">カテゴリ</label>
                    <input type="text" name="category" id="category" value="{{ request('category') }}" placeholder="カテゴリで検索" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="is_published" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">公開状態</label>
                    <select name="is_published" id="is_published" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">全て</option>
                        <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>公開</option>
                        <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>非公開</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- 新規登録ボタン -->
        <div class="mb-4">
            <a href="{{ route('admin.portal.faqs.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                新規登録
            </a>
        </div>

        <!-- FAQテーブル -->
        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700" style="table-layout: fixed;">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 60px;">ID</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 120px;">アプリ</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 140px;">カテゴリ</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 400px; min-width: 400px;">質問</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 80px;">表示順</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 100px;">公開状態</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 110px;">作成日</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="width: 180px;">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($faqs as $faq)
                            <tr>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $faq->id }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <span class="px-2 py-1 text-xs rounded whitespace-nowrap {{ $faq->app_name === 'MyTeacher' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                        {{ $faq->app_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="break-words">{{ $faq->category }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="break-words">{{ $faq->question }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $faq->display_order }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    @if ($faq->is_published)
                                        <span class="px-2 py-1 text-xs rounded whitespace-nowrap bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            公開
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded whitespace-nowrap bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            非公開
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $faq->created_at->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-4 text-sm font-medium">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <a href="{{ route('admin.portal.faqs.edit', $faq) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 whitespace-nowrap">編集</a>
                                        
                                        <form method="POST" action="{{ route('admin.portal.faqs.toggle-published', $faq) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 whitespace-nowrap">
                                                {{ $faq->is_published ? '非公開' : '公開' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.portal.faqs.destroy', $faq) }}" class="inline" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('本当に削除しますか?', () => { event.target.submit(); }); } else { if (confirm('本当に削除しますか?')) { event.target.submit(); } }">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 whitespace-nowrap">削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    FAQが登録されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            @if ($faqs->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $faqs->links() }}
                </div>
            @endif
        </div>

                </div>
            </main>
        </div>
    </div>
</x-app-layout>
