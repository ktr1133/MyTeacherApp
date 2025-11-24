@props(['task', 'tags', 'isCompleted' => false, 'isChildTheme' => false])

@php
    use Carbon\Carbon;

    // 期限の状態を判定
    $dueStatus = 'none';
    $dueMessage = '';
    
    // 完了済みタスクの場合は期限チェックをスキップ
    if ($task->completed_at) {
        $dueStatus = 'completed';
        $dueMessage = $isChildTheme ? 'おわったよ！' : '完了済';
    } elseif ($task->due_date && $task->span === config('const.task_spans.short')) {
        $dueDate = Carbon::parse($task->due_date);
        $today = Carbon::today();
        $daysUntilDue = $today->diffInDays($dueDate, false);
        
        if ($daysUntilDue < 0) {
            $dueStatus = 'overdue';
            $dueMessage = abs($daysUntilDue) . '日超過';
        } elseif ($daysUntilDue <= 3) {
            $dueStatus = 'approaching';
            $dueMessage = '残り' . $daysUntilDue . '日';
        } else {
            $dueStatus = 'safe';
        }
    }

    // スパン別の配色
    $spanColors = [
        config('const.task_spans.long') => [
            'bg' => 'bg-gradient-to-br from-[#59B9C6]/5 to-[#59B9C6]/10',
            'border' => 'border-l-[#59B9C6]',
            'icon' => 'text-[#59B9C6]',
            'badge' => 'bg-[#59B9C6] text-white',
            'hover' => 'hover:shadow-[#59B9C6]/20',
        ],
        config('const.task_spans.mid') => [
            'bg' => 'bg-gradient-to-br from-[#7BC9A8]/5 to-[#7BC9A8]/10',
            'border' => 'border-l-[#7BC9A8]',
            'icon' => 'text-[#7BC9A8]',
            'badge' => 'bg-[#7BC9A8] text-white',
            'hover' => 'hover:shadow-[#7BC9A8]/20',
        ],
        config('const.task_spans.short') => [
            'bg' => 'bg-gradient-to-br from-[#9DD9C0]/5 to-[#9DD9C0]/10',
            'border' => 'border-l-[#9DD9C0]',
            'icon' => 'text-[#9DD9C0]',
            'badge' => 'bg-[#9DD9C0] text-gray-800',
            'hover' => 'hover:shadow-[#9DD9C0]/20',
        ],
    ];

    $colors = $spanColors[$task->span] ?? $spanColors[config('const.task_spans.mid')];
    
    $spanLabels = [
        config('const.task_spans.long')  => '長期',
        config('const.task_spans.mid')   => '中期',
        config('const.task_spans.short') => '短期',
    ];

    // グループタスク（クエスト）の場合は紫色
    $isQuest = $task->isGroupTask();
    if ($isQuest) {
        $colors['border'] = 'border-l-purple-500';
        $colors['icon'] = 'text-purple-500';
        $colors['bg'] = 'bg-gradient-to-br from-purple-500/5 to-purple-500/10';
        $colors['hover'] = 'hover:shadow-purple-500/20';
    }

    // 完了タスクの場合
    if ($isCompleted) {
        $colors['border'] = 'border-l-green-500';
        $colors['icon'] = 'text-green-500';
        $colors['bg'] = 'bg-gradient-to-br from-green-500/5 to-green-500/10';
    }

    $created = $task->created_at instanceof Carbon ? $task->created_at : Carbon::parse($task->created_at);
    $elapsedDays = max(0, $created->startOfDay()->diffInDays(now()->startOfDay()));
    
    $tagNames = $task->tags->pluck('name')->implode(',');
    $canEdit = $task->canEdit();
@endphp

