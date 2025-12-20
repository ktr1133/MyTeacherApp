{{-- 未紐付け子アカウント検索セクション --}}
<div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter" style="animation-delay: 0.15s;">
    <div class="-mx-4 sm:mx-0 px-8 sm:px-6 py-4 border-b border-blue-500/20 dark:border-blue-500/30 bg-gradient-to-r from-blue-500/5 to-cyan-50/50 dark:from-blue-500/10 dark:to-cyan-900/10">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shadow" style="background: linear-gradient(to bottom right, rgb(59 130 246), rgb(6 182 212));">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-sm font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
                    お子様アカウントとの紐付け
                </h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                    招待リンクを使わずに登録した場合はこちらから検索できます
                </p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        {{-- 検索フォーム --}}
        <form id="search-children-form" method="POST" action="{{ route('profile.group.search-children') }}" class="space-y-4">
            @csrf

            {{-- 検索説明 --}}
            <div class="bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200/50 dark:border-blue-700/50 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-1">
                            保護者のメールアドレスで検索
                        </p>
                        <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                            お子様が登録時に入力した「保護者のメールアドレス」を入力してください。<br>
                            一致するアカウントが見つかった場合、紐付けリクエストを送信できます。
                        </p>
                    </div>
                </div>
            </div>

            {{-- メールアドレス入力 --}}
            <div>
                <label for="parent_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    保護者のメールアドレス
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input 
                        type="email" 
                        id="parent_email" 
                        name="parent_email" 
                        value="{{ old('parent_email', auth()->user()->email) }}"
                        required
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition @error('parent_email') border-red-300 dark:border-red-600 @enderror"
                        placeholder="parent@example.com">
                    <button 
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 whitespace-nowrap group">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>検索</span>
                    </button>
                </div>
                @error('parent_email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </form>

        {{-- 検索結果 --}}
        @if (isset($children) && $children->isNotEmpty())
            {{-- サーバー側レンダリング時の表示（後方互換性） --}}
            <div class="mt-6 space-y-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        見つかったお子様（{{ $children->count() }}件）
                    </h3>
                </div>

                <p class="text-sm text-blue-600 dark:text-blue-400">
                    ※ JavaScriptが有効な場合、検索結果はモーダルで表示されます。
                </p>

                <div class="space-y-3">
                    @foreach ($children as $child)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-800 transition">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    {{-- アバター --}}
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center text-white font-semibold text-sm shadow-md">
                                            {{ mb_substr($child->username, 0, 1) }}
                                        </div>
                                    </div>

                                    {{-- ユーザー情報 --}}
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ $child->username }}
                                        </h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ $child->email }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                                            登録日: {{ $child->created_at->format('Y年m月d日') }}
                                        </p>
                                    </div>
                                </div>

                                {{-- 紐付けリクエストボタン --}}
                                <form method="POST" action="{{ route('profile.group.send-link-request') }}" class="flex-shrink-0">
                                    @csrf
                                    <input type="hidden" name="child_user_id" value="{{ $child->id }}">
                                    <button 
                                        type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 whitespace-nowrap group">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        <span>リクエスト送信</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif (isset($children) && $children->isEmpty())
            <div class="mt-6 bg-yellow-50/50 dark:bg-yellow-900/10 border border-yellow-200/50 dark:border-yellow-700/50 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-yellow-900 dark:text-yellow-200">
                            該当するアカウントが見つかりませんでした
                        </p>
                        <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1 leading-relaxed">
                            以下をご確認ください：<br>
                            • 保護者のメールアドレスが正しく入力されているか<br>
                            • お子様が登録時に正しいメールアドレスを入力したか<br>
                            • お子様のアカウントが既に別の保護者と紐付けられていないか
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- エラーメッセージ --}}
        @error('child_user_id')
            <div class="mt-4 bg-red-50/50 dark:bg-red-900/10 border border-red-200/50 dark:border-red-700/50 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                </div>
            </div>
        @enderror
    </div>
</div>

{{-- 検索結果モーダル --}}
<div id="search-results-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- 背景オーバーレイ -->
    <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/75 dark:bg-gray-900/90 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>

        <!-- モーダルパネル -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <!-- ヘッダー -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent" id="modal-title">
                                検索結果
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                紐づけする子アカウントを選択してください
                            </p>
                        </div>
                    </div>
                    <button type="button" 
                            data-close-modal="search-results-modal"
                            class="rounded-lg p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <span class="sr-only">閉じる</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- モーダルボディ -->
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                <div id="children-list" class="space-y-3">
                    <!-- JavaScriptで動的に生成 -->
                </div>
            </div>

            <!-- モーダルフッター -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <form id="link-children-form" method="POST" action="{{ route('profile.group.link-children') }}">
                    @csrf
                    {{-- child_user_ids[] はJavaScriptで動的に生成 --}}
                    
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            「×」ボタンで対象から除外できます
                        </p>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 group">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="button-text">選択した子を紐づける</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
