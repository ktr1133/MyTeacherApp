@php
    $u = Auth::user();
    $sidebarTaskTotal = \App\Models\Task::where('user_id', $u->id)
        ->where('is_completed', false)
        ->count();
    $sidebarPendingTotal = 0;
    if ($u->canEditGroup()) {
        $sidebarPendingTotal = \App\Models\Task::query()
            ->where('requires_approval', true)
            ->where('is_completed', true)
            ->whereNull('approved_at')
            ->where('assigned_by_user_id', $u->id)
            ->count();
    }
    
    // トークン残高を取得
    $tokenBalance = $u->getOrCreateTokenBalance();
    $isLowBalance = $tokenBalance->balance <= config('const.token.low_threshold', 200000);
@endphp

{{-- デスクトップ: 左固定サイドバー --}}
<aside 
    x-data="{ collapsed: $store.sidebar?.isCollapsed || false }"
    x-init="$watch('$store.sidebar.isCollapsed', value => collapsed = value)"
    :class="collapsed ? 'justify-center p-3' : 'gap-3 px-3 py-2'"
    data-sidebar="desktop"
    class="hidden lg:flex lg:flex-col shrink-0 bg-white/80 dark:bg-gray-900/80 border-r border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm z-10 transition-all duration-300 overflow-hidden">
    
    <nav class="flex flex-col h-full">
        {{-- ロゴ + トグルボタン --}}
        <div class="px-2 py-2 border-b border-gray-200/50 dark:border-gray-700/50 flex items-center justify-between">
            {{-- トグルボタン（左側に配置） --}}
            <button 
                @click="$store.sidebar.toggle()"
                data-sidebar-action="toggle-desktop"
                :aria-label="collapsed ? 'サイドバーを展開' : 'サイドバーを最小化'"
                :aria-expanded="!collapsed"
                class="mr-4 group relative flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6]/10 to-purple-600/10 hover:from-[#59B9C6]/20 hover:to-purple-600/20 border border-[#59B9C6]/20 hover:border-[#59B9C6]/30 transition-all duration-200 shrink-0"
            >
                {{-- 展開時: 左向き二重矢印 --}}
                <svg x-show="!collapsed" 
                     class="w-5 h-5 text-[#59B9C6] group-hover:text-[#4A9AA5] transition-colors" 
                     fill="none" 
                     stroke="currentColor" 
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                {{-- 最小化時: 右向き二重矢印 --}}
                <svg x-show="collapsed" 
                     class="w-5 h-5 text-[#59B9C6] group-hover:text-[#4A9AA5] transition-colors" 
                     fill="none" 
                     stroke="currentColor" 
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>
            
            {{-- ロゴ（展開時のみ表示） --}}
            <a href="{{ route('dashboard') }}" 
               x-show="!collapsed" 
               x-transition:enter="transition ease-out duration-300 delay-100"
               x-transition:enter-start="opacity-0 -translate-x-4"
               x-transition:enter-end="opacity-100 translate-x-0"
               x-transition:leave="transition ease-in duration-200"
               x-transition:leave-start="opacity-100 translate-x-0"
               x-transition:leave-end="opacity-0 -translate-x-4"
               class="flex-1">
                <x-application-logo />
            </a>
        </div>

        {{-- ナビゲーションリンク --}}
        <div class="flex flex-col space-y-2 px-3 mt-6 flex-1 overflow-y-auto">
            {{-- タスクリスト --}}
            <x-nav-link 
                :href="route('dashboard')" 
                :active="request()->routeIs('dashboard')" 
                class="sidebar-nav-link flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-[#59B9C6]/10 hover:to-purple-500/5 transition-all duration-200 group relative {{ request()->routeIs('dashboard') ? 'active bg-gradient-to-r from-[#59B9C6]/10 to-purple-500/5 text-[#59B9C6]' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                    @if(!$isChildTheme)
                        タスクリスト
                    @else
                        ToDo
                    @endif

                </span>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="badge-gradient inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold shadow-sm">
                    {{ $sidebarTaskTotal }}
                </span>
            </x-nav-link>

            {{-- 承認待ち --}}
            @if($u->canEditGroup())
                <x-nav-link 
                    :href="route('tasks.pending-approvals')" 
                    :active="request()->routeIs('tasks.pending-approvals')" 
                    class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500/10 hover:to-orange-500/5 transition-all duration-200 group relative {{ request()->routeIs('tasks.pending-approvals') ? 'active bg-gradient-to-r from-yellow-500/10 to-orange-500/5 text-yellow-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="!collapsed" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                        承認待ち
                    </span>
                    @if($sidebarPendingTotal > 0)
                        <span x-show="!collapsed" 
                              x-transition:enter="transition ease-out duration-200"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition ease-in duration-150"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="badge-warning inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold shadow-sm animate-pulse">
                            {{ $sidebarPendingTotal }}
                        </span>
                    @endif
                </x-nav-link>
            @endif

            {{-- タグ管理 --}}
            <x-nav-link 
                :href="route('tags.list')" 
                :active="request()->routeIs('tags.list')" 
                class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-500/10 hover:to-purple-500/5 transition-all duration-200 group relative {{ request()->routeIs('tags.list') ? 'active bg-gradient-to-r from-blue-500/10 to-purple-500/5 text-blue-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                    @if(!$isChildTheme)
                        タグ管理
                    @else
                        タグ
                    @endif
                </span>
            </x-nav-link>

            {{-- 教師アバター設定 --}}
            <x-nav-link 
                :href="route('avatars.edit')" 
                :active="request()->routeIs('avatars.*')"
                class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-pink-500/10 hover:to-rose-500/5 transition-all duration-200 group relative {{ request()->routeIs('avatars.*') ? 'active bg-gradient-to-r from-pink-500/10 to-rose-500/5 text-pink-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                    @if(!$isChildTheme)
                        教師アバター
                    @else
                        サポートアバター
                    @endif
                </span>
            </x-nav-link>

            {{-- 実績 --}}
            <x-nav-link 
                :href="route('reports.performance')"
                :active="request()->routeIs('reports.performance')"
                class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-green-500/10 hover:to-emerald-500/5 transition-all duration-200 group relative {{ request()->routeIs('reports.performance') ? 'active bg-gradient-to-r from-green-500/10 to-emerald-500/5 text-green-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                    実績
                </span>                
            </x-nav-link>

            {{-- トークン購入リンク --}}
            <x-nav-link 
                :href="route('tokens.purchase')" 
                :active="request()->routeIs('tokens.purchase', 'tokens.history')"
                class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-amber-500/10 hover:to-yellow-500/5 transition-all duration-200 group relative {{ request()->routeIs('tokens.purchase', 'tokens.history') ? 'active bg-gradient-to-r from-amber-500/10 to-yellow-500/5 text-amber-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                    @if(!$isChildTheme)
                        トークン
                    @else
                        コイン
                    @endif
                </span>
                @if($isLowBalance)
                    <span x-show="!collapsed" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="inline-flex items-center justify-center w-2 h-2 bg-red-500 rounded-full animate-pulse">
                    </span>
                @endif
            </x-nav-link>


            {{-- 管理者メニュー --}}
            @if($u->isAdmin())
                <div class="pt-4 pb-2 px-4">
                    <div x-show="!collapsed" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        管理者メニュー
                    </div>
                    <div x-show="collapsed" class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                </div>

                {{-- ユーザー管理 --}}
                <x-nav-link 
                    :href="route('admin.users.index')" 
                    :active="request()->routeIs('admin.users.*')"
                    class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500/10 hover:to-purple-500/5 transition-all duration-200 group relative {{ request()->routeIs('admin.users.*') ? 'active bg-gradient-to-r from-indigo-500/10 to-purple-500/5 text-indigo-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span x-show="!collapsed" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                        ユーザー管理
                    </span>
                </x-nav-link>

                {{-- トークンパッケージ --}}
                <x-nav-link 
                    :href="route('admin.token-packages')" 
                    :active="request()->routeIs('admin.token-packages*')"
                    class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-amber-500/10 hover:to-yellow-500/5 transition-all duration-200 group relative {{ request()->routeIs('admin.token-packages*') ? 'active bg-gradient-to-r from-amber-500/10 to-yellow-500/5 text-amber-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                    <span x-show="!collapsed" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                        パッケージ設定
                    </span>
                </x-nav-link>

                {{-- トークン統計 --}}
                <x-nav-link 
                    :href="route('admin.token-stats')" 
                    :active="request()->routeIs('admin.token-stats')"
                    class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-purple-500/10 hover:to-pink-500/5 transition-all duration-200 group relative {{ request()->routeIs('admin.token-stats') ? 'active bg-gradient-to-r from-purple-500/10 to-pink-500/5 text-purple-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span x-show="!collapsed" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                        トークン統計
                    </span>
                </x-nav-link>

                {{-- 課金履歴 --}}
                <x-nav-link 
                    :href="route('admin.payment-history')" 
                    :active="request()->routeIs('admin.payment-history')"
                    class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-teal-500/10 hover:to-cyan-500/5 transition-all duration-200 group relative {{ request()->routeIs('admin.payment-history') ? 'active bg-gradient-to-r from-teal-500/10 to-cyan-500/5 text-teal-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span x-show="!collapsed" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                        課金履歴
                    </span>
                </x-nav-link>
            @endif

            {{-- 設定(アカウント管理) --}}
            <div class="pt-2"></div>
            <x-nav-link 
                :href="route('profile.edit')" 
                :active="request()->routeIs('profile.edit')"
                class="sidebar-nav-link flex items-center gap-3 px-2.5 py-2 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-gray-500/10 hover:to-gray-400/5 transition-all duration-200 group relative {{ request()->routeIs('profile.edit') ? 'active bg-gradient-to-r from-gray-500/10 to-gray-400/5 text-gray-700' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="!collapsed" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis flex-1">
                    設定
                </span>
            </x-nav-link>
        </div>

        {{-- トークン残高表示 --}}
        <div class="px-3 pb-6 shrink-0">
            {{-- 展開時 --}}
            <div x-show="!collapsed" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-700/30">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ !$isChildTheme ? 'トークン' : 'コイン' }}残高</span>
                </div>
                <div class="text-2xl font-bold text-amber-900 dark:text-amber-100 mb-1">
                    {{ number_format($tokenBalance->balance) }}
                </div>
                <div class="text-xs text-amber-600 dark:text-amber-400">
                    無料: {{ number_format($tokenBalance->free_balance) }} / 有料: {{ number_format($tokenBalance->paid_balance) }}
                </div>
                @if($isLowBalance)
                    <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700/30">
                        <a href="{{ route('tokens.purchase') }}" class="text-xs text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 font-medium flex items-center gap-1 group">
                            <svg class="w-3 h-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ !$isChildTheme ? 'トークン' : 'コイン' }}購入
                        </a>
                    </div>
                @endif
            </div>
            
            {{-- 最小化時 --}}
            <div x-show="collapsed" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl p-2 border border-amber-200 dark:border-amber-700/30 text-center">
                <svg class="w-6 h-6 mx-auto text-amber-600 dark:text-amber-400 mb-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                </svg>
                <div class="text-xs font-bold text-amber-900 dark:text-amber-100">
                    @php
                        $balanceK = number_format($tokenBalance->balance / 1000, 0);
                    @endphp
                    {{ $balanceK }}K
                </div>
            </div>
        </div>
    </nav>
</aside>

{{-- モバイル: オフキャンバスサイドバー --}}
<div class="lg:hidden">
    {{-- オーバーレイ --}}
    <div 
        x-show="showSidebar"
        x-transition.opacity
        data-sidebar-overlay="mobile"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
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
        data-sidebar="mobile"
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
            {{-- タスクリスト --}}
            <x-nav-link 
                :href="route('dashboard')" 
                :active="request()->routeIs('dashboard')" 
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-[#59B9C6]/10 hover:to-purple-500/5 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'active bg-gradient-to-r from-[#59B9C6]/10 to-purple-500/5 text-[#59B9C6]' : '' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                @if(!$isChildTheme)
                    <span class="text-sm font-medium flex-1">タスクリスト</span>
                @else
                    <span class="text-sm font-medium flex-1">ToDo</span>
                @endif
                <span class="badge-gradient inline-flex items-center justify-center min-w-[1.75rem] h-6 px-2 rounded-full text-white text-xs font-bold">
                    {{ $sidebarTaskTotal }}
                </span>
            </x-nav-link>

            {{-- 承認待ち --}}
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

            {{-- タグ管理 --}}
            <x-nav-link 
                :href="route('tags.list')" 
                :active="request()->routeIs('tags.list')" 
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-blue-500/10 hover:to-purple-500/5 transition-all duration-200 {{ request()->routeIs('tags.list') ? 'active bg-gradient-to-r from-blue-500/10 to-purple-500/5 text-blue-600' : '' }}"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                @if(!$isChildTheme)
                    <span class="text-sm font-medium">タグ管理</span>
                @else
                    <span class="text-sm font-medium">タグ</span>
                @endif
            </x-nav-link>

            {{-- 教師アバター設定 --}}
            <x-nav-link 
                :href="route('avatars.edit')" 
                :active="request()->routeIs('avatars.*')"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-pink-500/10 hover:to-rose-500/5 transition-all duration-200 group {{ request()->routeIs('avatars.*') ? 'active bg-gradient-to-r from-pink-500/10 to-rose-500/5 text-pink-600' : '' }}"
            >
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                @if(!$isChildTheme)
                    <span class="text-sm font-medium">教師アバター</span>
                @else
                    <span class="text-sm font-medium">サポートアバター</span>
                @endif
            </x-nav-link>

            {{-- 実績 --}}
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

            {{-- トークン購入リンク（モバイル） --}}
            <x-nav-link 
                :href="route('tokens.purchase')" 
                :active="request()->routeIs('tokens.purchase', 'tokens.history')"
                @click="showSidebar = false"
                class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-amber-500/10 hover:to-yellow-500/5 transition-all duration-200 {{ request()->routeIs('tokens.purchase', 'tokens.history') ? 'active bg-gradient-to-r from-amber-500/10 to-yellow-500/5 text-amber-600' : '' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @if(!$isChildTheme)
                    <span class="text-sm font-medium flex-1">トークン</span>
                @else
                    <span class="text-sm font-medium">コイン</span>
                @endif
                @if($isLowBalance)
                    <span class="inline-flex items-center justify-center w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                @endif
            </x-nav-link>

            {{-- 管理者メニュー --}}
            @if($u->isAdmin())
                <div class="pt-4 pb-2 px-4">
                    <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        管理者メニュー
                    </div>
                </div>

                {{-- ユーザー管理 --}}
                <x-nav-link 
                    :href="route('admin.users.index')" 
                    :active="request()->routeIs('admin.users.*')"
                    class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500/10 hover:to-purple-500/5 transition-all duration-200 group {{ request()->routeIs('admin.users.*') ? 'active bg-gradient-to-r from-indigo-500/10 to-purple-500/5 text-indigo-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span class="text-sm font-medium">ユーザー管理</span>
                </x-nav-link>


                {{-- トークンパッケージ --}}
                <x-nav-link 
                    :href="route('admin.token-packages')" 
                    :active="request()->routeIs('admin.token-packages', 'admin.token-packages-create', 'admin.token-packages-edit', 'admin.token-packages-delete')"
                    class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-amber-500/10 hover:to-yellow-500/5 transition-all duration-200 group {{ request()->routeIs('admin.token-packages') ? 'active bg-gradient-to-r from-amber-500/10 to-yellow-500/5 text-amber-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">パッケージ設定</span>
                </x-nav-link>

                {{-- トークン統計 --}}
                <x-nav-link 
                    :href="route('admin.token-stats')" 
                    :active="request()->routeIs('admin.token-stats')"
                    class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-purple-500/10 hover:to-pink-500/5 transition-all duration-200 group {{ request()->routeIs('admin.token-stats') ? 'active bg-gradient-to-r from-purple-500/10 to-pink-500/5 text-purple-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ !$isChildTheme ? 'トークン' : 'コイン' }}統計</span>
                </x-nav-link>

                {{-- 課金履歴 --}}
                <x-nav-link 
                    :href="route('admin.payment-history')" 
                    :active="request()->routeIs('admin.payment-history')"
                    class="sidebar-nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-teal-500/10 hover:to-cyan-500/5 transition-all duration-200 group {{ request()->routeIs('admin.payment-history') ? 'active bg-gradient-to-r from-teal-500/10 to-cyan-500/5 text-teal-600' : '' }}"
                >
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="text-sm font-medium">課金履歴</span>
                </x-nav-link>
            @endif

            {{-- 設定(アカウント管理) --}}
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

        {{-- トークン残高表示（モバイル） --}}
        <div class="px-3 pb-6">
            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-700/30">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ !$isChildTheme ? 'トークン' : 'コイン' }}残高</span>
                </div>
                <div class="text-2xl font-bold text-amber-900 dark:text-amber-100 mb-1">
                    {{ number_format($tokenBalance->balance) }}
                </div>
                <div class="text-xs text-amber-600 dark:text-amber-400">
                    無料: {{ number_format($tokenBalance->free_balance) }} / 有料: {{ number_format($tokenBalance->paid_balance) }}
                </div>
                @if($isLowBalance)
                    <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700/30">
                        <a href="{{ route('tokens.purchase') }}" @click="showSidebar = false" class="text-xs text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 font-medium flex items-center gap-1 group">
                            <svg class="w-3 h-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ !$isChildTheme ? 'トークン' : 'コイン' }}購入
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </aside>
</div>