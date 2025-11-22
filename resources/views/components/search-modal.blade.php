{{-- 検索モーダル --}}
<div id="search-modal" class="fixed inset-0 z-50 hidden p-6 md:p-8 lg:p-10 transition-all duration-300 ease-in-out" data-search-modal>
    {{-- 背景オーバーレイ --}}
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity duration-300" data-modal-backdrop></div>
    
    {{-- モーダルコンテンツ（タスクリスト画面を踏襲） --}}
    <div class="relative h-full bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden transform transition-all duration-300 ease-out scale-95 opacity-0" data-modal-content>
        {{-- ヘッダー --}}
        <div class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm {{ $isChildTheme ? 'child-theme' : '' }}">
            <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div>
                        @if (!$isChildTheme)
                            <h2 class="dashboard-header-title text-lg font-bold">タスク検索</h2>
                            <p class="text-xs text-gray-600 dark:text-gray-400">キーワードやタグで検索</p>
                        @else
                            <h2 class="dashboard-header-title text-lg font-bold">さがす</h2>
                            <p class="text-xs text-gray-600 dark:text-gray-400">ことばやたぐで</p>
                        @endif
                    </div>
                </div>
                <button 
                    type="button" 
                    class="p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    data-close-modal
                    aria-label="閉じる">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- 検索エリア --}}
        <div class="flex-1 overflow-y-auto">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{-- 検索ボックス --}}
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-lg p-6 border border-gray-200/50 dark:border-gray-700/50">
                    <x-task-filter />
                    
                    {{-- ヒントテキスト --}}
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-gray-700/50 rounded-lg">
                        @if (!$isChildTheme)
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">💡 検索のヒント</p>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• <code class="px-1.5 py-0.5 bg-white dark:bg-gray-600 rounded font-mono text-xs">#タグ名</code> でタグ検索</li>
                                <li>• スペース区切りで<strong>OR検索</strong>（いずれかの単語を含む）</li>
                                <li>• <code class="px-1.5 py-0.5 bg-white dark:bg-gray-600 rounded font-mono text-xs">&</code> で<strong>AND検索</strong>（すべての単語を含む）</li>
                                <li>• 検索結果をクリックするとタスクの編集画面に移動します</li>
                            </ul>
                        @else
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">💡 つかいかた</p>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• <code class="px-1.5 py-0.5 bg-white dark:bg-gray-600 rounded font-mono text-xs">#たぐのなまえ</code> でたぐさがし</li>
                                <li>• ことばをすぺーすでわけるとどれかのことばをさがせるよ</li>
                                <li>• <code class="px-1.5 py-0.5 bg-white dark:bg-gray-600 rounded font-mono text-xs">&</code> をいれると全部のことばをさがせるよ</li>
                                <li>• さがしたものをおすとひらくよ</li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@vite(['resources/js/components/search-modal.js'])
