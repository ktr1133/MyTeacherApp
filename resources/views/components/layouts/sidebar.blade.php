@php
    $u = Auth::user();
    $sidebarTaskTotal = \App\Models\Task::where('user_id', $u->id)->count();
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

{{-- デスクトップ: 左固定サイドバー --}}
<aside class="hidden lg:flex lg:flex-col shrink-0 bg-white/80 dark:bg-gray-900/80 border-r border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm z-10">
    <nav class="w-60 flex flex-col h-full">
        {{-- ロゴ --}}
        <div class="px-6 py-6 border-b border-gray-200/50 dark:border-gray-700/50">
            <a href="{{ route('dashboard') }}">
                <x-application-logo />
            </a>
        </div>

        {{-- ナビゲーションリンク --}}
        <div class="flex flex-col space-y-2 px-3 mt-6">
            <x-nav-link 
                :href="route('dashboard')" 
                :active="request()->routeIs('dashboard')" 
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-[#59B9C6]/10 hover:to-purple-500/5 transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'active bg-gradient-to-r from-[#59B9C6]/10 to-purple-500/5 text-[#59B9C6]' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="text-sm font-medium flex-1">タスクリスト</span>
                <span class="badge-gradient inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold shadow-sm">
                    {{ $sidebarTaskTotal }}
                </span>
            </x-nav-link>

            @if(Auth::user()->canEditGroup())
                <x-nav-link 
                    :href="route('tasks.pending-approvals')" 
                    :active="request()->routeIs('tasks.pending-approvals')" 
                    class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500/10 hover:to-orange-500/5 transition-all duration-200 group {{ request()->routeIs('tasks.pending-approvals') ? 'active bg-gradient-to-r from-yellow-500/10 to-orange-500/5 text-yellow-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium flex-1">承認待ち</span>
                    @if($sidebarPendingTotal > 0)
                        <span class="badge-warning inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold shadow-sm animate-pulse">
                            {{ $sidebarPendingTotal }}
                        </span>
                    @endif
                </x-nav-link>
            @endif

            <x-nav-link 
                :href="route('tags.list')" 
                :active="request()->routeIs('tags.list')" 
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-500/10 hover:to-purple-500/5 transition-all duration-200 group {{ request()->routeIs('tags.list') ? 'active bg-gradient-to-r from-blue-500/10 to-purple-500/5 text-blue-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium">タグ管理</span>
            </x-nav-link>

            <x-nav-link 
                :href="route('reports.performance')"
                :active="request()->routeIs('reports.performance')"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-green-500/10 hover:to-emerald-500/5 transition-all duration-200 group {{ request()->routeIs('reports.performance') ? 'active bg-gradient-to-r from-green-500/10 to-emerald-500/5 text-green-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-sm font-medium">実績</span>
            </x-nav-link>

            <x-nav-link 
                :href="route('profile.edit')" 
                :active="request()->routeIs('profile.edit')"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-gray-500/10 hover:to-gray-400/5 transition-all duration-200 group {{ request()->routeIs('profile.edit') ? 'active bg-gradient-to-r from-gray-500/10 to-gray-400/5 text-gray-700' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm font-medium">設定</span>
            </x-nav-link>
        </div>
    </nav>
</aside>

{{-- モバイル: オフキャンバスサイドバー --}}
<div class="lg:hidden">
    {{-- オーバーレイ --}}
    <div 
        x-show="showSidebar"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
        style="pointer-events: auto;"
        @click.stop="showSidebar = false"
        @touchstart.stop="showSidebar = false">
    </div>

    {{-- サイドバー本体 --}}
    <aside
        x-show="showSidebar"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-gray-900 shadow-2xl overflow-y-auto"
        @click.stop>
        
        <div class="flex items-center justify-between px-6 py-6 border-b border-gray-200 dark:border-gray-700">
            <x-application-logo />
            <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition" @click="showSidebar = false">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex flex-col space-y-2 px-3 mt-6 pb-6">
            <x-nav-link 
                :href="route('dashboard')" 
                :active="request()->routeIs('dashboard')" 
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-[#59B9C6]/10 hover:to-purple-500/5 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'active bg-gradient-to-r from-[#59B9C6]/10 to-purple-500/5 text-[#59B9C6]' : '' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="text-sm font-medium flex-1">タスクリスト</span>
                <span class="badge-gradient inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold">
                    {{ $sidebarTaskTotal }}
                </span>
            </x-nav-link>

            @if(Auth::user()->canEditGroup())
                <x-nav-link 
                    :href="route('tasks.pending-approvals')" 
                    :active="request()->routeIs('tasks.pending-approvals')" 
                    @click="showSidebar = false"
                    class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500/10 hover:to-orange-500/5 transition-all duration-200 {{ request()->routeIs('tasks.pending-approvals') ? 'active bg-gradient-to-r from-yellow-500/10 to-orange-500/5 text-yellow-600' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium flex-1">承認待ち</span>
                    @if($sidebarPendingTotal > 0)
                        <span class="badge-warning inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold">
                            {{ $sidebarPendingTotal }}
                        </span>
                    @endif
                </x-nav-link>
            @endif

            <x-nav-link 
                :href="route('tags.list')" 
                :active="request()->routeIs('tags.list')" 
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-500/10 hover:to-purple-500/5 transition-all duration-200 {{ request()->routeIs('tags.list') ? 'active bg-gradient-to-r from-blue-500/10 to-purple-500/5 text-blue-600' : '' }}"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium">タグ管理</span>
            </x-nav-link>

            <x-nav-link 
                :href="route('reports.performance')"
                :active="request()->routeIs('reports.performance')"
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-green-500/10 hover:to-emerald-500/5 transition-all duration-200 {{ request()->routeIs('reports.performance') ? 'active bg-gradient-to-r from-green-500/10 to-emerald-500/5 text-green-600' : '' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-sm font-medium">実績</span>
            </x-nav-link>

            <x-nav-link 
                :href="route('profile.edit')" 
                :active="request()->routeIs('profile.edit')"
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-gray-500/10 hover:to-gray-400/5 transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'active bg-gradient-to-r from-gray-500/10 to-gray-400/5' : '' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm font-medium">設定</span>
            </x-nav-link>
        </nav>
    </aside>
</div>