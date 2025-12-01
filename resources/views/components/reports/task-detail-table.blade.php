@props(['memberDetails', 'groupTaskSummary'])

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                タスク明細
            </h3>
            
            {{-- メンバー選択 --}}
            <div class="flex items-center gap-2">
                <label for="member-filter" class="text-sm text-gray-600 dark:text-gray-400">メンバー:</label>
                <select id="member-filter" 
                        class="block rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">全員</option>
                    @foreach($memberDetails as $userId => $member)
                        <option value="{{ $userId }}">{{ $member['user_name'] ?? $member['name'] ?? 'Unknown' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        タイプ
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        タスク名
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        メンバー
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        タグ
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        報酬
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        完了日時
                    </th>
                </tr>
            </thead>
            <tbody id="task-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {{-- 通常タスク --}}
                @foreach($memberDetails as $userId => $member)
                    @foreach($member['tasks'] ?? [] as $task)
                        <tr class="task-row hover:bg-gray-50 dark:hover:bg-gray-700/50" data-user-id="{{ $userId }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    通常
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $task['title'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $member['user_name'] ?? $member['name'] ?? 'Unknown' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($task['completed_at'])->format('m/d H:i') }}</div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                
                {{-- グループタスク --}}
                @foreach($groupTaskSummary as $userId => $member)
                    @foreach($member['tasks'] ?? [] as $task)
                        <tr class="task-row hover:bg-gray-50 dark:hover:bg-gray-700/50" data-user-id="{{ $userId }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                    グループ
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $task['title'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $member['name'] }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($task['tags'] ?? [] as $tag)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $tag }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ number_format($task['reward']) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($task['completed_at'])->format('m/d H:i') }}</div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- データがない場合 --}}
    <div id="no-data-message" class="p-6 text-center hidden">
        <p class="text-gray-500 dark:text-gray-400">選択したメンバーのタスクがありません</p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // メンバーフィルタ
        const memberFilter = document.getElementById('member-filter');
        const taskRows = document.querySelectorAll('.task-row');
        const noDataMessage = document.getElementById('no-data-message');
        const tableBody = document.getElementById('task-table-body');
        
        if (memberFilter && taskRows.length > 0) {
            memberFilter.addEventListener('change', function() {
                const selectedUserId = this.value;
                let visibleCount = 0;
                
                taskRows.forEach(row => {
                    const rowUserId = row.getAttribute('data-user-id');
                    
                    if (selectedUserId === '' || rowUserId === selectedUserId) {
                        row.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden');
                    }
                });
                
                // データがない場合のメッセージ表示制御
                if (visibleCount === 0) {
                    tableBody.classList.add('hidden');
                    noDataMessage.classList.remove('hidden');
                } else {
                    tableBody.classList.remove('hidden');
                    noDataMessage.classList.add('hidden');
                }
            });
        }
    });
</script>
@endpush
