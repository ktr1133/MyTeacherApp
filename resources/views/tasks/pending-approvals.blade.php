<x-app-layout>
    <div x-data="{ showSidebar: false }" class="flex min-h-[100dvh] bg-[#F3F3F2]">
        <x-layouts.sidebar />

        <div class="flex-1 flex flex-col overflow-y-auto">
            <header class="sticky top-0 z-20 border-b bg-white shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-xl text-gray-800">承認待ちタスク</h2>
                    <a href="{{ route('dashboard') }}" 
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                        戻る
                    </a>
                </div>
            </header>

            <main class="flex-1 p-6">
                @php
                    // 自分が承認者として設定されている承認待ちタスクのみ
                    $myPendingTasks = $pendingTasks->filter(fn($t) => $t->shouldShowInApprovalList(Auth::id()));
                @endphp

                @if($myPendingTasks->count() > 0)
                    <div class="space-y-4">
                        @foreach($myPendingTasks as $task)
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800">{{ $task->title }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">担当: {{ $task->user->username }}</p>
                                        <p class="text-sm text-gray-600">完了申請: {{ $task->completed_at->format('Y/m/d H:i') }}</p>
                                        
                                        @if($task->description)
                                            <p class="text-sm text-gray-700 mt-2">{{ $task->description }}</p>
                                        @endif
                                        
                                        @if($task->reward)
                                            <p class="text-sm font-medium text-purple-600 mt-2">報酬: {{ number_format($task->reward) }}円</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- 添付画像 --}}
                                @if($task->images->count() > 0)
                                    <div class="mb-4">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">添付画像</h4>
                                        <div class="grid grid-cols-3 gap-2">
                                            @foreach($task->images as $image)
                                                <img src="{{ Storage::url($image->file_path) }}" 
                                                     class="w-full h-32 object-cover rounded-lg border cursor-pointer hover:opacity-75"
                                                     onclick="window.open('{{ Storage::url($image->file_path) }}', '_blank')">
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- 承認/却下ボタン --}}
                                <div class="flex gap-3">
                                    <form method="POST" action="{{ route('tasks.approve', $task) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('このタスクを承認しますか？')"
                                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                            承認する
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="{{ route('tasks.reject', $task) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('このタスクを却下しますか？\n担当者は再度完了申請が必要になります。')"
                                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                            却下する
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 bg-white rounded-lg shadow">
                        <p class="text-gray-500">承認待ちのタスクはありません。</p>
                    </div>
                @endif
            </main>
        </div>
    </div>
</x-app-layout>