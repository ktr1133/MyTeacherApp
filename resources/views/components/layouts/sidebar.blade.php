{{-- デスクトップ: 左固定 --}}
@php
    $u = Auth::user();
    // 自分のタスク総数
    $sidebarTaskTotal = \App\Models\Task::where('user_id', $u->id)->count();

    // 自分が承認者として対応が必要な承認待ちタスク数（作成者 = 自分、かつ承認待ち）
    $sidebarPendingTotal = 0;
    if ($u->canEditGroup()) {
        $sidebarPendingTotal = \App\Models\Task::query()
            ->where('requires_approval', true)
            ->where('is_completed', true)
            ->whereNull('approved_at')
            ->where('assigned_by_user_id', $u->id)
            ->count();
    }
@endphp

{{-- デスクトップ: 左固定 --}}
<aside class="hidden lg:flex lg:flex-col shrink-0 bg-white border-r">
    <nav class="w-48 bg-white flex flex-col h-full border-r">
        {{-- ナビゲーションリンク --}}
        <div class="flex flex-col space-y-4 px-2 mt-6">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                </a>
            </div>

            <x-nav-link 
                :href="route('dashboard')" 
                :active="request()->routeIs('dashboard')" 
                class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
            >
                <i class="fas fa-tasks w-5"></i>
                <span class="text-sm">タスクリスト</span>
                <span class="ml-auto inline-flex items-center justify-center min-w-[1.5rem] h-5 px-2 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                    {{ $sidebarTaskTotal }}
                </span>
            </x-nav-link>
            @if(Auth::user()->canEditGroup())
                <x-nav-link 
                    :href="route('tasks.pending-approvals')" 
                    :active="request()->routeIs('tasks.pending-approvals')" 
                    class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
                >
                    <i class="fas fa-pending-approvals w-5"></i>
                    <span class="text-sm">承認待ちタスク</span>
                    <span class="ml-auto inline-flex items-center justify-center min-w-[1.5rem] h-5 px-2 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">
                        {{ $sidebarPendingTotal }}
                    </span>
                </x-nav-link>
            @endif
            <x-nav-link 
                :href="route('tags.list')" 
                :active="request()->routeIs('tags.list')" 
                class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
            >
                <i class="fas fa-tags w-5"></i>
                <span class="text-sm">タグ管理</span>
            </x-nav-link>

            <x-nav-link 
                :href="route('reports.performance')"
                :active="request()->routeIs('reports.performance')"
                class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
            >
                <i class="fas fa-chart-line w-5"></i>
                <span class="text-sm">実績</span>
            </x-nav-link>

            <x-nav-link 
                :href="route('profile.edit')" 
                class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
            >
                <i class="fas fa-user-cog w-5"></i>
                <span class="text-sm">アカウント</span>
            </x-nav-link>
        </div>
    </nav>
</aside>

{{-- モバイル: オフキャンバス --}}
<div class="lg:hidden">
    <!-- 背景オーバーレイ -->
    <div 
        x-show="showSidebar"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-black/40"
        @click="showSidebar = false"
        aria-hidden="true">
    </div>

    <!-- スライドインパネル -->
    <aside
        x-show="showSidebar"
        x-trap.noscroll="showSidebar"
        x-transition:enter="transform transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-full opacity-0"
        class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-white shadow-xl overflow-y-auto border-r">
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <span class="font-semibold">My Teacher</span>
            <button class="p-2 rounded hover:bg-gray-100" @click="showSidebar = false" aria-label="閉じる">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        <nav class="w-48 bg-white flex flex-col h-full border-r">
            {{-- ロゴ/タイトル --}}
            <div class="px-4 py-4 text-center bg-white border-[#E5F1F2]">
                <h1 class="text-xl font-bold text-[#59B9C6] tracking-wide">
                    My Teacher
                </h1>
            </div>

            {{-- ナビゲーションリンク --}}
            <div class="flex flex-col space-y-4 px-2 mt-6">
                <x-nav-link 
                    :href="route('dashboard')" 
                    :active="request()->routeIs('dashboard')" 
                    class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
                >
                    <i class="fas fa-tasks w-5"></i>
                    <span class="text-sm">タスクリスト</span>
                    <span class="ml-auto inline-flex items-center justify-center min-w-[1.5rem] h-5 px-2 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                        {{ $sidebarTaskTotal }}
                    </span>
                </x-nav-link>

                <x-nav-link 
                    :href="route('reports.performance')"
                    :active="request()->routeIs('reports.performance')"
                    class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
                >
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="text-sm">実績</span>
                </x-nav-link>

                <x-nav-link 
                    :href="route('profile.edit')" 
                    class="flex items-center px-2 py-3 rounded-md text-gray-700 hover:bg-white hover:text-[#59B9C6] transition-colors duration-150"
                >
                    <i class="fas fa-user-cog w-5"></i>
                    <span class="text-sm">アカウント</span>
                </x-nav-link>
            </div>
        </nav>
    </aside>
</div>