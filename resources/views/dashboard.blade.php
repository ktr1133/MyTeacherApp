<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
        @vite(['resources/css/avatar/avatar.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/dashboard/dashboard.js'])
        @vite(['resources/js/dashboard/task-modal.js'])
        @vite(['resources/js/dashboard/tag-tasks-modal.js'])
        @vite(['resources/js/dashboard/bulk-complete.js'])
        @vite(['resources/js/dashboard/tag-modal.js'])
        @vite(['resources/js/dashboard/group-task-detail.js'])
        @vite(['resources/js/infinite-scroll.js'])
        @vite(['resources/js/dashboard/infinite-scroll-init.js'])
        @if(Auth::user()->canEditGroup())
            @vite(['resources/js/dashboard/group-task.js'])
        @endif
    @endpush
    {{-- アバターイベント監視用 --}}
    <x-layouts.avatar-event-common />
    {{-- 汎用確認ダイアログ --}}
    <x-confirm-dialog />
    {{-- 汎用アラートダイアログ --}}
    <x-alert-dialog />

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾（大人向けのみ） --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
                <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            @include('dashboard.partials.header')

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar" id="main-scroll-container">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    <div id="task-list-container" 
                         data-has-more="{{ $hasMore ? 'true' : 'false' }}" 
                         data-next-page="{{ $nextPage }}" 
                         data-per-page="{{ $perPage }}">
                        @include('dashboard.partials.task-bento')
                    </div>
                    
                    {{-- ローディングインジケーター --}}
                    <div id="loading-indicator" class="hidden text-center py-8">
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
                            <svg class="animate-spin h-5 w-5 text-[#59B9C6]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">読み込み中...</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        {{-- モーダル（共通、CSS でテーマ切替） --}}
        @include('dashboard.modal-dashboard-task')
        
        @if(Auth::user()->canEditGroup())
            @include('dashboard.modal-group-task')
            {{-- グループタスク作成上限エラーモーダル --}}
            <x-group-task-limit-modal />
        @endif

        {{-- タスクカード用モーダル（すべてのタスクに対して body 直下で include） --}}
        @foreach($tasks as $task)
            @if($task->canEdit())
                @include('dashboard.modal-task-card', ['task' => $task, 'tags' => $tags])
            @else
                @include('dashboard.modal-group-task-detail', ['task' => $task])
            @endif
        @endforeach
    </div>
</x-app-layout>