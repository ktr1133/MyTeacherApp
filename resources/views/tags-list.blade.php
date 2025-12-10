<x-app-layout>
    @push('styles')
        @vite(['resources/css/tags.css', 'resources/css/dashboard.css'])
    @endpush

    <div
        id="tag-page"
        x-data="{ 
            showSidebar: false,
            editingTagId: null,
            editingTagName: ''
        }"
        x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
        class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden"
        data-app-origin="{{ url('') }}"
        data-tags-base="/tags"
    >

        {{-- 背景装飾 --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
                <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            data-sidebar-toggle="mobile"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="tag-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                @if(!$isChildTheme)
                                    <h1 class="tag-header-title text-lg font-bold truncate">
                                        タグ管理
                                    </h1>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 hidden min-[380px]:block">タスクの分類と整理</p>
                                @else
                                    <h1 class="tag-header-title text-lg font-bold truncate">
                                        タグ
                                    </h1>
                                @endif
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">{{ !$isChildTheme ? 'タスクリストへ戻る' : 'ToDoにもどる' }}</span>
                    </a>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    <div class="space-y-6">
                    {{-- 新規タグ作成カード --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter">
                        <div class="tag-block-header px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30">
                            <div class="flex items-center gap-3">
                                <div class="tag-header-icon w-8 h-8 rounded-lg flex items-center justify-center shadow">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                                <h2 class="tag-header-title text-sm font-bold">
                                    新規タグを作成
                                </h2>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('tags.store') }}" class="p-6">
                            @csrf
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <input type="text" name="name" required
                                           placeholder="タグ名を入力してください"
                                           class="w-full px-4 py-2.5 border border-blue-500/30 dark:border-blue-500/40 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm placeholder-gray-400">
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 shadow-lg hover:shadow-xl px-6 py-2.5 text-sm font-semibold transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    追加
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- タグ一覧カード --}}
                    <div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.1s;">
                        <div class="tag-block-header px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="tag-header-icon w-8 h-8 rounded-lg flex items-center justify-center shadow">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <h2 class="tag-header-title text-sm font-bold">
                                        タグ一覧
                                    </h2>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">クリックで関連タスクを表示</p>
                            </div>
                        </div>

                        <div class="p-6">
                            @if(isset($tags) && count($tags) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    @foreach($tags as $tag)
                                        <div x-data="{ showEditForm: false }"
                                             class="group relative bg-white dark:bg-gray-800 border border-blue-500/20 dark:border-blue-500/30 rounded-xl p-4 hover:shadow-lg transition-all duration-300 overflow-hidden"
                                             style="animation: fadeInUp 0.5s ease-out; animation-delay: {{ $loop->index * 0.05 }}s;">
                                            
                                            {{-- ホバーエフェクト --}}
                                            <div class="tag-card-hover absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            
                                            <div class="relative">
                                                {{-- タグ情報 --}}
                                                <button type="button"
                                                        class="text-left w-full mb-3 cursor-pointer"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->name }}"
                                                        data-action="open-tag-modal">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="tag-badge-gradient inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                                            </svg>
                                                            タグ
                                                        </span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-gray-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition mb-2">
                                                        {{ $tag->name }}
                                                    </h3>
                                                    @if($tag->tasks_count > 0)
                                                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                            </svg>
                                                            <span>関連タスク: <strong class="tag-task-count">{{ $tag->tasks_count }}</strong> 件</span>
                                                        </div>
                                                    @else
                                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                                            関連タスクなし
                                                        </div>
                                                    @endif
                                                </button>

                                                {{-- アクションボタン --}}
                                                <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                    {{-- 編集ボタン --}}
                                                    <button type="button"
                                                            @click.stop="showEditForm = !showEditForm"
                                                            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                        </svg>
                                                        <span>編集</span>
                                                    </button>

                                                    {{-- 削除ボタン --}}
                                                    <form method="POST" action="{{ route('tags.destroy', $tag->id) }}"
                                                          onsubmit="event.preventDefault(); if(window.showConfirmDialog) { window.showConfirmDialog('タグ「{{ $tag->name }}」を削除しますか？この操作は取り消せません。', () => { event.target.submit(); }); } else { if(confirm('タグ「{{ $tag->name }}」を削除しますか？この操作は取り消せません。')) { event.target.submit(); } }">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                                                aria-label="削除">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m-3 0h14"/>
                                                            </svg>
                                                            <span>削除</span>
                                                        </button>
                                                    </form>
                                                </div>

                                                {{-- 編集フォーム（Alpine.jsで制御） --}}
                                                <div x-show="showEditForm"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 transform scale-95"
                                                     x-transition:enter-end="opacity-100 transform scale-100"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100 transform scale-100"
                                                     x-transition:leave-end="opacity-0 transform scale-95"
                                                     @click.stop
                                                     class="mt-3 pt-3 border-t border-blue-500/20 dark:border-blue-500/30"
                                                     style="display: none;">
                                                    <form method="POST" action="{{ route('tags.update', $tag->id) }}" class="space-y-3">
                                                        @csrf
                                                        @method('PUT')
                                                        <div>
                                                            <label for="edit-tag-{{ $tag->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                                タグ名
                                                            </label>
                                                            <input type="text" 
                                                                   id="edit-tag-{{ $tag->id }}"
                                                                   name="name" 
                                                                   value="{{ $tag->name }}" 
                                                                   required
                                                                   class="w-full px-3 py-2 rounded-lg border border-blue-500/30 dark:border-blue-500/40 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <button type="submit"
                                                                    class="flex-1 rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-3 py-2 text-sm font-medium shadow hover:shadow-lg transition">
                                                                保存
                                                            </button>
                                                            <button type="button"
                                                                    @click="showEditForm = false"
                                                                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                                                キャンセル
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state text-center py-16 rounded-xl border-2 border-dashed border-blue-500/30 bg-blue-50/50 dark:bg-blue-900/10">
                                    <svg class="mx-auto h-16 w-16 text-blue-400 dark:text-blue-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">タグがありません</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">上のフォームから新しいタグを作成してください</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    </div>
                </div>
            </main>
        </div>

        {{-- タグのタスク管理モーダル --}}
        @include('tags.modal-tags-list')
    </div>

    @push('scripts')
        @vite(['resources/js/tags/tags.js'])
    @endpush
</x-app-layout>