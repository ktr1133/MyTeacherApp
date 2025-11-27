<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/batch.css'])
    @endpush

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

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
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            data-sidebar-toggle="mobile"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-600 to-slate-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg font-bold bg-gradient-to-r from-gray-600 to-slate-600 bg-clip-text text-transparent">
                                    実行履歴
                                </h1>
                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $scheduledTask->title }}</p>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('batch.scheduled-tasks.index', ['group_id' => $groupId]) }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="hidden sm:inline">一覧へ戻る</span>
                    </a>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-12 space-y-6">
                    
                    {{-- スケジュール情報 --}}
                    <div class="bento-card rounded-xl p-6">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ $scheduledTask->title }}
                                </h2>
                                @if ($scheduledTask->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ $scheduledTask->description }}
                                    </p>
                                @endif
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($scheduledTask->schedules as $schedule)
                                        <span class="schedule-badge {{ $schedule['type'] }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            @if ($schedule['type'] === 'daily')
                                                毎日 {{ $schedule['time'] ?? '' }}
                                            @elseif ($schedule['type'] === 'weekly')
                                                @php
                                                    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                                                    $days = collect($schedule['days'] ?? [])->map(fn($d) => $weekdays[$d])->join('・');
                                                @endphp
                                                毎週{{ $days }} {{ $schedule['time'] ?? '' }}
                                            @elseif ($schedule['type'] === 'monthly')
                                                毎月{{ implode(',', $schedule['dates'] ?? []) }}日 {{ $schedule['time'] ?? '' }}
                                            @endif
                                        </span>
                                    @endforeach
                                    <span class="status-badge {{ $scheduledTask->is_active ? 'active' : 'paused' }}">
                                        @if ($scheduledTask->is_active)
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            有効
                                        @else
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            一時停止
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('batch.scheduled-tasks.edit', $scheduledTask->id) }}"
                               class="shrink-0 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                編集
                            </a>
                        </div>
                    </div>

                    {{-- 実行履歴テーブル --}}
                    @include('batch.partials.execution-history')
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush
</x-app-layout>