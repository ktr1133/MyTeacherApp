@props(['data', 'kind' => 'normal'])
@php
    $done = $kind === 'normal' ? array_sum($data['nDone']) : array_sum($data['gDone']);
    $todo = $kind === 'normal' ? array_sum($data['nTodo']) : array_sum($data['gTodo']);
    $cum  = $kind === 'normal' ? end($data['nCum']) : end($data['gCum']);
    $reward = $kind === 'group' ? end($data['gRewardCum']) : null;
@endphp
<div class="mt-3 text-sm text-gray-600">
    <div class="flex flex-wrap gap-4">
        <span>完了: <strong class="text-gray-800">{{ $done }}</strong></span>
        <span>未完了: <strong class="text-gray-800">{{ $todo }}</strong></span>
        <span>累積完了: <strong class="text-gray-800">{{ $cum }}</strong></span>
        @if(!is_null($reward))
            <span>報酬累計: <strong class="text-gray-800">{{ number_format($reward) }} 円</strong></span>
        @endif
    </div>
</div>