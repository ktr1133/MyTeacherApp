@if ($scheduledTasks->isEmpty())
    {{-- 空状態 --}}
    <div class="empty-state-modern">
        <div class="empty-state-decoration">
            <div class="floating-circle circle-1"></div>
            <div class="floating-circle circle-2"></div>
            <div class="floating-circle circle-3"></div>
        </div>
        <div class="relative z-10">
            <div class="empty-state-icon-wrapper">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="empty-state-title">スケジュールタスクがありません</h3>
            <p class="empty-state-description">
                定期的に自動実行するタスクを設定できます。<br>
                毎日、毎週、毎月など、柔軟なスケジュール設定が可能です。
            </p>
            <a href="{{ route('batch.scheduled-tasks.create', ['group_id' => $groupId]) }}"
               class="btn-create-schedule">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                最初のスケジュールを作成
                <svg class="w-5 h-5 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
@else
    <div class="schedule-grid">
        @foreach ($scheduledTasks as $task)
            <div class="schedule-card-modern {{ $task->is_active ? 'active' : 'paused' }} fade-in-up" 
                 style="animation-delay: {{ $loop->index * 50 }}ms">
                
                {{-- カード上部のグラデーションバー --}}
                <div class="schedule-card-header">
                    <div class="schedule-card-gradient {{ $task->is_active ? 'active' : 'paused' }}"></div>
                </div>

                <div class="schedule-card-content">
                    {{-- ヘッダー部分 --}}
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="schedule-icon-wrapper {{ $task->is_active ? 'active' : 'paused' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="schedule-card-title">
                                    {{ $task->title }}
                                </h3>
                            </div>

                            @if ($task->description)
                                <p class="schedule-card-description">
                                    {{ $task->description }}
                                </p>
                            @endif
                        </div>

                        <div class="status-badge-modern {{ $task->is_active ? 'active' : 'paused' }}">
                            @if ($task->is_active)
                                <span class="status-pulse"></span>
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                有効
                            @else
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                一時停止
                            @endif
                        </div>
                    </div>

                    {{-- スケジュール表示 --}}
                    <div class="schedule-info-section">
                        <div class="schedule-info-header">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">実行スケジュール</span>
                        </div>
                        <div class="schedule-badges-container">
                            @foreach ($task->schedules as $schedule)
                                <div class="schedule-badge-item">
                                    <span class="schedule-badge-modern {{ $schedule['type'] }}">
                                        @if ($schedule['type'] === 'daily')
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            毎日
                                        @elseif ($schedule['type'] === 'weekly')
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                            </svg>
                                            毎週
                                        @elseif ($schedule['type'] === 'monthly')
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                            </svg>
                                            毎月
                                        @endif
                                    </span>
                                    <span class="schedule-time">
                                        @if ($schedule['type'] === 'daily')
                                            {{ $schedule['time'] ?? '' }}
                                        @elseif ($schedule['type'] === 'weekly')
                                            @php
                                                $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                                                $days = collect($schedule['days'] ?? [])->map(fn($d) => $weekdays[$d])->join('・');
                                            @endphp
                                            {{ $days }} {{ $schedule['time'] ?? '' }}
                                        @elseif ($schedule['type'] === 'monthly')
                                            {{ implode(',', $schedule['dates'] ?? []) }}日 {{ $schedule['time'] ?? '' }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 詳細情報 --}}
                    <div class="schedule-details-grid">
                        @if ($task->assigned_user_id)
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <span class="detail-label">担当</span>
                                <span class="detail-value">{{ $task->assignedUser->name ?? '未設定' }}</span>
                            </div>
                        @elseif ($task->auto_assign)
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <span class="detail-label">割当</span>
                                <span class="detail-value">ランダム</span>
                            </div>
                        @endif

                        @if ($task->reward > 0)
                            <div class="detail-item reward">
                                <div class="detail-icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="detail-label">報酬</span>
                                <span class="detail-value font-semibold text-[#59B9C6]">{{ number_format($task->reward) }}円</span>
                            </div>
                        @endif

                        @if ($task->due_duration_days || $task->due_duration_hours)
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="detail-label">期限</span>
                                <span class="detail-value">
                                    @if ($task->due_duration_days){{ $task->due_duration_days }}日@endif
                                    @if ($task->due_duration_hours){{ $task->due_duration_hours }}時間@endif
                                </span>
                            </div>
                        @endif

                        <div class="detail-item col-span-2">
                            <div class="detail-icon">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="detail-label">期間</span>
                            <span class="detail-value">{{ $task->start_date->format('Y/m/d') }} 〜 {{ $task->end_date?->format('Y/m/d') ?? '無期限' }}</span>
                        </div>
                    </div>

                    {{-- タグ --}}
                    @if ($task->tags && count($task->tags) > 0)
                        <div class="schedule-tags-section">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($task->tags as $tag)
                                    <span class="tag-chip-modern">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- アクションボタン --}}
                    <div class="schedule-actions">
                        <a href="{{ route('batch.scheduled-tasks.edit', $task->id) }}"
                           class="action-btn primary"
                           title="編集">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>編集</span>
                        </a>

                        <a href="{{ route('batch.scheduled-tasks.history', $task->id) }}"
                           class="action-btn secondary"
                           title="実行履歴">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span>履歴</span>
                        </a>

                        @if ($task->is_active)
                            <form action="{{ route('batch.scheduled-tasks.pause', $task->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="action-btn warning"
                                        title="一時停止"
                                        onclick="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('このスケジュールタスクを一時停止しますか？', () => { event.target.closest('form').submit(); }); } else { if (confirm('このスケジュールタスクを一時停止しますか？')) { event.target.closest('form').submit(); } }">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>停止</span>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('batch.scheduled-tasks.resume', $task->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="action-btn success"
                                        title="再開">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>再開</span>
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('batch.scheduled-tasks.destroy', $task->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="action-btn danger"
                                    title="削除"
                                    onclick="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('このスケジュールタスクを削除してもよろしいですか？\n作成済みのタスクは削除されません。', () => { event.target.closest('form').submit(); }); } else { if (confirm('このスケジュールタスクを削除してもよろしいですか？\n作成済みのタスクは削除されません。')) { event.target.closest('form').submit(); } }">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>削除</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif