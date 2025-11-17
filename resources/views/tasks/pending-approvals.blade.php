<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/tasks/pending-approvals.css'])
    @endpush

    <div x-data="{ showSidebar: false }" class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        {{-- モバイルメニューボタン --}}
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            @click="showSidebar = true"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- ページタイトル --}}
                        <div class="flex items-center gap-3">
                            <div class="approval-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="approval-header-title text-lg font-bold">
                                    承認待ちタスク
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">タスクの承認と却下</p>
                            </div>
                        </div>
                    </div>

                    {{-- 戻るボタン --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('dashboard') }}" 
                           class="approval-btn-back inline-flex items-center justify-center shrink-0 rounded-xl text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition px-4 py-2.5">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="text-sm font-semibold whitespace-nowrap">戻る</span>
                        </a>
                    </div>

                    {{-- ユーザーメニュー（デスクトップ） --}}
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
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('ログアウト') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 p-4 lg:p-6 custom-scrollbar">
                <div class="max-w-5xl mx-auto">
                    @php
                        // 自分が承認者として設定されている承認待ちタスクのみ
                        $myPendingTasks = $pendingTasks->filter(fn($t) => $t->shouldShowInApprovalList(Auth::id()));
                    @endphp

                    @if($myPendingTasks->count() > 0)
                        <div class="space-y-4">
                            @foreach($myPendingTasks as $task)
                                <div class="bento-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 task-card-enter hover:shadow-xl transition-all duration-300 cursor-pointer"
                                     @click="$dispatch('open-approval-task-modal-{{ $task->id }}')">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">{{ $task->title }}</h3>
                                            <div class="flex flex-wrap gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span>{{ $task->user->username }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span>{{ $task->completed_at->format('Y/m/d H:i') }}</span>
                                                </div>
                                            </div>
                                            
                                            @if($task->description)
                                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg line-clamp-3">{{ $task->description }}</p>
                                            @endif
                                            
                                            @if($task->reward)
                                                <div class="mt-3 inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-purple-500/10 to-pink-500/10 border border-purple-500/20 rounded-lg">
                                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="text-sm font-semibold text-purple-600 dark:text-purple-400">報酬: {{ number_format($task->reward) }}円</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- 添付画像プレビュー --}}
                                    @if($task->images->count() > 0)
                                        <div class="mb-4">
                                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                                </svg>
                                                添付画像 ({{ $task->images->count() }}枚)
                                            </h4>
                                            <div class="grid grid-cols-3 gap-2">
                                                @foreach($task->images->take(3) as $image)
                                                    <div class="aspect-square rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700">
                                                        <img src="{{ Storage::url($image->file_path) }}" 
                                                             class="w-full h-full object-cover"
                                                             alt="タスク画像">
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- 承認/却下ボタン --}}
                                    <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                                         @click.stop>
                                        <form method="POST" action="{{ route('tasks.approve', $task) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('このタスクを承認しますか？')"
                                                    class="approval-btn-approve w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 hover:-translate-y-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                承認する
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="{{ route('tasks.reject', $task) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('このタスクを却下しますか？\n担当者は再度完了申請が必要になります。')"
                                                    class="approval-btn-reject w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-white font-semibold shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 hover:-translate-y-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                却下する
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- タスク編集モーダル --}}
                                @include('tasks.modal-approval-task-detail', ['task' => $task])
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state text-center py-16 bento-card rounded-2xl shadow-lg">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mb-2">承認待ちのタスクがありません</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">タスクの完了申請があるとここに表示されます</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/tasks/pending-approvals.js'])
    @endpush
</x-app-layout>