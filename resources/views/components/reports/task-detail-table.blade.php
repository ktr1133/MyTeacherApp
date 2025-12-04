@props(['memberDetails', 'groupTaskSummary'])

<div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                タスク明細
            </h3>
            
            {{-- メンバー選択と概況レポート生成ボタン --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="member-filter" class="member-filter-label text-sm text-gray-600 dark:text-gray-400">メンバー:</label>
                    <select id="member-filter" 
                            class="block rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">全員</option>
                        @foreach($memberDetails as $userId => $member)
                            <option value="{{ $userId }}" data-name="{{ $member['user_name'] ?? 'Unknown' }}">{{ $member['user_name'] ?? 'Unknown' }}</option>
                        @endforeach
                    </select>
                </div>
                
                <button id="generate-member-summary-btn" 
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA5B2] hover:to-purple-700 text-white text-sm font-semibold rounded-lg transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        title="概況レポート生成"
                        disabled>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="button-text">概況レポート生成</span>
                </button>
            </div>
        </div>
    </div>
    
    {{-- デスクトップ: テーブル表示 --}}
    <div class="hidden md:block overflow-x-auto">
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
                                <div class="text-sm text-gray-900 dark:text-white">{{ $member['user_name'] ?? $member['name'] ?? 'Unknown' }}</div>
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
    
    {{-- モバイル: カード表示 --}}
    <div id="task-card-container" class="md:hidden p-4 space-y-4">
        {{-- 通常タスク --}}
        @foreach($memberDetails as $userId => $member)
            @foreach($member['tasks'] ?? [] as $task)
                <div class="task-card glass-card feature-card p-4 rounded-2xl border shadow-lg" data-user-id="{{ $userId }}">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex-1 pr-2">
                            {{ $task['title'] }}
                        </h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 flex-shrink-0">
                            通常
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>{{ $member['user_name'] ?? $member['name'] ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($task['completed_at'])->format('Y/m/d H:i') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
        
        {{-- グループタスク --}}
        @foreach($groupTaskSummary as $userId => $member)
            @foreach($member['tasks'] ?? [] as $task)
                <div class="task-card glass-card feature-card p-4 rounded-2xl border shadow-lg" data-user-id="{{ $userId }}">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex-1 pr-2">
                            {{ $task['title'] }}
                        </h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 flex-shrink-0">
                            グループ
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>{{ $member['user_name'] ?? $member['name'] ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($task['completed_at'])->format('Y/m/d H:i') }}</span>
                        </div>
                        @if(!empty($task['tags']))
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($task['tags'] as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                        @endif
                        @if(isset($task['reward']) && $task['reward'] > 0)
                        <div class="flex items-center text-amber-600 dark:text-amber-400 font-medium mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>報酬: {{ number_format($task['reward']) }}円</span>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
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
        const taskCards = document.querySelectorAll('.task-card');
        const noDataMessage = document.getElementById('no-data-message');
        const tableBody = document.getElementById('task-table-body');
        const cardContainer = document.getElementById('task-card-container');
        
        if (memberFilter && (taskRows.length > 0 || taskCards.length > 0)) {
            memberFilter.addEventListener('change', function() {
                const selectedUserId = this.value;
                let visibleRowCount = 0;
                let visibleCardCount = 0;
                
                // テーブル行のフィルタリング
                taskRows.forEach(row => {
                    const rowUserId = row.getAttribute('data-user-id');
                    
                    if (selectedUserId === '' || rowUserId === selectedUserId) {
                        row.classList.remove('hidden');
                        visibleRowCount++;
                    } else {
                        row.classList.add('hidden');
                    }
                });
                
                // カードのフィルタリング
                taskCards.forEach(card => {
                    const cardUserId = card.getAttribute('data-user-id');
                    
                    if (selectedUserId === '' || cardUserId === selectedUserId) {
                        card.classList.remove('hidden');
                        visibleCardCount++;
                    } else {
                        card.classList.add('hidden');
                    }
                });
                
                // データがない場合のメッセージ表示制御
                if (visibleRowCount === 0 && visibleCardCount === 0) {
                    if (tableBody) tableBody.classList.add('hidden');
                    if (cardContainer) cardContainer.classList.add('hidden');
                    noDataMessage.classList.remove('hidden');
                } else {
                    if (tableBody) tableBody.classList.remove('hidden');
                    if (cardContainer) cardContainer.classList.remove('hidden');
                    noDataMessage.classList.add('hidden');
                }
            });
        }
    });
</script>
@endpush
