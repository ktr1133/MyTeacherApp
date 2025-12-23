<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css', 'resources/css/batch.css'])
    @endpush
    @push('scripts')
        @vite(['resources/js/batch/scheduled-task-controller.js'])
    @endpush

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            data-sidebar-toggle="mobile"
                            aria-label="メニューを開く"
                            class="lg:hidden p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                <span class="hidden sm:inline">スケジュール編集</span>
                                <span class="sm:hidden">設定</span>
                            </h2>
                            <p class="hidden sm:block text-xs text-gray-500 dark:text-gray-400">定期タスクの設定を変更</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('batch.scheduled-tasks.index', ['group_id' => $groupId]) }}" 
                           class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="hidden sm:inline">戻る</span>
                        </a>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 custom-scrollbar">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-12">
                    
                    @if ($errors->any())
                        <div class="mb-6 bento-card rounded-xl p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border border-red-200 dark:border-red-700">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-2">入力エラーがあります</h3>
                                    <ul class="text-sm text-red-700 dark:text-red-400 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('batch.scheduled-tasks.update', $scheduledTask->id) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @include('batch.partials.scheduled-task-form-edit')

                        {{-- 送信ボタン --}}
                        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('batch.scheduled-tasks.index', ['group_id' => $groupId]) }}"
                               class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 transition">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                更新する
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    @include('batch.partials.scheduled-task-form-script-edit')
</x-app-layout>