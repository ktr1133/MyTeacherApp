<x-app-layout>
    @push('styles')
        @vite(['resources/css/admin/common.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        <x-layouts.sidebar />

        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
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
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">
                                    課金履歴
                                </h1>
                                <p class="hidden sm:block text-xs text-teal-700 dark:text-cyan-300">トークン課金の履歴一覧</p>
                            </div>
                        </div>
                    </div>
                    <span class="hidden sm:inline-block px-3 py-1.5 bg-gradient-to-r from-teal-500 to-cyan-600 text-white text-xs font-bold rounded-full">
                        管理者
                    </span>
                </div>
            </header>

            <main class="flex-1 overflow-auto px-4 lg:px-6 py-4 lg:py-6">
                <div class="admin-card admin-fade-in">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">課金履歴一覧</h2>
                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>日時</th>
                                        <th>ユーザー</th>
                                        <th>金額</th>
                                        <th>ステータス</th>
                                        <th>決済ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($histories as $history)
                                        <tr>
                                            <td><x-user-local-time :datetime="$history->created_at" format="Y/m/d H:i" /></td>
                                            <td>{{ $history->user?->username ?? '不明' }}</td>
                                            <td class="font-mono text-right">¥{{ number_format($history->amount) }}</td>
                                            <td>
                                                @if($history->status === 'succeeded')
                                                    <span class="admin-badge admin-badge-success">成功</span>
                                                @else
                                                    <span class="admin-badge admin-badge-danger">{{ $history->status }}</span>
                                                @endif
                                            </td>
                                            <td class="font-mono text-xs">{{ $history->payment_id }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-8 text-gray-500">履歴がありません</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{-- ページネーション --}}
                        <div class="mt-6 flex justify-center">
                            {{ $histories->links() }}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>