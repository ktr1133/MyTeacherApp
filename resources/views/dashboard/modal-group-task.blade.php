<div
     id="group-task-modal-wrapper"
     class="modal fixed inset-0 z-50 items-center justify-center p-safe modal-overlay bg-gray-900 bg-opacity-75 hidden opacity-0 transition-opacity duration-300"
     data-modal-state="closed"
     >

    {{-- モーダルメインパネル --}}
    <div 
         id="group-task-modal-content"
         class="modal-content modal-panel bg-white w-full max-w-2xl mx-auto overflow-hidden transform transition-all duration-300 translate-y-4 scale-95">

        {{-- ヘッダー --}}
        <div class="px-6 py-4 border-b flex justify-between items-center bg-purple-600/10">
            <h3 class="text-xl font-semibold text-gray-800">{{ __('グループタスク登録') }}</h3>
            <button 
                type="button"
                id="close-group-modal-btn" 
                class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-600 rounded p-1" 
                aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- 本文（縦スクロール可 --}}
        <div class="p-6 modal-body">
            <form id="group-task-form" method="POST" action="{{ route('tasks.store') }}">
                @csrf
                <input type="hidden" name="is_group_task" value="1">
                <input type="hidden" name="requires_approval" value="1">
                
                {{-- 画像必須設定 --}}
                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="requires_image" value="1"
                               class="form-checkbox h-4 w-4 text-purple-600 focus:ring-purple-600 rounded">
                        <span class="text-sm text-gray-700">{{ __('完了時に画像添付を必須にする') }}</span>
                    </label>
                </div>

                {{-- 担当者選択をnullable化 --}}
                <div class="mb-4">
                    <label for="assignedUserId" class="block text-sm font-medium text-gray-700">{{ __('担当者（任意）') }}</label>
                    <select id="assignedUserId" name="assigned_user_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-600 focus:ring focus:ring-purple-600/50">
                        <option value="">{{ __('未割り当て') }}</option>
                        @if(Auth::user()->group)
                            @foreach(Auth::user()->group->users as $member)
                                <option value="{{ $member->id }}">{{ $member->username }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- タスク選択方式（新規 or テンプレート） --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('タスク作成方法') }}</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="task_mode" value="new" checked
                                   class="form-radio h-4 w-4 text-purple-600 focus:ring-purple-600">
                            <span class="ml-2 text-sm text-gray-700">新規作成</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="task_mode" value="template"
                                   class="form-radio h-4 w-4 text-purple-600 focus:ring-purple-600">
                            <span class="ml-2 text-sm text-gray-700">過去のタスクから選択</span>
                        </label>
                    </div>
                </div>

                {{-- 新規作成フォーム --}}
                <div id="new-task-form">
                    <div class="mb-4">
                        <label for="groupTaskTitle" class="block text-sm font-medium text-gray-700">{{ __('タスク名') }}</label>
                        <input type="text" id="groupTaskTitle" name="title" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-600 focus:ring focus:ring-purple-600/50">
                    </div>

                    <div class="mb-4">
                        <label for="groupTaskDescription" class="block text-sm font-medium text-gray-700">{{ __('説明') }}</label>
                        <textarea id="groupTaskDescription" name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-600 focus:ring focus:ring-purple-600/50"></textarea>
                    </div>
                </div>

                {{-- テンプレート選択フォーム --}}
                <div id="template-task-form" style="display: none;">
                    <div class="mb-4">
                        <label for="taskTemplate" class="block text-sm font-medium text-gray-700">{{ __('過去のタスクから選択') }}</label>
                        <select id="taskTemplate" name="template_task_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-600 focus:ring focus:ring-purple-600/50">
                            <option value="">{{ __('選択してください') }}</option>
                            @foreach(Auth::user()->tasks()->orderBy('created_at', 'desc')->take(50)->get() as $task)
                                <option value="{{ $task->id }}" data-title="{{ $task->title }}" data-description="{{ $task->description }}">
                                    {{ $task->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- プレビューエリア --}}
                    <div id="template-preview" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200" style="display: none;">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">{{ __('プレビュー') }}</h4>
                        <p class="text-sm text-gray-600"><strong>タイトル:</strong> <span id="preview-title"></span></p>
                        <p class="text-sm text-gray-600 mt-2"><strong>説明:</strong> <span id="preview-description"></span></p>
                    </div>
                </div>

                {{-- 期限（短期固定なので日付選択のみ） --}}
                <div class="mb-4">
                    <label for="groupDueDate" class="block text-sm font-medium text-gray-700">{{ __('期限') }}</label>
                    <input type="date" id="groupDueDate" name="due_date" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-600 focus:ring focus:ring-purple-600/50"
                           min="{{ date('Y-m-d') }}">
                </div>

                {{-- 報酬設定 --}}
                <div class="mb-4">
                    <label for="taskReward" class="block text-sm font-medium text-gray-700">{{ __('報酬') }}</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" id="taskReward" name="reward" min="0" step="1" required
                               class="block w-full rounded-md border-gray-300 pl-3 pr-12 focus:border-purple-600 focus:ring focus:ring-purple-600/50"
                               placeholder="0">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">円</span>
                        </div>
                    </div>
                </div>

                {{-- タグ入力 --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('タグ') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <label class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 cursor-pointer transition">
                                <input type="checkbox" 
                                    name="tags[]" 
                                    value="{{ $tag->name }}"
                                    class="form-checkbox h-4 w-4 text-purple-600 focus:ring-purple-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
        {{-- フッター（常時表示） --}}
        <div class="px-6 py-3 border-t bg-white flex justify-end gap-3 shrink-0">
            <button type="button" id="cancel-group-task-btn"
                    class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition">
                {{ __('キャンセル') }}
            </button>
            <button type="submit" form="group-task-form"
                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition">
                {{ __('登録') }}
            </button>
        </div>
    </div>
</div>