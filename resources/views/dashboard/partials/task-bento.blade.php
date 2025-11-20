@php
    $todoTasks = $tasks->where('is_completed', false);
    $completedTasks = $tasks->where('is_completed', true);

    $bucketizeTasks = function($taskCollection) use ($tags) {
        $bucketMap = [];
        foreach ($taskCollection as $t) {
            if ($t->tags && $t->tags->count() > 0) {
                foreach ($t->tags as $tg) {
                    $bid = $tg->id;
                    if (!isset($bucketMap[$bid])) {
                        $bucketMap[$bid] = [
                            'id' => $tg->id,
                            'name' => $tg->name,
                            'tasks' => collect(),
                        ];
                    }
                    $bucketMap[$bid]['tasks']->push($t);
                }
            } else {
                if (!isset($bucketMap[0])) {
                    $bucketMap[0] = [
                        'id' => 0,
                        'name' => Auth::user()->theme === 'child' ? 'そのほか' : '未分類',
                        'tasks' => collect(),
                    ];
                }
                $bucketMap[0]['tasks']->push($t);
            }
        }
        return collect($bucketMap)->sortByDesc(fn($b) => $b['tasks']->count())->values();
    };

    $todoBuckets = $bucketizeTasks($todoTasks);
    $completedBuckets = $bucketizeTasks($completedTasks);
@endphp

{{-- 未完了タブ --}}
<div x-show="activeTab === 'todo'" x-transition class="task-card-enter">
    @if($todoBuckets->isEmpty())
        <div class="empty-state text-center py-16 bento-card rounded-2xl shadow-lg">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">未完了のタスクがありません</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">新しいタスクを登録してみましょう</p>
        </div>
    @else
        @include('dashboard.partials.task-bento-layout', ['buckets' => $todoBuckets, 'tags' => $tags, 'prefix' => 'todo'])
    @endif
</div>

{{-- 完了済タブ --}}
<div x-show="activeTab === 'completed'" x-transition class="task-card-enter">
    @if($completedBuckets->isEmpty())
        <div class="empty-state text-center py-16 bento-card rounded-2xl shadow-lg">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">完了済のタスクがありません</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">タスクを完了するとここに表示されます</p>
        </div>
    @else
        @include('dashboard.partials.task-bento-layout', ['buckets' => $completedBuckets, 'tags' => $tags, 'prefix' => 'completed'])
    @endif
</div>