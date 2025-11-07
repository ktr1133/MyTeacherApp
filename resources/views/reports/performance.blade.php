<x-app-layout>
    @push('styles')
        @vite(['resources/css/reports/performance.css'])
    @endpush

    <div x-data="performanceReport(@js($tab), @js($period), @js($offset))" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-[100dvh] performance-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl performance-floating-decoration"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl performance-floating-decoration" style="animation-delay: -10s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <main class="flex-1 overflow-hidden relative z-10">
            
            {{-- モバイルヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 performance-header-blur shadow-sm lg:hidden">
                <div class="px-4 h-14 flex items-center justify-between gap-3">
                    <button 
                        @click="showSidebar = !showSidebar"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        aria-label="Toggle menu">
                        <svg class="w-6 h-6 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-2">
                        <div class="performance-header-icon w-8 h-8 rounded-lg flex items-center justify-center shadow-lg">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h1 class="performance-header-title text-base font-bold">実績</h1>
                    </div>

                    <div class="w-10"></div>
                </div>
            </header>

            <div class="h-full flex flex-col px-4 lg:px-6 py-4 lg:py-6">
                
                {{-- デスクトップヘッダー --}}
                <div class="performance-header-blur border-b border-gray-200/50 dark:border-gray-700/50 pb-4 mb-6 shrink-0 hidden lg:block">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent mb-1">
                                実績ダッシュボード
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400">タスクの進捗状況を可視化</p>
                        </div>
                    </div>
                </div>

                {{-- タブナビゲーション（通常タスク/グループタスク） --}}
                <div class="flex gap-3 mb-6 shrink-0">
                    <a href="?tab=normal&period={{ $period }}&offset={{ $offset }}"
                       class="performance-tab {{ $tab === 'normal' ? 'performance-tab-active performance-tab-normal' : 'performance-tab-inactive' }} px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold text-xs sm:text-sm transition-all shadow-md flex-1 sm:flex-none">
                        <svg class="w-4 h-4 inline-block mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="hidden sm:inline">通常タスク</span>
                        <span class="sm:hidden">通常</span>
                    </a>
                    <a href="?tab=group&period={{ $period }}&offset={{ $offset }}"
                       class="performance-tab {{ $tab === 'group' ? 'performance-tab-active performance-tab-group' : 'performance-tab-inactive' }} px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold text-xs sm:text-sm transition-all shadow-md flex-1 sm:flex-none">
                        <svg class="w-4 h-4 inline-block mr-1 sm:mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                        <span class="hidden sm:inline">グループタスク</span>
                        <span class="sm:hidden">グループ</span>
                    </a>
                </div>

                {{-- グループタスクのメンバー選択 --}}
                @if ($tab === 'group' && $members->isNotEmpty())
                    <div class="mb-6 shrink-0">
                        <label for="user-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                            メンバー選択
                        </label>
                        <select 
                            id="user-select" 
                            onchange="window.location.href='?tab=group&period={{ $period }}&offset={{ $offset }}&user_id=' + this.value"
                            class="w-full max-w-xs text-sm border-purple-200 dark:border-purple-700 rounded-lg shadow-sm focus:border-purple-600 focus:ring-2 focus:ring-purple-600/50 bg-white dark:bg-gray-800 pr-8">
                            <option value="0" {{ $isGroupWhole ? 'selected' : '' }}>グループ全体</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ $targetUser && $targetUser->id === $member->id ? 'selected' : '' }}>
                                    {{ $member->username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- 期間区分選択（週間/月間/年間） --}}
                <div class="flex gap-3 mb-6 shrink-0">
                    <a href="?tab={{ $tab }}&period=week&offset=0{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="period-button {{ $period === 'week' ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        週間
                    </a>
                    <a href="?tab={{ $tab }}&period=month&offset=0{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="period-button {{ $period === 'month' ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        月間
                    </a>
                    <a href="?tab={{ $tab }}&period=year&offset=0{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="period-button {{ $period === 'year' ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        年間
                    </a>
                </div>

                @php
                    $currentData = $tab === 'normal' ? $normalData : $groupData;
                    $periodInfo = $currentData['periodInfo'];
                @endphp

                {{-- 期間ナビゲーション --}}
                <div class="flex items-center justify-between mb-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 shrink-0">
                    <a href="?tab={{ $tab }}&period={{ $period }}&offset={{ $offset - 1 }}{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="nav-button {{ !$periodInfo['canGoPrevious'] ? 'disabled' : '' }}"
                       @if(!$periodInfo['canGoPrevious']) onclick="event.preventDefault(); return false;" @endif>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="hidden sm:inline">前へ</span>
                    </a>

                    <div class="period-display">
                        <svg class="w-5 h-5 text-{{ $tab === 'normal' ? '[#59B9C6]' : 'purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">{{ $periodInfo['displayText'] }}</span>
                    </div>

                    <a href="?tab={{ $tab }}&period={{ $period }}&offset={{ $offset + 1 }}{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="nav-button {{ !$periodInfo['canGoNext'] ? 'disabled' : '' }}"
                       @if(!$periodInfo['canGoNext']) onclick="event.preventDefault(); return false;" @endif>
                        <span class="hidden sm:inline">次へ</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                {{-- グラフ表示エリア（大きく1つ表示） --}}
                <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                    <div class="chart-container-large {{ $tab === 'normal' ? 'chart-normal' : 'chart-group' }}">
                        <div class="chart-header">
                            <h3 class="chart-title">
                                @if($tab === 'normal')
                                    <svg class="w-5 h-5 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    通常タスク -
                                @else
                                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    グループタスク -
                                @endif
                                {{ $periodInfo['displayText'] }}
                            </h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="performance-chart"></canvas>
                        </div>
                        <div class="chart-footer">
                            <x-reports.totals :data="$currentData" :kind="$tab === 'normal' ? 'normal' : 'group'"/>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    @push('scripts')
        @vite(['resources/js/reports/performance.js'])
        <script>
            window.performanceData = {
                tab: @js($tab),
                period: @js($period),
                currentData: @js($currentData),
            };
        </script>
    @endpush
</x-app-layout>