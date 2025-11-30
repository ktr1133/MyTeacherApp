<x-app-layout>
    <div class="flex min-h-[100dvh] bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex items-center justify-center p-4">
            <div class="max-w-md w-full">
                {{-- キャンセルカード --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center">
                    {{-- アイコン --}}
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>

                    {{-- タイトル --}}
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                        サブスクリプション登録をキャンセルしました
                    </h2>

                    {{-- メッセージ --}}
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        サブスクリプションの登録がキャンセルされました。<br>
                        いつでも再度お申し込みいただけます。
                    </p>

                    {{-- 情報 --}}
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            引き続き無料プランをご利用いただけます。<br>
                            <span class="font-medium text-gray-900 dark:text-gray-100">最大6名・月3回のグループタスク</span>
                        </p>
                    </div>

                    {{-- ボタン --}}
                    <div class="flex flex-col gap-3">
                        <a href="{{ route('subscriptions.index') }}" 
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            プラン選択に戻る
                        </a>
                        <a href="{{ route('dashboard') }}" 
                           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition">
                            ダッシュボードへ戻る
                        </a>
                    </div>
                </div>

                {{-- サポート情報 --}}
                <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                    <p>ご不明な点がございましたら、</p>
                    <a href="{{ route('portal.contact.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        お問い合わせ
                    </a>
                    <span>ください。</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
