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
                            {{ $targetMonth->format('Y年n月') }}のレポート
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

            {{-- ロック画面 --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        プレミアム機能
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                        過去のレポートを閲覧するには、サブスクリプションへの加入が必要です。<br>
                        無料ユーザーは、グループ作成後1ヶ月間のレポートのみ閲覧可能です。
                    </p>
                    <div class="mt-6 flex items-center justify-center gap-4">
                        <a href="{{ route('subscriptions.index') }}"
                           class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 rounded-lg hover:from-[#4AA5B2] hover:to-purple-700 transition shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            プランを見る
                        </a>
                        <a href="{{ route('reports.monthly.show') }}"
                           class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            最新のレポートを見る
                        </a>
                    </div>
                </div>
            </div>

            {{-- 補足情報 --}}
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                            サブスクリプションについて
                        </p>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            サブスクリプションに加入すると、過去12ヶ月分のレポートを閲覧できます。また、実績画面でのメンバー別表示や過去期間の閲覧も可能になります。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
