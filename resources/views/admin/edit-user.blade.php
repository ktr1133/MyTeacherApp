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

                        <a href="{{ route('admin.users.index') }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    ユーザー編集
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">{{ $user->username }}</p>
                            </div>
                        </div>
                    </div>

                    <span class="hidden sm:inline-block px-3 py-1.5 bg-gradient-to-r from-purple-500 to-indigo-600 text-white text-xs font-bold rounded-full">
                        管理者
                    </span>
                </div>
            </header>

            {{-- メインコンテンツエリア --}}
            <main class="flex-1 overflow-auto px-4 lg:px-6 py-4 lg:py-6">
                <div class="max-w-3xl mx-auto">
                    <div class="admin-card admin-fade-in">
                        <div class="p-6">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                @csrf
                                @method('PUT')

                                {{-- ユーザー名 --}}
                                <div class="admin-form-group">
                                    <label for="username" class="admin-form-label">ユーザー名</label>
                                    <input 
                                        type="text" 
                                        id="username" 
                                        name="username" 
                                        value="{{ old('username', $user->username) }}"
                                        class="admin-form-input @error('username') border-red-500 @enderror"
                                        required
                                    >
                                    @error('username')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- パスワード --}}
                                <div class="admin-form-group">
                                    <label for="password" class="admin-form-label">パスワード（変更する場合のみ入力）</label>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        class="admin-form-input @error('password') border-red-500 @enderror"
                                        placeholder="変更しない場合は空欄"
                                    >
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- グループ --}}
                                <div class="admin-form-group">
                                    <label class="admin-form-label">所属グループ</label>
                                    <div class="admin-form-input bg-gray-50 dark:bg-gray-700">
                                        {{ $user->group?->name ?? '未所属' }}
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">グループの変更は各ユーザーの設定画面から行ってください</p>
                                </div>

                                {{-- 管理者フラグ --}}
                                <div class="admin-form-group">
                                    <label class="flex items-center cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="is_admin" 
                                            value="1"
                                            {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 admin-form-label mb-0">管理者権限</span>
                                    </label>
                                </div>

                                {{-- グループ編集権限 --}}
                                <div class="admin-form-group">
                                    <label class="flex items-center cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="group_edit_flg" 
                                            value="1"
                                            {{ old('group_edit_flg', $user->group_edit_flg) ? 'checked' : '' }}
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 admin-form-label mb-0">グループ編集権限</span>
                                    </label>
                                </div>

                                {{-- トークン残高（表示のみ） --}}
                                <div class="admin-form-group">
                                    <label class="admin-form-label">トークン残高</label>
                                    <div class="admin-form-input bg-gray-50 dark:bg-gray-700 font-mono">
                                        {{ number_format($user->tokenBalance->balance ?? 0) }} トークン
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        無料: {{ number_format($user->tokenBalance->free_balance ?? 0) }} / 
                                        有料: {{ number_format($user->tokenBalance->paid_balance ?? 0) }}
                                    </p>
                                </div>

                                {{-- ボタン --}}
                                <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <button type="submit" class="admin-btn admin-btn-primary">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        更新
                                    </button>
                                    <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        キャンセル
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- 削除セクション --}}
                    @if(Auth::id() !== $user->id && !$user->isGroupMaster())
                        <div class="admin-card mt-6 border-red-200 dark:border-red-800">
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4">危険な操作</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    このユーザーを削除すると、関連するすべてのデータが削除されます。この操作は取り消せません。
                                </p>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('本当にこのユーザーを削除しますか？', () => { event.target.submit(); }); } else { if (confirm('本当にこのユーザーを削除しますか？')) { event.target.submit(); } }">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn admin-btn-danger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        ユーザーを削除
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-app-layout>