<div class="task-card-modern group relative {{ $colors['bg'] }} rounded-xl shadow-md hover:shadow-xl {{ $colors['hover'] }} transition-all duration-300 border-l-4 {{ $colors['border'] }} cursor-pointer overflow-hidden"
     data-task-id="{{ $task->id }}"
     data-tags="{{ $tagNames }}"
     data-due-date="{{ $task->due_date }}"
     data-is-quest="{{ $isQuest ? 'true' : 'false' }}"
     @if($task->canEdit())
         @click="$dispatch('open-task-modal-{{ $task->id }}')">
     @else
         onclick="window.openGroupTaskDetailModal({{ $task->id }})">
     @endif
    
    {{-- 期限アラートバナー --}}
    @if($dueStatus === 'completed')
        <div class="absolute top-0 right-0 bg-gradient-to-l from-green-500 to-green-600 text-white px-3 py-1 rounded-bl-lg text-xs font-bold shadow-lg flex items-center gap-1.5 z-10">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $dueMessage }}</span>
        </div>
    @elseif($dueStatus === 'overdue')
        <div class="absolute top-0 right-0 bg-gradient-to-l from-red-500 to-red-600 text-white px-3 py-1 rounded-bl-lg text-xs font-bold shadow-lg flex items-center gap-1.5 z-10">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $dueMessage }}</span>
        </div>
    @elseif($dueStatus === 'approaching')
        <div class="absolute top-0 right-0 bg-gradient-to-l from-amber-500 to-amber-600 text-white px-3 py-1 rounded-bl-lg text-xs font-bold shadow-lg flex items-center gap-1.5 z-10 animate-pulse">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $dueMessage }}</span>
        </div>
    @endif

    {{-- カード内コンテンツ --}}
    <div class="p-4">
        {{-- ヘッダー部 --}}
        <div class="flex items-start gap-3 mb-3">
            {{-- チェックボックス or クエストアイコン --}}
            @if(!$isQuest)
                <label for="task-{{ $task->id }}" class="flex items-center cursor-pointer shrink-0 mt-1" @click.stop>
                    <input 
                        type="checkbox" 
                        id="task-{{ $task->id }}" 
                        class="task-checkbox h-5 w-5 rounded border-2 {{ $colors['icon'] }} focus:ring-2 focus:ring-offset-0 focus:ring-{{ $colors['icon'] }}" 
                        @checked($isCompleted) 
                        onclick="event.preventDefault(); document.getElementById('toggle-task-{{ $task->id }}').submit();">
                </label>
            @else
                <div class="flex items-center shrink-0 mt-1">
                    <div class="quest-badge">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                        <span>{{ $isChildTheme ? 'クエスト' : 'グループタスク' }}</span>
                    </div>
                </div>
            @endif
            
            {{-- タイトル部分 --}}
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-base text-gray-900 dark:text-white {{ $isCompleted ? 'line-through text-gray-400 dark:text-gray-500' : '' }} break-words leading-snug">
                    {{ $task->title }}
                </h3>
            </div>

            {{-- スパンバッジ --}}
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $colors['badge'] }} shrink-0 shadow-sm">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                {{ $spanLabels[$task->span] ?? $task->span }}
            </span>
        </div>
        
        {{-- 詳細情報エリア --}}
        <div class="space-y-2 text-sm">
            {{-- クエスト情報 --}}
            @if($isQuest)
                <div class="coin-display inline-flex">
                    <span>{{ number_format($task->reward) }} {{ $isChildTheme ? 'コイン' : '円' }}</span>
                </div>
                
                @if($task->isPendingApproval())
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <svg class="w-4 h-4 shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">{{ $isChildTheme ? 'チェック待ち' : '承認待ち' }}</span>
                    </div>
                @elseif($task->isApproved())
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">承認済み</span>
                    </div>
                @endif
            @endif

            {{-- 期限表示 --}}
            @if ($task->due_date)
                @php
                    if ($task->span == config('const.task_spans.short')) {
                        $dueDate = Carbon::parse($task->due_date)->format('Y/m/d');
                    } elseif ($task->span == config('const.task_spans.mid')) {
                        $dueDate = Carbon::parse($task->due_date)->format('Y');
                    } elseif ($task->span == config('const.task_spans.long')) {
                        $dueDate = $task->due_date;
                    }
                @endphp
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 {{ $colors['icon'] }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="truncate font-medium">{{ $isChildTheme ? 'しめきり' : '期限' }}: {{ $dueDate }}</span>
                </div>
            @endif
            
            {{-- 登録日と経過日数 / 完了済みラベル --}}
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 {{ $colors['icon'] }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                @if($task->completed_at)
                    <span class="truncate font-semibold text-green-600 dark:text-green-400">{{ $isChildTheme ? 'おわったよ！' : '完了済' }}</span>
                @else
                    <span class="truncate">登録: {{ $created->format('Y/m/d') }} ({{ $elapsedDays }}日経過)</span>
                @endif
            </div>

            {{-- タグ --}}
            @if($task->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach ($task->tags->take(3) as $tag)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                    @if($task->tags->count() > 3)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium text-gray-500 dark:text-gray-400">
                            +{{ $task->tags->count() - 3 }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ホバー時のグラデーションライン --}}
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-{{ $colors['icon'] }} to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

    {{-- 通常タスクの場合のみトグルフォーム --}}
    @if(!$isQuest)
        <form id="toggle-task-{{ $task->id }}" method="POST" action="{{ route('tasks.toggle', $task) }}" class="hidden">
            @csrf
            @method('PATCH')
        </form>
    @endif
</div>

{{-- モーダル --}}
@if($task->canEdit())
    @include('dashboard.modal-task-card', ['task' => $task, 'tags' => $tags ?? []])
@else
    @include('dashboard.modal-group-task-detail', ['task' => $task])
@endif