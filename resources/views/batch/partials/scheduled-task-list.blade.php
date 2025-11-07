
@if ($scheduledTasks->isEmpty())
    {{-- 空状態 --}}
    <div class="empty-state">
        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="empty-state-title">スケジュールタスクがありません</h3>
        <p class="empty-state-description">
            定期的に自動実行するタスクを設定できます。<br>
            毎日、毎週、毎月など、柔軟なスケジュール設定が可能です。
        </p>
        <a href="{{ route('batch.scheduled-tasks.create', ['group_id' => $groupId]) }}"
           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            最初のスケジュールを作成
        </a>
    </div>
@else
    <div class="grid gap-4 md:gap-6">
        @foreach ($scheduledTasks as $task)
            <div class="schedule-card {{ $task->is_active ? 'active' : 'paused' }} fade-in-up">
                <div class="flex items-start justify-between gap-4">
                    {{-- タスク情報 --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                                {{ $task->title }}
                            </h3>
                            <span class="status-badge {{ $task->is_active ? 'active' : 'paused' }}">
                                @if ($task->is_active)
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    有効
                                @else
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    一時停止
                                @endif
                            </span>
                        </div>

                        @if ($task->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                {{ $task->description }}
                            </p>
                        @endif

                        {{-- スケジュール表示 --}}
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @foreach ($task->schedules as $schedule)
                                <span class="schedule-badge {{ $schedule['type'] }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @if ($schedule['type'] === 'daily')
                                        毎日 {{ $schedule['time'] ?? '' }}
                                    @elseif ($schedule['type'] === 'weekly')
                                        @php
                                            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                                            $days = collect($schedule['days'] ?? [])->map(fn($d) => $weekdays[$d])->join('・');
                                        @endphp
                                        毎週{{ $days }} {{ $schedule['time'] ?? '' }}
                                    @elseif ($schedule['type'] === 'monthly')
                                        毎月{{ implode(',', $schedule['dates'] ?? []) }}日 {{ $schedule['time'] ?? '' }}
                                    @endif
                                </span>
                            @endforeach
                        </div>

                        {{-- 詳細情報 --}}
                        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            @if ($task->assigned_user_id)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>担当: {{ $task->assignedUser->name ?? '未設定' }}</span>
                                </div>
                            @elseif ($task->auto_assign)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>ランダム割り当て</span>
                                </div>
                            @endif

                            @if ($task->reward > 0)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>報酬: {{ $task->reward }}円</span>
                                </div>
                            @endif

                            @if ($task->due_duration_days || $task->due_duration_hours)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>期限: 
                                        @if ($task->due_duration_days){{ $task->due_duration_days }}日@endif
                                        @if ($task->due_duration_hours){{ $task->due_duration_hours }}時間@endif
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $task->start_date->format('Y/m/d') }} 〜 {{ $task->end_date?->format('Y/m/d') ?? '無期限' }}</span>
                            </div>
                        </div>

                        {{-- タグ --}}
                        @if ($task->tags && count($task->tags) > 0)
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                @foreach ($task->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-700">
                                        #{{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- アクションボタン --}}
                    <div class="flex flex-col gap-2 shrink-0">
                        <a href="{{ route('batch.scheduled-tasks.edit', $task->id) }}"
                           class="p-2 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                           title="編集">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>

                        <a href="{{ route('batch.scheduled-tasks.history', $task->id) }}"
                           class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                           title="実行履歴">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </a>

                        @if ($task->is_active)
                            <form action="{{ route('batch.scheduled-tasks.pause', $task->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="p-2 rounded-lg text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors"
                                        title="一時停止"
                                        onclick="return confirm('このスケジュールタスクを一時停止しますか？')">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('batch.scheduled-tasks.resume', $task->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="p-2 rounded-lg text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                        title="再開">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('batch.scheduled-tasks.destroy', $task->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="remove-schedule-btn"
                                    title="削除"
                                    onclick="return confirm('このスケジュールタスクを削除してもよろしいですか？\n作成済みのタスクは削除されません。')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif