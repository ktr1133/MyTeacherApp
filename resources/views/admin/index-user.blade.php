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
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    ユーザー管理
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">システムユーザーの管理</p>
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

            {{-- メインコンテンツエリア --}}
            <main class="flex-1 overflow-auto px-4 lg:px-6 py-4 lg:py-6">
                
                {{-- 統計カード --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="admin-card stat-card admin-fade-in">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">総ユーザー数</span>
                                <div class="stat-card-icon w-10 h-10 rounded-lg flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $users->total() }}</div>
                        </div>
                    </div>

                    <div class="admin-card stat-card admin-fade-in" style="animation-delay: 0.1s;">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">管理者</span>
                                <div class="stat-card-icon w-10 h-10 rounded-lg flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['admin_count'] ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="admin-card stat-card admin-fade-in" style="animation-delay: 0.2s;">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">グループマスター</span>
                                <div class="stat-card-icon w-10 h-10 rounded-lg flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['master_count'] ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="admin-card stat-card admin-fade-in" style="animation-delay: 0.3s;">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">一般ユーザー</span>
                                <div class="stat-card-icon w-10 h-10 rounded-lg flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['normal_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                {{-- ユーザー一覧 --}}
                <div class="admin-card admin-fade-in" style="animation-delay: 0.4s;">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">ユーザー一覧</h2>
                            
                            {{-- 検索フォーム --}}
                            <form method="GET" class="flex gap-2 w-full sm:w-auto">
                                <input 
                                    type="text" 
                                    name="search" 
                                    value="{{ request('search') }}"
                                    placeholder="ユーザー名で検索..."
                                    class="admin-form-input text-sm w-full sm:w-64"
                                >
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ユーザー名</th>
                                        <th>グループ</th>
                                        <th>権限</th>
                                        <th>トークン残高</th>
                                        <th>登録日</th>
                                        <th class="text-center">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td class="font-mono text-sm text-gray-500">{{ $user->id }}</td>
                                            <td class="font-semibold">{{ $user->username }}</td>
                                            <td>
                                                @if($user->group)
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $user->group->name }}</span>
                                                @else
                                                    <span class="text-sm text-gray-400">未所属</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($user->is_admin)
                                                    <span class="admin-badge admin-badge-danger">管理者</span>
                                                @elseif($user->isGroupMaster())
                                                    <span class="admin-badge admin-badge-warning">マスター</span>
                                                @elseif($user->canEditGroup())
                                                    <span class="admin-badge admin-badge-info">編集権限</span>
                                                @else
                                                    <span class="admin-badge admin-badge-success">一般</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $balance = $user->getOrCreateTokenBalance();
                                                @endphp
                                                <span class="font-mono text-sm">{{ number_format($balance->balance) }}</span>
                                            </td>
                                            <td class="text-sm text-gray-500">{{ $user->created_at->format('Y/m/d') }}</td>
                                            <td>
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn admin-btn-secondary text-xs px-3 py-1.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        編集
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-8 text-gray-500">
                                                ユーザーが見つかりませんでした
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- ページネーション --}}
                        @if($users->hasPages())
                            <div class="mt-6 flex justify-center">
                                <div class="admin-pagination">
                                    {{-- 前へ --}}
                                    @if ($users->onFirstPage())
                                        <span class="admin-pagination-link disabled">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                            </svg>
                                        </span>
                                    @else
                                        <a href="{{ $users->previousPageUrl() }}" class="admin-pagination-link">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- ページ番号 --}}
                                    @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                                        @if ($page == $users->currentPage())
                                            <span class="admin-pagination-link active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="admin-pagination-link">{{ $page }}</a>
                                        @endif
                                    @endforeach

                                    {{-- 次へ --}}
                                    @if ($users->hasMorePages())
                                        <a href="{{ $users->nextPageUrl() }}" class="admin-pagination-link">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="admin-pagination-link disabled">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>