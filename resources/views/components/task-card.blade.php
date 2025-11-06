@props(['task', 'tags', 'isCompleted' => false])

@php
    use Carbon\Carbon;

    // スパン別の配色（メインカラー #59B9C6 系統）
    $spanColors = [
        config('const.task_spans.long') => [
            'border' => 'border-l-[#59B9C6]',
            'icon' => 'text-[#59B9C6]',
            'tag' => 'bg-[#59B9C6]/20 text-[#59B9C6]',
            'badge' => 'bg-[#59B9C6] text-white',
        ],
        config('const.task_spans.mid') => [
            'border' => 'border-l-[#7BC9A8]',
            'icon' => 'text-[#7BC9A8]',
            'tag' => 'bg-[#7BC9A8]/20 text-[#7BC9A8]',
            'badge' => 'bg-[#7BC9A8] text-white',
        ],
        config('const.task_spans.short') => [
            'border' => 'border-l-[#9DD9C0]',
            'icon' => 'text-[#9DD9C0]',
            'tag' => 'bg-[#9DD9C0]/20 text-[#9DD9C0]',
            'badge' => 'bg-[#9DD9C0] text-gray-800',
        ],
    ];

    $colors = $spanColors[$task->span] ?? $spanColors[config('const.task_spans.mid')];
    
    $spanLabels = [
        config('const.task_spans.long')  => '長期',
        config('const.task_spans.mid')   => '中期',
        config('const.task_spans.short') => '短期',
    ];


    // グループタスクの場合は紫色
    if ($task->isGroupTask()) {
        $borderColor = 'border-l-purple-500';
        $iconColor = 'text-purple-500';
    } else {
        $borderColor = $isCompleted ? 'border-l-green-500' : $colors['border'];
        $iconColor = $isCompleted ? 'text-green-500' : $colors['icon'];
    }

    $titleDecoration = $isCompleted ? 'line-through text-gray-400' : 'text-gray-800';

    $created = $task->created_at instanceof Carbon ? $task->created_at : Carbon::parse($task->created_at);
    $elapsedDays = max(0, $created->startOfDay()->diffInDays(now()->startOfDay()));
    
    // タグ名をカンマ区切りで取得
    $tagNames = $task->tags->pluck('name')->implode(',');
    
    // グループタスクは編集不可
    $canEdit = $task->canEdit();
@endphp

<div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow border-l-4 {{ $borderColor }} cursor-pointer"
     data-task-id="{{ $task->id }}"
     data-tags="{{ $tagNames }}"
     data-due-date="{{ $task->due_date }}"
     @click="$dispatch('open-task-modal-{{ $task->id }}')"
>
    
    {{-- カード内コンテンツ --}}
    <div class="p-5">
        {{-- ヘッダー：チェックボックス + タイトル + スパンバッジ --}}
        <div class="flex items-start gap-3 mb-3">
            {{-- グループタスクの場合はチェックボックス非表示 --}}
            @if(!$task->isGroupTask())
                <label for="task-{{ $task->id }}" class="flex items-center cursor-pointer shrink-0 pt-0.5" @click.stop>
                    <input 
                        type="checkbox" 
                        id="task-{{ $task->id }}" 
                        class="form-checkbox h-5 w-5 rounded border-gray-300 {{ $iconColor }} focus:ring-[#59B9C6]" 
                        @checked($isCompleted) 
                        onclick="event.preventDefault(); document.getElementById('toggle-task-{{ $task->id }}').submit();">
                </label>
            @else
                {{-- グループタスクアイコン --}}
                <div class="flex items-center shrink-0 pt-0.5">
                    <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                </div>
            @endif
            
            {{-- タイトル --}}
            <h3 class="font-semibold text-base flex-1 {{ $titleDecoration }} break-words">
                {{ $task->title }}
            </h3>
            
            {{-- スパンバッジ --}}
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $colors['badge'] }} shrink-0">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                {{ $spanLabels[$task->span] ?? $task->span }}
            </span>
        </div>
        
        {{-- 詳細情報エリア --}}
        <div class="space-y-2 text-sm text-gray-600">
            {{-- グループタスク情報 --}}
            @if($task->isGroupTask())
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium text-purple-600">報酬: {{ number_format($task->reward) }}円</span>
                </div>
                
                @if($task->isPendingApproval())
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-yellow-600 font-medium">承認待ち</span>
                    </div>
                @elseif($task->isApproved())
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-600 font-medium">承認済み</span>
                    </div>
                @endif
            @endif
            {{-- 期限 --}}
            @if ($task->due_date)
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $colors['icon'] }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="truncate">期限: {{ \Carbon\Carbon::parse($task->due_date)->format('Y/m/d') }}</span>
                </div>
            @endif
            
            {{-- 登録日と経過日数 --}}
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 {{ $colors['icon'] }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                <span class="truncate">登録: {{ $created->format('Y/m/d') }} ({{ $elapsedDays }}日経過)</span>
            </div>

            {{-- タグ --}}
            <div class="flex flex-wrap gap-1.5 pt-1">
                @forelse ($task->tags as $tag)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors['tag'] }}">
                        {{ $tag->name }}
                    </span>
                @empty
                    <span class="text-xs text-gray-400">タグなし</span>
                @endforelse
            </div>
        </div>
    </div>
    {{-- 通常タスクの場合のみトグルフォーム --}}
    @if(!$task->isGroupTask())
        {{-- 完了トグル用隠しフォーム --}}
        <form id="toggle-task-{{ $task->id }}" method="POST" action="{{ route('tasks.toggle', $task) }}" class="hidden">
            @csrf
            @method('PATCH')
        </form>
    @endif
</div>

{{-- 編集可能なタスクのみモーダルをインクルード --}}
@if($task->canEdit())
    @include('dashboard.modal-task-card', ['task' => $task, 'tags' => $tags ?? []])
@else
    {{-- グループタスク詳細モーダル（閲覧のみ） --}}
    @include('dashboard.modal-group-task-detail', ['task' => $task])
@endif