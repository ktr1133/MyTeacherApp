@props(['data', 'kind' => 'normal'])
@php
    $done = $kind === 'normal' ? array_sum($data['nDone']) : array_sum($data['gDone']);
    $todo = $kind === 'normal' ? array_sum($data['nTodo']) : array_sum($data['gTodo']);
    $cum  = $kind === 'normal' ? end($data['nCum']) : end($data['gCum']);
    $reward = $kind === 'group' ? end($data['gRewardCum']) : null;
@endphp

<div class="mt-3">
    @if(!$isChildTheme)
        {{-- 大人用: シンプルな表記 --}}
        <div class="flex flex-wrap gap-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                完了: <strong class="text-gray-800 dark:text-gray-200">{{ $done }}</strong>
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">
                未完了: <strong class="text-gray-800 dark:text-gray-200">{{ $todo }}</strong>
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">
                累積完了: <strong class="text-gray-800 dark:text-gray-200">{{ $cum }}</strong>
            </span>
            @if(!is_null($reward))
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    報酬累計: <strong class="text-gray-800 dark:text-gray-200">{{ number_format($reward) }} 円</strong>
                </span>
            @endif
        </div>
    @else
        {{-- 子ども用: YET/DONE/ごうけい --}}
        <div class="flex flex-wrap gap-3">
            <span class="stat-yet">
                <span class="text-gray-700 dark:text-gray-300">YET:</span>
                <strong class="text-orange-600 dark:text-orange-400">{{ $todo }}</strong>
            </span>
            
            <span class="stat-done">
                <span class="text-gray-700 dark:text-gray-300">DONE:</span>
                <strong class="text-green-600 dark:text-green-400">{{ $done }}</strong>
            </span>
            
            <span class="stat-total">
                <span class="text-gray-700 dark:text-gray-300">ごうけい:</span>
                <strong class="text-purple-600 dark:text-purple-400">{{ $cum }}</strong>
            </span>
            
            @if(!is_null($reward))
                <span class="stat-coin">
                    <span class="text-gray-700 dark:text-gray-300">コイン:</span>
                    <strong class="text-amber-600 dark:text-amber-400">{{ number_format($reward) }}</strong>
                </span>
            @endif
        </div>
    @endif
</div>