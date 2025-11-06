<x-app-layout>
    @push('styles')
        @vite(['resources/css/reports/performance.css'])
    @endpush

    <div class="flex min-h-[100dvh] bg-[#F3F3F2]">
        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <main class="flex-1 overflow-hidden">
            <div class="h-full flex flex-col px-4 lg:px-6 py-4 lg:py-6" 
                 x-data="{ activeTab: '{{ request()->get('tab', 'normal') }}' }">
                {{-- ヘッダー --}}
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 shrink-0">
                    <h1 class="text-xl font-semibold text-gray-800">実績ダッシュボード</h1>
                    
                    {{-- メンバー選択（グループタスクタブでのみ有効、グループ編集権限がある場合のみ） --}}
                    <div x-show="activeTab === 'group'">
                        @if(Auth::user()->canEditGroup() && $members->isNotEmpty())
                            <form method="GET" action="{{ route('reports.performance') }}" class="flex items-center gap-2">
                                <input type="hidden" name="tab" value="group">
                                <label for="user-select" class="text-sm text-gray-600 whitespace-nowrap">メンバー:</label>
                                <select 
                                    id="user-select" 
                                    name="user_id" 
                                    onchange="this.form.submit()"
                                    class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-purple-600 focus:ring focus:ring-purple-600/50 pr-8">
                                    <option value="0" {{ $isGroupWhole ? 'selected' : '' }}>グループ全体</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}" {{ $targetUser && $targetUser->id === $member->id ? 'selected' : '' }}>
                                            {{ $member->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="text-sm text-gray-600">{{ $targetUser->username ?? Auth::user()->username }} の実績</span>
                        @endif
                    </div>
                    <div x-show="activeTab === 'normal'">
                        <span class="text-sm text-gray-600">{{ $normalUser->username }} の実績</span>
                    </div>
                </div>

                {{-- タブナビゲーション --}}
                <div class="flex gap-2 border-b border-gray-200 mb-4 shrink-0">
                    <button 
                        @click="activeTab = 'normal'; updateTabParam('normal')"
                        :class="activeTab === 'normal' ? 'border-[#59B9C6] text-[#59B9C6]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                        通常タスク
                    </button>
                    <button 
                        @click="activeTab = 'group'; updateTabParam('group')"
                        :class="activeTab === 'group' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 border-b-2 font-medium text-sm transition">
                        グループタスク
                    </button>
                </div>

                {{-- タブコンテンツ: 通常タスク --}}
                <div x-show="activeTab === 'normal'" x-transition class="flex-1 min-h-0 overflow-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 h-full auto-rows-fr">
                        <div class="report-card">
                            <h2 class="report-card-title">週間</h2>
                            <div class="report-card-body">
                                <canvas id="w-normal"></canvas>
                            </div>
                            <x-reports.totals :data="$weekNormal" kind="normal"/>
                        </div>
                        <div class="report-card">
                            <h2 class="report-card-title">月間</h2>
                            <div class="report-card-body">
                                <canvas id="m-normal"></canvas>
                            </div>
                            <x-reports.totals :data="$monthNormal" kind="normal"/>
                        </div>
                        <div class="report-card">
                            <h2 class="report-card-title">年間</h2>
                            <div class="report-card-body">
                                <canvas id="y-normal"></canvas>
                            </div>
                            <x-reports.totals :data="$yearNormal" kind="normal"/>
                        </div>
                    </div>
                </div>

                {{-- タブコンテンツ: グループタスク --}}
                <div x-show="activeTab === 'group'" x-transition class="flex-1 min-h-0 overflow-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 h-full auto-rows-fr">
                        <div class="report-card">
                            <h2 class="report-card-title">週間</h2>
                            <div class="report-card-body">
                                <canvas id="w-group"></canvas>
                            </div>
                            <x-reports.totals :data="$weekGroup" kind="group"/>
                        </div>
                        <div class="report-card">
                            <h2 class="report-card-title">月間</h2>
                            <div class="report-card-body">
                                <canvas id="m-group"></canvas>
                            </div>
                            <x-reports.totals :data="$monthGroup" kind="group"/>
                        </div>
                        <div class="report-card">
                            <h2 class="report-card-title">年間</h2>
                            <div class="report-card-body">
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

            // タブ切り替え時にURLパラメータを更新（ページリロードなし）
            function updateTabParam(tab) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        </script>
    @endpush
</x-app-layout>