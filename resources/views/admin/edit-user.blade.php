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

                                {{-- グループタスク制限設定（グループ所属時のみ表示） --}}
                                @if($user->group)
                                    <div class="admin-form-group mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            グループ設定（{{ $user->group->name }}）
                                        </h3>

                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 space-y-4">
                                            {{-- サブスクリプション状態（表示のみ） --}}
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                    サブスクリプション状態
                                                </label>
                                                <div class="flex items-center gap-2">
                                                    @if($user->group->subscription_active)
                                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            有効
                                                        </span>
                                                        @if($user->group->subscription_plan)
                                                            <span class="text-xs px-2 py-1 rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                                {{ ucfirst($user->group->subscription_plan) }}プラン
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                            無料プラン
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- グループタスク無料枠上限 --}}
                                            <div>
                                                <label for="free_group_task_limit" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                    グループタスク無料作成回数（月次）
                                                </label>
                                                <input 
                                                    type="number" 
                                                    id="free_group_task_limit" 
                                                    name="free_group_task_limit" 
                                                    value="{{ old('free_group_task_limit', $user->group->free_group_task_limit) }}"
                                                    min="0"
                                                    max="100"
                                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm @error('free_group_task_limit') border-red-500 @enderror"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">
                                                    現在の使用状況: {{ $user->group->group_task_count_current_month }} / {{ $user->group->free_group_task_limit }}
                                                </p>
                                                @error('free_group_task_limit')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            {{-- 無料トライアル日数 --}}
                                            <div>
                                                <label for="free_trial_days" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                    無料トライアル期間（日数）
                                                </label>
                                                <input 
                                                    type="number" 
                                                    id="free_trial_days" 
                                                    name="free_trial_days" 
                                                    value="{{ old('free_trial_days', $user->group->free_trial_days) }}"
                                                    min="0"
                                                    max="90"
                                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm @error('free_trial_days') border-red-500 @enderror"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">
                                                    サブスクリプション開始時の無料試用期間
                                                </p>
                                                @error('free_trial_days')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        <p class="mt-2 text-xs text-yellow-600 dark:text-yellow-400 flex items-start gap-1">
                                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>これらの設定は<strong>システム管理者のみ</strong>が変更できます。グループ管理者は閲覧のみ可能です。</span>
                                        </p>
                                    </div>
                                @endif

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