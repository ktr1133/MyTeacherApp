<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
        @vite(['resources/css/avatar/avatar.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/dashboard/dashboard.js'])
        @if(Auth::user()->canEditGroup())
            @vite(['resources/js/dashboard/group-task.js'])
        @endif
        
        {{-- 子ども向け花火エフェクトスクリプト --}}
        @if($isChildTheme)
            <script>
                // タスク完了時の花火エフェクト
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.btn-complete-quest').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            // 花火を発射
                            confetti({
                                particleCount: 100,
                                spread: 70,
                                origin: { y: 0.6 }
                            });
                            
                            // 0.3秒後にもう一度
                            setTimeout(() => {
                                confetti({
                                    particleCount: 50,
                                    angle: 60,
                                    spread: 55,
                                    origin: { x: 0 }
                                });
                                confetti({
                                    particleCount: 50,
                                    angle: 120,
                                    spread: 55,
                                    origin: { x: 1 }
                                });
                            }, 300);
                        });
                    });
                });
            </script>
        @endif
        
        {{-- ログインイベント発火 --}}
        <script>
            let avatarEventFired = false;
            let dispatchAttempts = 0;
            
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('avatar_event'))
                    if (avatarEventFired) {
                        console.warn('[Dashboard] Avatar event already fired, skipping');
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
                            console.error('[Dashboard] Alpine initialization timeout');
                        }
                    }, 50);
                @endif
            });
        </script>
    @endpush

    <div x-data="{ 
        showTaskModal: false, 
        showDecompositionModal: false, 
        showRefineModal: false,
        isProposing: false,
        taskTitle: '',
        taskSpan: 'mid', 
        refinementPoints: '',
        decompositionProposal: null,
        showSidebar: false,
        activeTab: 'todo',
        
        startDecomposition: function(isRefinement = false) {
            if (typeof window.decomposeTask === 'function') {
                window.decomposeTask.call(this, isRefinement);
            }
        },
        confirmProposal: function() {
            if (typeof window.acceptProposal === 'function') {
                window.acceptProposal.call(this);
            }
        }
    }" class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

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
                    @include('dashboard.partials.task-bento')
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