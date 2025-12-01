<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- ヘッダー --}}
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            月次レポート
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            グループの月次実績レポート一覧
                        </p>
                    </div>
                    <a href="{{ route('reports.performance') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        実績画面に戻る
                    </a>
                </div>
            </div>

            {{-- レポート一覧 --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                @if($reports->isEmpty())
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">レポートがありません</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            月次レポートは毎月1日に自動生成されます。
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($reports as $item)
                            @php
                                $report = $item['report'];
                                $canAccess = $item['can_access'];
                                $formatted = $item['formatted'];
                            @endphp
                            
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $report->report_month->format('Y年m月') }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            生成日時: {{ $report->generated_at ? $report->generated_at->format('Y-m-d H:i') : '未生成' }}
                                        </p>
                                        
                                        @if($canAccess && $formatted)
                                            <div class="mt-3 grid grid-cols-3 gap-4">
                                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">通常タスク</p>
                                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $formatted['summary']['normal_tasks']['count'] }}</p>
                                                    @if($formatted['summary']['normal_tasks']['change_percentage'] != 0)
                                                        <p class="text-xs {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $formatted['summary']['normal_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['normal_tasks']['change_percentage'] }}%
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                                                    <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">グループタスク</p>
                                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ $formatted['summary']['group_tasks']['count'] }}</p>
                                                    @if($formatted['summary']['group_tasks']['change_percentage'] != 0)
                                                        <p class="text-xs {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $formatted['summary']['group_tasks']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['group_tasks']['change_percentage'] }}%
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                                                    <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">獲得報酬</p>
                                                    <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">{{ number_format($formatted['summary']['rewards']['total']) }}</p>
                                                    @if($formatted['summary']['rewards']['change_percentage'] != 0)
                                                        <p class="text-xs {{ $formatted['summary']['rewards']['change_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $formatted['summary']['rewards']['change_percentage'] > 0 ? '+' : '' }}{{ $formatted['summary']['rewards']['change_percentage'] }}%
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="mt-3 bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-center">
                                                <svg class="w-8 h-8 mx-auto text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                                </svg>
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                    このレポートを閲覧するにはサブスクリプションが必要です
                                                </p>
                                                <a href="{{ route('subscriptions.index') }}"
                                                   class="mt-3 inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 rounded-lg hover:from-[#4AA5B2] hover:to-purple-700 transition">
                                                    プランを見る
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 補足情報 --}}
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                            月次レポートについて
                        </p>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            月次レポートは毎月1日の午前2時に自動生成されます。サブスクリプション加入者は全期間のレポートを閲覧できます。無料ユーザーはグループ作成後1ヶ月間のレポートのみ閲覧可能です。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
