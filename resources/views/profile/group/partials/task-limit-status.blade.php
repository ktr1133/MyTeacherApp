{{-- グループタスク作成制限の使用状況表示（グループ管理者向け: 閲覧のみ） --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(to bottom right, rgb(59 130 246), rgb(6 182 212));">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                グループタスク作成状況
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                今月の使用状況とサブスクリプション情報
            </p>
        </div>
    </div>

    @php
        $usage = app(\App\Services\Group\GroupTaskLimitServiceInterface::class)->getGroupTaskUsage($group);
        $usagePercentage = $usage['limit'] > 0 ? ($usage['current'] / $usage['limit']) * 100 : 0;
        $isNearLimit = $usagePercentage >= 80;
        $isAtLimit = $usage['remaining'] <= 0;
    @endphp

    {{-- サブスクリプション状態 --}}
    <div class="mb-6 p-4 rounded-xl {{ $group->subscription_active ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold {{ $group->subscription_active ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300' }}">
                    サブスクリプション状態:
                </span>
                @if($group->subscription_active)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        有効
                    </span>
                    @if($group->subscription_plan)
                        <span class="text-xs px-2 py-1 rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            {{ ucfirst($group->subscription_plan) }}プラン
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        無料プラン
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- 使用状況表示 --}}
    <div class="space-y-4">
        {{-- プログレスバー --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    今月の作成数
                </span>
                <span class="text-sm font-bold {{ $isAtLimit ? 'text-red-600 dark:text-red-400' : ($isNearLimit ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white') }}">
                    {{ $usage['current'] }} / {{ $group->subscription_active ? '無制限' : $usage['limit'] }}
                </span>
            </div>
            
            @if(!$group->subscription_active)
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $isAtLimit ? 'bg-red-500' : ($isNearLimit ? 'bg-yellow-500' : 'bg-blue-500') }}"
                         style="width: {{ min($usagePercentage, 100) }}%">
                    </div>
                </div>
                
                @if($isAtLimit)
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 font-medium">
                        ⚠️ 今月の無料枠を使い切りました
                    </p>
                @elseif($isNearLimit)
                    <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400 font-medium">
                        ⚠️ 残り {{ $usage['remaining'] }}回です
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        残り {{ $usage['remaining'] }}回作成できます
                    </p>
                @endif
            @else
                <div class="w-full bg-green-100 dark:bg-green-900/30 rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-500 w-full"></div>
                </div>
                <p class="mt-2 text-sm text-green-600 dark:text-green-400 font-medium">
                    ✨ サブスクリプション会員は無制限に作成できます
                </p>
            @endif
        </div>

        {{-- 次回リセット日 --}}
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                次回リセット日
            </span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                @if($usage['reset_at'])
                    {{ \Carbon\Carbon::parse($usage['reset_at'])->format('Y年m月d日') }}
                @else
                    未設定
                @endif
            </span>
        </div>

        {{-- サブスクリプション未加入の場合の案内 --}}
        @if(!$group->subscription_active)
            <div class="mt-4 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                <div class="flex items-start gap-3">
                    <div class="shrink-0">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-5a1 1 0 100 2 1 1 0 000-2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-purple-900 dark:text-purple-100 mb-1">
                            ファミリープランでもっと便利に！
                        </h4>
                        <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">
                            サブスクリプションに加入すると、グループタスクを無制限で作成できます。
                        </p>
                        <a href="{{ route('subscriptions.index') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            プランを見る
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
