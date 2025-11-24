<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
        @vite(['resources/css/avatar/avatar.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/dashboard/dashboard.js'])
        @vite(['resources/js/dashboard/tab-switch.js'])
        @vite(['resources/js/dashboard/bulk-complete.js'])
        @vite(['resources/js/dashboard/tag-modal.js'])
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
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    <div id="task-list-container">
                        @include('dashboard.partials.task-bento')
                    </div>
                </div>
            </main>
        </div>

        {{-- モーダル（共通、CSS でテーマ切替） --}}
        @include('dashboard.modal-dashboard-task')
        
        @if(Auth::user()->canEditGroup())
            @include('dashboard.modal-group-task')
        @endif
    </div>
</x-app-layout>