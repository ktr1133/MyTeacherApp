
<x-app-layout>
    @push('styles')
        @vite(['resources/css/reports/performance.css', 'resources/css/dashboard.css'])
    @endpush

    @php
        $currentData = $tab === 'normal' ? $normalData : $groupData;
    @endphp

    {{-- Alpine起動前にperformance.jsを読み込む --}}
    @push('scripts')
        @vite(['resources/js/reports/performance.js'])
        <script>
            window.performanceData = {
                tab: @json($tab),
                period: @json($period),
                currentData: @json($currentData),
                // アバター用データ追加
                normalData: @json($normalData),
                groupData: @json($groupData)
            };
            
            // 累積データの確認ログ（デバッグ用）
            console.log('[Performance] Current data:', window.performanceData.currentData);
            
            @if($tab === 'normal')
                console.log('[Performance] Normal task cumulative:', window.performanceData.currentData.nCum);
            @else
                console.log('[Performance] Group task cumulative:', window.performanceData.currentData.gCum);
                console.log('[Performance] Reward cumulative:', window.performanceData.currentData.gRewardCum);
            @endif
        </script>
    @endpush

    <div x-data="performanceReport(@js($tab), @js($period), @js($offset))" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-screen max-h-screen performance-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾（大人用のみ） --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 pointer-events-none z-0">
                <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl performance-floating-decoration"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl performance-floating-decoration" style="animation-delay: -10s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
            
            {{-- ヘッダー --}}
            <header class="shrink-0 border-b border-gray-200/50 dark:border-gray-700/50 performance-header-blur shadow-sm">
                <div class="px-3 py-3 sticky top-0 z-10 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm {{ $isChildTheme ? 'child-theme' : '' }}" >
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            @click="toggleSidebar()"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="performance-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="performance-header-title text-base lg:text-lg font-bold">
                                    @if(!$isChildTheme)
                                        実績ダッシュボード
                                    @else
                                        実績
                                    @endif
                                </h1>
                                @if(!$isChildTheme)
                                    <p class="performance-header-subtitle hidden sm:block text-xs text-gray-600 dark:text-gray-400">タスクの進捗状況を可視化</p>
                                @endif
                            </div>
                        </div>

                        {{-- 期間区分選択（Weekly/Monthly/Yearly） --}}
                        <div class="flex gap-2 shrink-0 flex-wrap">
                            <a href="?tab={{ $tab }}&period=week&offset=0{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                            class="period-button {{ $period === 'week' ? 'active' : '' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                @if(!$isChildTheme)
                                    週間
                                @else
                                    Weekly
                                @endif
                            </a>
                            <a href="?tab={{ $tab }}&period=month&offset=0{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                            class="period-button {{ $period === 'month' ? 'active' : '' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                @if(!$isChildTheme)
                                    月間
                                @else
                                    Monthly
                                @endif
                            </a>
                            <a href="?tab={{ $tab }}&period=year&offset=0{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                            class="period-button {{ $period === 'year' ? 'active' : '' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                @if(!$isChildTheme)
                                    年間
                                @else
                                    Yearly
                                @endif
                            </a>
                        </div>
                    </div>
                </div>

                {{-- タブナビゲーション（やること/クエスト） --}}
                <div class="flex gap-2 border-t border-gray-200 dark:border-gray-700 px-4 lg:px-6 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
                    <a href="?tab=normal&period={{ $period }}&offset={{ $offset }}"
                       :class="'{{ $tab }}' === 'normal' ? 'performance-tab performance-tab-normal performance-tab-active' : 'performance-tab performance-tab-inactive'"
                       class="px-3 py-2 lg:px-4 lg:py-3 border-b-2 font-semibold text-xs lg:text-sm transition">
                        @if(!$isChildTheme)
                            通常タスク
                        @else
                            やること
                        @endif
                    </a>
                    <a href="?tab=group&period={{ $period }}&offset={{ $offset }}"
                       :class="'{{ $tab }}' === 'group' ? 'performance-tab performance-tab-group performance-tab-active' : 'performance-tab performance-tab-inactive'"
                       class="px-3 py-2 lg:px-4 lg:py-3 border-b-2 font-semibold text-xs lg:text-sm transition">
                        @if(!$isChildTheme)
                            グループタスク
                        @else
                            クエスト
                        @endif
                    </a>
                </div>
            </header>

            {{-- メインコンテンツエリア --}}
            <main class="flex-1 flex flex-col overflow-hidden px-3 lg:px-6 py-3 lg:py-4 gap-3">
                {{-- グループタスクのメンバー選択 --}}
                @if ($tab === 'group' && $members->isNotEmpty())
                    <div class="shrink-0">
                        <label for="user-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                            @if(!$isChildTheme)
                                メンバー選択
                            @else
                                だれのグラフをみる？
                            @endif
                        </label>
                        <select 
                            id="user-select" 
                            onchange="window.location.href='?tab=group&period={{ $period }}&offset={{ $offset }}&user_id=' + this.value"
                            class="w-full max-w-xs text-sm border-purple-200 dark:border-purple-700 rounded-lg shadow-sm focus:border-purple-600 focus:ring-2 focus:ring-purple-600/50 bg-white dark:bg-gray-800 pr-8">
                            <option value="0" {{ $isGroupWhole ? 'selected' : '' }}>
                                @if(!$isChildTheme)
                                    グループ全体
                                @else
                                    みんな
                                @endif
                            </option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ $targetUser && $targetUser->id === $member->id ? 'selected' : '' }}>
                                    {{ $member->username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @php
                    $periodInfo = $currentData['periodInfo'];
                @endphp

                {{-- 期間ナビゲーション --}}
                <div class="flex items-center justify-between bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-3 shadow-sm border border-gray-200 dark:border-gray-700 shrink-0">
                    <a href="?tab={{ $tab }}&period={{ $period }}&offset={{ $offset - 1 }}{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="nav-button {{ !$periodInfo['canGoPrevious'] ? 'disabled' : '' }}"
                       @if(!$periodInfo['canGoPrevious']) onclick="event.preventDefault(); return false;" @endif>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="hidden sm:inline">
                            @if(!$isChildTheme)
                                前へ
                            @else
                                まえ
                            @endif
                        </span>
                    </a>

                    <div class="period-display">
                        <svg class="w-4 h-4 {{ $tab === 'normal' ? 'text-[#59B9C6]' : 'text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">{{ $periodInfo['displayText'] }}</span>
                    </div>

                    <a href="?tab={{ $tab }}&period={{ $period }}&offset={{ $offset + 1 }}{{ $tab === 'group' && !$isGroupWhole ? '&user_id=' . $targetUser->id : '' }}"
                       class="nav-button {{ !$periodInfo['canGoNext'] ? 'disabled' : '' }}"
                       @if(!$periodInfo['canGoNext']) onclick="event.preventDefault(); return false;" @endif>
                        <span class="hidden sm:inline">
                            @if(!$isChildTheme)
                                次へ
                            @else
                                つぎ
                            @endif
                        </span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                {{-- グラフ表示エリア（画面内に収まるように調整） --}}
                <div class="flex-1 min-h-0">
                    <div class="chart-container-large {{ $tab === 'normal' ? 'chart-normal' : 'chart-group' }} h-full flex flex-col">
                        <div class="chart-header">
                            <h3 class="chart-title">
                                @if($tab === 'normal')
                                    @if(!$isChildTheme)
                                        通常タスク - {{ $periodInfo['displayText'] }}
                                    @else
                                        やること - {{ $periodInfo['displayText'] }}
                                    @endif
                                @else
                                    @if(!$isChildTheme)
                                        グループタスク - {{ $periodInfo['displayText'] }}
                                    @else
                                        クエスト - {{ $periodInfo['displayText'] }}
                                    @endif
                                @endif
                            </h3>
                        </div>
                        <div class="chart-body flex-1 min-h-0">
                            <canvas id="performance-chart"></canvas>
                        </div>
                        <div class="chart-footer">
                            <x-reports.totals :data="$currentData" :kind="$tab === 'normal' ? 'normal' : 'group'"/>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>