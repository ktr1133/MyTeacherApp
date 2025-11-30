<x-app-layout>
    <div class="flex min-h-[100dvh] bg-gradient-to-br from-green-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex items-center justify-center p-4">
            <div class="max-w-md w-full">
                {{-- 成功カード --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center">
                    {{-- アニメーションアイコン --}}
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>

                    {{-- タイトル --}}
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                        決済処理を開始しました
                    </h2>

                    {{-- メッセージ --}}
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Stripeでの決済処理を実行しています。<br>
                        処理が完了次第、通知でお知らせします。
                    </p>

                    {{-- ステータス表示 --}}
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-center gap-3 text-blue-800 dark:text-blue-200">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="font-medium">処理中...</span>
                        </div>
                    </div>

                    {{-- 注意事項 --}}
                    <div class="text-left mb-6 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>通常、数分以内に処理が完了します</span>
                        </p>
                        <p class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>処理完了後、通知でお知らせします</span>
                        </p>
                        <p class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>このページを閉じても問題ありません</span>
                        </p>
                    </div>

                    {{-- ボタン --}}
                    <div class="flex flex-col gap-3">
                        <a href="{{ route('dashboard') }}" 
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            タスクリストへ戻る
                        </a>
                        <a href="{{ route('subscriptions.index') }}" 
                           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition">
                            サブスクリプション管理へ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
