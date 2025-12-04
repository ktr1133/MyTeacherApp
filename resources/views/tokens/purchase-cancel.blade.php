<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/purchase.css'])
    @endpush

    <div class="flex min-h-[100dvh] token-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        @if(auth()->user()->theme !== 'child')
            <div class="absolute inset-0 -z-10 pointer-events-none">
                <div class="token-floating-decoration absolute top-20 left-10 w-72 h-72 bg-yellow-500/10 rounded-full blur-3xl"></div>
                <div class="token-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 token-header-blur shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3">
                        {{-- モバイルメニューボタン --}}
                        <button
                            type="button"
                            data-sidebar-toggle="mobile"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- ヘッダータイトル --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center shadow-lg">
                                @if(auth()->user()->theme === 'child')
                                    <span class="text-2xl lg:text-3xl">⚠️</span>
                                @else
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                                    @if(auth()->user()->theme === 'child')
                                        キャンセルしたよ
                                    @else
                                        購入キャンセル
                                    @endif
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-4xl mx-auto px-4 lg:px-6 py-8 lg:py-12">
                    {{-- キャンセルメッセージカード --}}
                    <div class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-2 border-yellow-500 rounded-3xl p-8 lg:p-12 text-center shadow-xl">
                        <div class="text-7xl lg:text-8xl mb-6">⚠️</div>
                        
                        @if(auth()->user()->theme === 'child')
                            <h2 class="text-3xl lg:text-4xl font-bold text-yellow-600 dark:text-yellow-400 mb-4">
                                こうにゅうをやめたよ
                            </h2>
                            <p class="text-lg lg:text-xl text-gray-700 dark:text-gray-300 mb-8">
                                おかねははらってないよ。<br>
                                またこうにゅうしたくなったらきてね！
                            </p>
                        @else
                            <h2 class="text-3xl lg:text-4xl font-bold text-yellow-600 dark:text-yellow-400 mb-4">
                                購入をキャンセルしました
                            </h2>
                            <p class="text-lg lg:text-xl text-gray-700 dark:text-gray-300 mb-8">
                                決済は行われていません。<br>
                                必要な時にまた購入できます。
                            </p>
                        @endif
                        
                        {{-- アクションボタン --}}
                        <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                            <a href="{{ route('tokens.purchase') }}" 
                               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-base font-bold text-white bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                                @if(auth()->user()->theme === 'child')
                                    <span class="text-xl">🪙</span>
                                    <span>こうにゅうがめんにもどる</span>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    <span>購入画面に戻る</span>
                                @endif
                            </a>
                            
                            <a href="{{ route('dashboard') }}" 
                               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-base font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-md hover:shadow-lg transform hover:scale-105 transition-all">
                                @if(auth()->user()->theme === 'child')
                                    <span class="text-xl">🏠</span>
                                    <span>ホームにもどる</span>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    <span>ダッシュボードに戻る</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    
                    {{-- 補足説明 --}}
                    <div class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-md">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">
                            @if(auth()->user()->theme === 'child')
                                きになること
                            @else
                                よくある質問
                            @endif
                        </h3>
                        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                            @if(auth()->user()->theme === 'child')
                                <p>❓ <strong>おかねははらってない？</strong><br>はい、はらってないよ。あんしんしてね。</p>
                                <p>❓ <strong>またこうにゅうできる？</strong><br>うん！いつでもこうにゅうできるよ。</p>
                            @else
                                <p>❓ <strong>料金は発生していますか？</strong><br>いいえ、決済はキャンセルされたため料金は発生していません。</p>
                                <p>❓ <strong>再度購入できますか？</strong><br>はい、購入画面からいつでも再度購入できます。</p>
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
