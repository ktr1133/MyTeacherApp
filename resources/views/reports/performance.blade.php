<x-app-layout>
    @push('styles')
        @vite(['resources/css/reports/performance.css'])
    @endpush

    <div x-data="{ 
        activeTab: '{{ request()->get('tab', 'normal') }}',
        showSidebar: false 
    }" class="flex min-h-[100dvh] performance-gradient-bg relative overflow-hidden">
        {{-- 背景装飾: z-0 --}}
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl performance-floating-decoration"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl performance-floating-decoration" style="animation-delay: -10s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ: z-10 --}}
        <main class="flex-1 overflow-hidden relative z-10">
            {{-- ヘッダー（モバイルメニューボタン付き） --}}
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

                    <div class="w-10"></div> {{-- バランス用の空要素 --}}
                </div>
            </header>

            <div class="h-full flex flex-col px-4 lg:px-6 py-4 lg:py-6" 
                 x-data="{ activeTab: '{{ request()->get('tab', 'normal') }}' }">
                
                {{-- ヘッダー（デスクトップ） --}}
                <div class="performance-header-blur border-b border-gray-200/50 dark:border-gray-700/50 pb-4 mb-6 shrink-0 hidden lg:block">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent mb-1">
                                実績ダッシュボード
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400">タスクの進捗状況を可視化</p>
                        </div>
                        
                        {{-- メンバー選択 --}}
                        <div x-show="activeTab === 'group'" x-transition>
                            @if(Auth::user()->canEditGroup() && $members->isNotEmpty())
                                <form method="GET" action="{{ route('reports.performance') }}" class="flex items-center gap-2">
                                    <input type="hidden" name="tab" value="group">
                                    <label for="user-select" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                        メンバー:
                                    </label>
                                    <select 
                                        id="user-select" 
                                        name="user_id" 
                                        onchange="this.form.submit()"
                                        class="text-sm border-purple-200 dark:border-purple-700 rounded-lg shadow-sm focus:border-purple-600 focus:ring-2 focus:ring-purple-600/50 bg-white dark:bg-gray-800 pr-8">
                                        <option value="0" {{ $isGroupWhole ? 'selected' : '' }}>グループ全体</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->id }}" {{ $targetUser && $targetUser->id === $member->id ? 'selected' : '' }}>
                                                {{ $member->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <div class="inline-flex items-center px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg border border-purple-200 dark:border-purple-700">
                                    <svg class="w-4 h-4 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $targetUser->username ?? Auth::user()->username }}</span>
                                </div>
                            @endif
                        </div>
                        <div x-show="activeTab === 'normal'" x-transition>
                            <div class="inline-flex items-center px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg border border-[#59B9C6]/30">
                                <svg class="w-4 h-4 mr-2 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $normalUser->username }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- モバイル用メンバー選択 --}}
                <div class="lg:hidden mb-4 shrink-0">
                    <div x-show="activeTab === 'group'" x-transition>
                        @if(Auth::user()->canEditGroup() && $members->isNotEmpty())
                            <form method="GET" action="{{ route('reports.performance') }}" class="w-full">
                                <input type="hidden" name="tab" value="group">
                                <label for="user-select-mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    メンバー選択
                                </label>
                                <select 
                                    id="user-select-mobile" 
                                    name="user_id" 
                                    onchange="this.form.submit()"
                                    class="w-full text-sm border-purple-200 dark:border-purple-700 rounded-lg shadow-sm focus:border-purple-600 focus:ring-2 focus:ring-purple-600/50 bg-white dark:bg-gray-800">
                                    <option value="0" {{ $isGroupWhole ? 'selected' : '' }}>グループ全体</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}" {{ $targetUser && $targetUser->id === $member->id ? 'selected' : '' }}>
                                            {{ $member->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- タブナビゲーション --}}
                <div class="flex gap-3 mb-6 shrink-0">
                    <button 
                        @click="activeTab = 'normal'; updateTabParam('normal')"
                        :class="activeTab === 'normal' ? 'performance-tab-active performance-tab-normal' : 'performance-tab-inactive'"
                        class="performance-tab px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold text-xs sm:text-sm transition-all shadow-md flex-1 sm:flex-none">
                        <svg class="w-4 h-4 inline-block mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="hidden sm:inline">通常タスク</span>
                        <span class="sm:hidden">通常</span>
                    </button>
                    <button 
                        @click="activeTab = 'group'; updateTabParam('group')"
                        :class="activeTab === 'group' ? 'performance-tab-active performance-tab-group' : 'performance-tab-inactive'"
                        class="performance-tab px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-semibold text-xs sm:text-sm transition-all shadow-md flex-1 sm:flex-none">
                        <svg class="w-4 h-4 inline-block mr-1 sm:mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                        <span class="hidden sm:inline">グループタスク</span>
                        <span class="sm:hidden">グループ</span>
                    </button>
                </div>

                {{-- タブコンテンツ: 通常タスク --}}
                <div x-show="activeTab === 'normal'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 pb-4">
                        <div class="performance-card performance-card-normal">
                            <div class="performance-card-header">
                                <svg class="w-5 h-5 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="performance-card-title">週間実績</h2>
                            </div>
                            <div class="performance-card-body">
                                <canvas id="w-normal"></canvas>
                            </div>
                            <x-reports.totals :data="$weekNormal" kind="normal"/>
                        </div>
                        <div class="performance-card performance-card-normal">
                            <div class="performance-card-header">
                                <svg class="w-5 h-5 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="performance-card-title">月間実績</h2>
                            </div>
                            <div class="performance-card-body">
                                <canvas id="m-normal"></canvas>
                            </div>
                            <x-reports.totals :data="$monthNormal" kind="normal"/>
                        </div>
                        <div class="performance-card performance-card-normal">
                            <div class="performance-card-header">
                                <svg class="w-5 h-5 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="performance-card-title">年間実績</h2>
                            </div>
                            <div class="performance-card-body">
                                <canvas id="y-normal"></canvas>
                            </div>
                            <x-reports.totals :data="$yearNormal" kind="normal"/>
                        </div>
                    </div>
                </div>

                {{-- タブコンテンツ: グループタスク --}}
                <div x-show="activeTab === 'group'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 pb-4">
                        <div class="performance-card performance-card-group">
                            <div class="performance-card-header">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="performance-card-title">週間実績</h2>
                            </div>
                            <div class="performance-card-body">
                                <canvas id="w-group"></canvas>
                            </div>
                            <x-reports.totals :data="$weekGroup" kind="group"/>
                        </div>
                        <div class="performance-card performance-card-group">
                            <div class="performance-card-header">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="performance-card-title">月間実績</h2>
                            </div>
                            <div class="performance-card-body">
                                <canvas id="m-group"></canvas>
                            </div>
                            <x-reports.totals :data="$monthGroup" kind="group"/>
                        </div>
                        <div class="performance-card performance-card-group">
                            <div class="performance-card-header">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="performance-card-title">年間実績</h2>
                            </div>
                            <div class="performance-card-body">
                                <canvas id="y-group"></canvas>
                            </div>
                            <x-reports.totals :data="$yearGroup" kind="group"/>
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
                weekNormal: @json($weekNormal),
                monthNormal: @json($monthNormal),
                yearNormal: @json($yearNormal),
                weekGroup: @json($weekGroup),
                monthGroup: @json($monthGroup),
                yearGroup: @json($yearGroup),
            };

            function updateTabParam(tab) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        </script>
    @endpush
</x-app-layout>