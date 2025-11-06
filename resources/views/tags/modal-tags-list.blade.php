<div id="tag-task-modal"
        class="fixed inset-0 z-50 items-center justify-center p-4 bg-gray-900 bg-opacity-75 hidden">
    <div class="relative w-full max-w-2xl mx-auto bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b bg-[#59B9C6]/10">
            <h3 id="modal-title" class="text-base font-semibold text-gray-800">
                タグのタスク管理
            </h3>
            <button type="button" id="close-tag-task-modal" class="p-2 rounded hover:bg-gray-100" aria-label="閉じる">
                <svg class="h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-5">
            {{-- タグ情報 --}}
            <div class="mb-4">
                <div class="text-sm text-gray-500">選択中のタグ</div>
                <div class="mt-1 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-[#59B9C6]/10 text-[#59B9C6]" id="current-tag-badge">
                    <!-- filled by JS -->
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- 紐づくタスク一覧 --}}
                <div class="border rounded-lg">
                    <div class="px-3 py-2 border-b bg-gray-50 flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-700">関連するタスク</h4>
                        <span id="linked-count" class="text-xs text-gray-500"></span>
                    </div>
                    <ul id="linked-tasks" class="max-h-[45vh] overflow-y-auto divide-y">
                        <!-- filled by JS -->
                    </ul>
                </div>

                {{-- タスクを追加 --}}
                <div class="border rounded-lg">
                    <div class="px-3 py-2 border-b bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-700">タスクを追加</h4>
                    </div>
                    <div class="p-3 space-y-3">
                        <select id="available-task-select"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring-[#59B9C6]">
                            <!-- filled by JS -->
                        </select>
                        <button id="attach-task-btn"
                                class="w-full inline-flex items-center justify-center rounded-md bg-[#59B9C6] text-white px-4 py-2 text-sm font-medium shadow hover:bg-[#4AA0AB] transition disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            追加する
                        </button>
                        <p class="text-xs text-gray-500">一覧に無い場合はタスク画面から新規作成してください。</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-5 py-3 border-t bg-gray-50 flex justify-end">
            <button type="button" id="close-tag-task-modal-bottom"
                    class="rounded-md border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                閉じる
            </button>
        </div>
    </div>
</div>