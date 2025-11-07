@if ($executions->isEmpty())
    {{-- 空状態 --}}
    <div class="empty-state">
        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h3 class="empty-state-title">実行履歴がありません</h3>
        <p class="empty-state-description">
            このスケジュールタスクはまだ実行されていません。<br>
            スケジュールに従って自動的にタスクが作成されます。
        </p>
    </div>
@else
    <div class="bento-card rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                実行履歴（最新{{ $executions->count() }}件）
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="execution-history-table">
                <thead>
                    <tr>
                        <th>実行日時</th>
                        <th>ステータス</th>
                        <th>作成タスク</th>
                        <th>担当者</th>
                        <th>完了日時</th>
                        <th>備考</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($executions as $execution)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ $execution->executed_at->format('Y/m/d H:i') }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="execution-status-badge {{ $execution->status }}">
                                    @if ($execution->status === 'success')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        作成成功
                                    @elseif ($execution->status === 'failed')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        失敗
                                    @else
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                        </svg>
                                        スキップ
                                    @endif
                                </span>
                            </td>
                            <td>
                                @if ($execution->task_id)
                                    <a href="{{ route('task.show', $execution->task_id) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:underline">
                                        タスク #{{ $execution->task_id }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($execution->assigned_user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ mb_substr($execution->assigned_user->name, 0, 1) }}
                                        </div>
                                        <span>{{ $execution->assigned_user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($execution->task && $execution->task->completed_at)
                                    <span class="text-green-600 dark:text-green-400">
                                        {{ $execution->task->completed_at->format('Y/m/d H:i') }}
                                    </span>
                                @elseif ($execution->task)
                                    <span class="text-gray-400">未完了</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($execution->error_message)
                                    <div class="max-w-xs truncate text-red-600 dark:text-red-400" title="{{ $execution->error_message }}">
                                        {{ $execution->error_message }}
                                    </div>
                                @elseif ($execution->skip_reason)
                                    <div class="max-w-xs truncate text-yellow-600 dark:text-yellow-400" title="{{ $execution->skip_reason }}">
                                        {{ $execution->skip_reason }}
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif