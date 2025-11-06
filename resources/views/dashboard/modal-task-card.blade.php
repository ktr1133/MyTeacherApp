@props(['task'])

<div 
    x-data="{ 
        showModal: false,
        title: {{ Js::from($task->title) }},
        description: {{ Js::from($task->description ?? '') }},
        span: {{ $task->span ?? config('const.task_spans.mid') }},
        due_date: {{ Js::from($task->due_date ?? '') }},
        selectedTags: {{ Js::from($task->tags->pluck('id')->toArray()) }},
        
        open() {
            this.showModal = true;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.showModal = false;
            document.body.classList.remove('overflow-hidden');
        },
        submit() {
            document.getElementById('edit-task-form-{{ $task->id }}').submit();
        }
    }"
    @open-task-modal-{{ $task->id }}.window="open()"
    @keydown.escape.window="showModal && close()">
    
    {{-- モーダルオーバーレイ --}}
    <div 
        x-show="showModal"
        x-transition.opacity
        @click="close()"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        style="display: none;">
        
        {{-- モーダルコンテンツ --}}
        <div 
            @click.stop
            x-show="showModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="modal-panel bg-white shadow-2xl w-full max-w-2xl">
            
            {{-- ヘッダー --}}
            <div class="px-6 py-4 border-b bg-[#59B9C6]/10 flex items-center justify-between shrink-0">
                <h3 class="text-xl font-semibold text-gray-800">タスク編集</h3>
                <button 
                    @click="close()"
                    class="text-gray-500 hover:text-gray-700 transition p-1 rounded-full hover:bg-gray-200"
                    aria-label="閉じる">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- スクロール可能なコンテンツエリア --}}
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <form id="edit-task-form-{{ $task->id }}" method="POST" action="{{ route('tasks.update', $task->id) }}">
                    @csrf
                    @method('PUT')

                    {{-- タイトル --}}
                    <div class="mb-4">
                        <label for="title-{{ $task->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                            タスク名 <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title-{{ $task->id }}"
                            name="title"
                            x-model="title"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent">
                    </div>

                    {{-- 詳細説明 --}}
                    <div class="mb-4">
                        <label for="description-{{ $task->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                            詳細説明
                        </label>
                        <textarea 
                            id="description-{{ $task->id }}"
                            name="description"
                            x-model="description"
                            rows="6"
                            placeholder="タスクの詳細な説明を入力してください"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent resize-none"></textarea>
                    </div>

                    {{-- スパン選択 --}}
                    <div class="mb-4">
                        <label for="span-{{ $task->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                            スパン <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="span-{{ $task->id }}"
                            name="span"
                            x-model.number="span"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent">
                            <option value="{{ config('const.task_spans.short') }}">短期</option>
                            <option value="{{ config('const.task_spans.mid') }}">中期</option>
                            <option value="{{ config('const.task_spans.long') }}">長期</option>
                        </select>
                    </div>

                    {{-- 期限 --}}
                    <div class="mb-4">
                        <label for="due_date-{{ $task->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                            期限
                        </label>
                        
                        {{-- 短期: 日付入力 --}}
                        <div x-show="span == {{ config('const.task_spans.short') }}">
                            <input 
                                type="date"
                                name="due_date"
                                x-model="due_date"
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent">
                        </div>

                        {{-- 中期: 年選択 --}}
                        <div x-show="span == {{ config('const.task_spans.mid') }}">
                            <select 
                                name="due_date"
                                x-model="due_date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent">
                                @php
                                    $currentYear = date('Y');
                                    $years = range($currentYear, $currentYear + 5);
                                @endphp
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}年</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 長期: テキスト入力 --}}
                        <div x-show="span == {{ config('const.task_spans.long') }}">
                            <input 
                                type="text"
                                name="due_date"
                                x-model="due_date"
                                placeholder="例：5年後"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent">
                        </div>
                    </div>

                    {{-- 画像アップロード（グループタスクのみ） --}}
                    @if($task->requires_approval)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                画像 @if($task->requires_image)<span class="text-red-500">*必須</span>@endif
                            </label>
                            
                            {{-- 既存画像一覧 --}}
                            @if($task->images->count() > 0)
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    @foreach($task->images as $image)
                                        <div class="relative group">
                                            <img src="{{ Storage::url($image->file_path) }}" 
                                                 class="w-full h-32 object-cover rounded-lg border">
                                            <button type="button"
                                                    onclick="if(confirm('この画像を削除しますか？')) { document.getElementById('delete-image-form-{{ $image->id }}').submit(); }"
                                                    class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <form id="delete-image-form-{{ $image->id }}" 
                                              method="POST" 
                                              action="{{ route('tasks.delete-image', $image) }}" 
                                              class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                            
                            {{-- アップロードフォーム --}}
                            <form id="upload-image-form-{{ $task->id }}" 
                                  method="POST" 
                                  action="{{ route('tasks.upload-image', $task) }}" 
                                  enctype="multipart/form-data"
                                  class="flex gap-2">
                                @csrf
                                <input type="file" name="image" accept="image/*" required
                                       class="flex-1 text-sm border border-gray-300 rounded-lg p-2">
                                <button type="submit"
                                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                                    アップロード
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- タグ選択 --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">タグ</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags ?? [] as $tag)
                                <label class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 cursor-pointer transition">
                                    <input 
                                        type="checkbox" 
                                        name="tags[]" 
                                        value="{{ $tag->id }}"
                                        x-model="selectedTags"
                                        class="form-checkbox h-4 w-4 text-[#59B9C6] focus:ring-[#59B9C6] rounded">
                                    <span class="ml-2 text-sm text-gray-700">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            {{-- フッター --}}
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center shrink-0">
                {{-- 削除ボタン（左側） --}}
                <button 
                    type="button"
                    onclick="if(confirm('このタスクを削除しますか？')) { document.getElementById('delete-task-form-{{ $task->id }}').submit(); }"
                    class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    削除
                </button>

                {{-- 右側のボタン群 --}}
                <div class="flex gap-3">
                    <button 
                        type="button"
                        @click="close()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        キャンセル
                    </button>
                    <button 
                        type="button"
                        @click="submit()"
                        class="px-4 py-2 text-sm font-medium text-white bg-[#59B9C6] rounded-lg hover:bg-[#4AA0AB] transition">
                        保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 削除用フォーム --}}
    <form id="delete-task-form-{{ $task->id }}" method="POST" action="{{ route('tasks.destroy') }}" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" name="task_id" value="{{ $task->id }}">
    </form>
</div>