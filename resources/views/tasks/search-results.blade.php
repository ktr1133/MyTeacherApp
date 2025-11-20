<x-app-layout>
    @push('styles')
        @vite(['resources/css/tasks/search-results.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/tasks/search-tasks.js', 'resources/js/dashboard/dashboard.js'])
        
        {{-- アバターイベント発火（既存のロジック） --}}
        <script>
            let avatarEventFired = false;
            let dispatchAttempts = 0;
            
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('avatar_event'))
                    if (avatarEventFired) {
                        console.warn('[Search Results] Avatar event already fired, skipping');
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
                            console.error('[Search Results] Alpine initialization timeout', {
                                attempts: dispatchAttempts,
                            });
                        }
                    }, 50);
                @endif
            });
        </script>
    @endpush

    <div class="flex min-h-[100dvh] search-results-gradient-bg relative overflow-hidden">
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div
            x-show="showModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="flex-1 flex flex-col overflow-y-auto"
        >
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 search-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        {{-- モバイルメニューボタン --}}
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            onclick="document.dispatchEvent(new CustomEvent('toggle-sidebar'))"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="search-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="search-header-title text-lg font-bold">
                                    検索結果
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $totalCount }}件のタスク</p>
                            </div>
                        </div>
                    </div>

                    {{-- ダッシュボードに戻るボタン --}}
                    <a href="{{ route('dashboard') }}" 
                       class="back-to-dashboard-btn inline-flex items-center justify-center shrink-0 rounded-xl px-4 py-2.5 text-white shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                        </svg>
                        <span class="hidden sm:inline-block text-sm font-semibold">ダッシュボードに戻る</span>
                        <span class="sm:hidden text-sm font-semibold">戻る</span>
                    </a>
                </div>

                {{-- 検索クエリ表示 --}}
                <div class="px-4 lg:px-6 py-3 border-t border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            @if($searchType === 'tag')
                                タグ検索:
                            @else
                                タイトル検索:
                            @endif
                        </span>
                        @foreach($searchTerms as $term)
                            <span class="search-query-chip inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                @if($searchType === 'tag')
                                    #{{ $term }}
                                @else
                                    {{ $term }}
                                @endif
                            </span>
                        @endforeach
                        @if(count($searchTerms) > 1)
                            <span class="search-operator-badge inline-flex items-center px-2 py-1 rounded text-xs font-bold">
                                {{ $operator === 'and' ? 'AND' : 'OR' }}
                            </span>
                        @endif
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-6xl mx-auto px-4 lg:px-6 py-6">
                    @if($tasks->isEmpty())
                        {{-- 空状態 --}}
                        <div class="empty-state text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">該当するタスクが見つかりませんでした</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">別の検索条件をお試しください</p>
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-[#59B9C6] text-white rounded-lg hover:bg-[#4AA5B2] transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                </svg>
                                ダッシュボードに戻る
                            </a>
                        </div>
                    @else
                        {{-- タスク一覧 --}}
                        <div class="space-y-4">
                            @foreach($tasks as $task)
                                <div class="task-result-item">
                                    {{-- タスクカード（data-task-id属性を追加） --}}
                                    <div data-task-id="{{ $task->id }}">
                                        <x-task-card 
                                            :task="$task" 
                                            :tags="[]" 
                                            :isCompleted="$task->is_completed" 
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-app-layout>