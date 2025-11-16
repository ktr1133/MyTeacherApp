<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/notifications.css'])
    @endpush

    @php
        $avatarEvent = session('avatar_event');
    @endphp

    @push('scripts')
        @vite(['resources/js/notifications/admin-notifications.js'])
        
        <script>
            let avatarEventFired = false;
            let dispatchAttempts = 0;
            
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('avatar_event'))
                    if (avatarEventFired) {
                        console.warn('[Admin Notifications Edit] Avatar event already fired, skipping');
                        return;
                    }
                    
                    const waitForAlpineAvatar = setInterval(() => {
                        dispatchAttempts++;
                        
                        if (window.Alpine && typeof window.dispatchAvatarEvent === 'function') {
                            clearInterval(waitForAlpineAvatar);
                            avatarEventFired = true;

                            setTimeout(() => {
                                window.dispatchAvatarEvent('{{ session('avatar_event') }}');
                            }, 500);
                        }
                        
                        if (dispatchAttempts > 100) {
                            clearInterval(waitForAlpineAvatar);
                            console.error('[Admin Notifications Edit] Alpine initialization timeout');
                        }
                    }, 50);
                @endif
            });
        </script>
    @endpush

    <div x-data="{ showSidebar: false }" class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        {{-- ハンバーガーメニュー（モバイル） --}}
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            @click="showSidebar = true"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- ヘッダーアイコンとタイトル --}}
                        <div class="flex items-center gap-3">
                            <div class="dashboard-btn-group w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    通知編集
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">通知内容を更新</p>
                            </div>
                        </div>
                    </div>

                    {{-- キャンセルボタン --}}
                    <a href="{{ route('admin.notifications.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        キャンセル
                    </a>

                    {{-- ユーザードロップダウン --}}
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-gray-500 dark:text-gray-400 bg-white/50 dark:bg-gray-800/50 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/80 dark:hover:bg-gray-800/80 focus:outline-none transition backdrop-blur-sm">
                                    <div>{{ Auth::user()->username }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('アカウント') }}
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('ログアウト') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-4xl mx-auto px-4 lg:px-6 py-6 lg:py-8">
                    {{-- 通知情報カード --}}
                    <div class="bento-card rounded-2xl shadow-lg p-4 lg:p-6 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">通知情報</h3>
                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    <p>作成者: <span class="font-medium">{{ $notification->sender->username }}</span></p>
                                    <p>作成日時: <span class="font-medium">{{ $notification->created_at->format('Y/m/d H:i') }}</span></p>
                                    @if($notification->updated_by)
                                        <p>最終更新者: <span class="font-medium">{{ $notification->updatedBy->username }}</span></p>
                                        <p>最終更新日時: <span class="font-medium">{{ $notification->updated_at->format('Y/m/d H:i') }}</span></p>
                                    @endif
                                    <p>配信件数: <span class="font-medium">{{ number_format($notification->userNotifications()->count()) }} 件</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- フォームカード --}}
                    <div class="bento-card rounded-2xl shadow-lg p-6 lg:p-8">
                        <form method="POST" action="{{ route('admin.notifications.update', $notification->id) }}" class="space-y-6">
                            @csrf
                            @method('PUT')

                            {{-- 通知種別 --}}
                            <div>
                                <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    通知種別 <span class="text-red-500">*</span>
                                </label>
                                <select id="type" name="type" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition" 
                                    required>
                                    <option value="">選択してください</option>
                                    <option value="admin_announcement" {{ old('type', $notification->type) === 'admin_announcement' ? 'selected' : '' }}>お知らせ</option>
                                    <option value="admin_maintenance" {{ old('type', $notification->type) === 'admin_maintenance' ? 'selected' : '' }}>メンテナンス</option>
                                    <option value="admin_update" {{ old('type', $notification->type) === 'admin_update' ? 'selected' : '' }}>アップデート</option>
                                    <option value="admin_warning" {{ old('type', $notification->type) === 'admin_warning' ? 'selected' : '' }}>警告</option>
                                </select>
                                @error('type')
                                    <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- 優先度 --}}
                            <div>
                                <label for="priority" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    優先度 <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    @foreach(['info' => '情報', 'normal' => '通常', 'important' => '重要'] as $value => $label)
                                        <label class="relative flex items-center justify-center px-4 py-3 border-2 rounded-xl cursor-pointer transition
                                            {{ old('priority', $notification->priority) === $value ? 'border-purple-500 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600' }}">
                                            <input type="radio" name="priority" value="{{ $value }}" 
                                                {{ old('priority', $notification->priority) === $value ? 'checked' : '' }}
                                                class="sr-only" required>
                                            <span class="text-sm font-semibold {{ old('priority', $notification->priority) === $value ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $label }}
                                            </span>
                                            @if(old('priority', $notification->priority) === $value)
                                                <svg class="absolute top-2 right-2 w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                                @error('priority')
                                    <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- タイトル --}}
                            <div>
                                <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    タイトル <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="title" name="title" value="{{ old('title', $notification->title) }}" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition" 
                                    maxlength="255" 
                                    placeholder="通知のタイトルを入力"
                                    required>
                                @error('title')
                                    <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- 本文 --}}
                            <div>
                                <label for="message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    本文 <span class="text-red-500">*</span>
                                </label>
                                <textarea id="message" name="message" rows="6" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition resize-none" 
                                    maxlength="10000" 
                                    placeholder="通知の本文を入力"
                                    required>{{ old('message', $notification->message) }}</textarea>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    最大10,000文字
                                </p>
                                @error('message')
                                    <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- 区切り線 --}}
                            <div class="section-divider my-6"></div>

                            {{-- アクションURL --}}
                            <div>
                                <label for="action_url" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    アクションURL
                                </label>
                                <input type="url" id="action_url" name="action_url" value="{{ old('action_url', $notification->action_url) }}" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition" 
                                    placeholder="https://example.com">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ボタンのリンク先URL（任意）
                                </p>
                                @error('action_url')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- アクションテキスト --}}
                            <div>
                                <label for="action_text" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    アクションボタンのテキスト
                                </label>
                                <input type="text" id="action_text" name="action_text" value="{{ old('action_text', $notification->action_text) }}" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition" 
                                    maxlength="100" 
                                    placeholder="詳細を見る">
                                @error('action_text')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 公式ページスラッグ --}}
                            <div>
                                <label for="official_page_slug" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    公式ページスラッグ
                                </label>
                                <input type="text" id="official_page_slug" name="official_page_slug" value="{{ old('official_page_slug', $notification->official_page_slug) }}" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition" 
                                    placeholder="2025-winter-update" 
                                    pattern="[a-z0-9\-]+">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    半角英数字とハイフンのみ（例: 2025-winter-update）
                                </p>
                                @error('official_page_slug')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 区切り線 --}}
                            <div class="section-divider my-6"></div>

                            {{-- 配信対象（変更不可） --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    配信対象 <span class="text-amber-600 dark:text-amber-400 text-xs">(変更不可)</span>
                                </label>
                                <div class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-700/50 rounded-xl border-2 border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        @if($notification->target_type === 'all')
                                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">全ユーザー</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">すべてのユーザーに配信済み</p>
                                            </div>
                                        @elseif($notification->target_type === 'users')
                                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">特定ユーザー</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($notification->target_ids ?? []) }}人のユーザーに配信済み</p>
                                            </div>
                                        @else
                                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">特定グループ</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($notification->target_ids ?? []) }}グループに配信済み</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" name="target_type" value="{{ $notification->target_type }}">
                                @if($notification->target_ids)
                                    @foreach($notification->target_ids as $targetId)
                                        <input type="hidden" name="target_ids[]" value="{{ $targetId }}">
                                    @endforeach
                                @endif
                            </div>

                            {{-- 区切り線 --}}
                            <div class="section-divider my-6"></div>

                            {{-- 公開開始日時 --}}
                            <div>
                                <label for="publish_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    公開開始日時
                                </label>
                                <input type="datetime-local" id="publish_at" name="publish_at" 
                                    value="{{ old('publish_at', $notification->publish_at?->format('Y-m-d\TH:i')) }}" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    未設定の場合は即時公開
                                </p>
                                @error('publish_at')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 公開終了日時 --}}
                            <div>
                                <label for="expire_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    公開終了日時
                                </label>
                                <input type="datetime-local" id="expire_at" name="expire_at" 
                                    value="{{ old('expire_at', $notification->expire_at?->format('Y-m-d\TH:i')) }}" 
                                    class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 transition">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    未設定の場合は無期限公開
                                </p>
                                @error('expire_at')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ボタン --}}
                            <div class="flex gap-4 pt-4">
                                <button type="submit" class="dashboard-btn-group flex-1 inline-flex items-center justify-center px-6 py-3 rounded-xl text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                                    </svg>
                                    <span class="font-semibold">更新</span>
                                </button>
                                <a href="{{ route('admin.notifications.index') }}" 
                                   class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm">
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