<div class="pending-requests-container">
    @forelse($pendingRequests as $request)
        <div class="pending-request-card {{ $isChildTheme ? 'child-theme' : '' }}">
            {{-- パッケージ情報 --}}
            <div class="flex items-center gap-4 flex-1">
                @if($isChildTheme)
                    <div class="coin-icon">
                        <span class="text-3xl">🪙</span>
                    </div>
                @else
                    <div class="icon-wrapper">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                @endif
                
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $request->package->name }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ number_format($request->package->tokens) }}
                        @if($isChildTheme)
                            コイン
                        @else
                            トークン
                        @endif
                        <span class="mx-2">•</span>
                        ¥{{ number_format($request->package->price) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        @if($isChildTheme)
                            お願いした日：{{ $request->created_at->format('Y年m月d日 H:i') }}
                        @else
                            リクエスト日時：{{ $request->created_at->format('Y-m-d H:i') }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- ステータスバッジ --}}
            <div class="status-badge pending">
                @if($isChildTheme)
                    <span class="emoji">⏳</span>
                    <span>お願い中</span>
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>承認待ち</span>
                @endif
            </div>

            {{-- 取り下げボタン --}}
            <form action="{{ route('tokens.requests.cancel', $request) }}" method="POST" class="ml-4">
                @csrf
                @method('DELETE')
                <button 
                    type="submit" 
                    class="btn-cancel {{ $isChildTheme ? 'child-theme' : '' }}"
                    onclick="return confirm('@if($isChildTheme)ほんとうに やめる？@elseリクエストを取り下げますか？@endif')">
                    @if($isChildTheme)
                        <span class="emoji">❌</span>
                        <span>やめる</span>
                    @else
                        取り下げ
                    @endif
                </button>
            </form>
        </div>
    @empty
        <div class="empty-state">
            @if($isChildTheme)
                <span class="text-6xl mb-4">😊</span>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    いまは お願いしてないよ！
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    コインを買いたいときは「買う」タブから選んでね！
                </p>
            @else
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    承認待ちのリクエストはありません
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    トークンを購入するには、パッケージ一覧から選択してください。
                </p>
            @endif
        </div>
    @endforelse
</div>