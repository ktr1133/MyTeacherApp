<x-app-layout>
    @push('styles')
        @vite(['resources/css/tags.css'])
    @endpush

    {{-- ダッシュボードと同じレイアウト構造 --}}
    <div
        id="tag-page"
        x-data="{ showSidebar: false }"
        class="flex min-h-[100dvh] bg-[#F3F3F2]"
        data-app-origin="{{ url('') }}"
        data-tags-base="/tags"
    >

        {{-- A. サイドバー (デスクトップ版) --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- B. ヘッダー --}}
            <header class="sticky top-0 z-20 border-b bg-white shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <!-- ハンバーガー（モバイルのみ表示） -->
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-md border border-gray-200 hover:bg-gray-50 shrink-0"
                            @click="showSidebar = true"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <h1 class="text-base lg:text-lg font-bold text-[#59B9C6] tracking-wide">
                            タグ管理
                        </h1>
                    </div>

                    <a href="{{ route('dashboard') }}"
                       class="text-sm text-gray-600 hover:text-[#59B9C6] transition flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">タスクリストへ戻る</span>
                    </a>
                </div>
            </header>

            {{-- C. メインコンテンツ --}}
            <main class="flex-1">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    {{-- 新規タグ作成 --}}
                    <div class="bg-white rounded-lg shadow-sm border mb-6">
                        <div class="px-4 py-4 border-b flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700">新規タグを作成</h2>
                        </div>
                        <form method="POST" action="{{ route('tags.store') }}" class="p-4 flex flex-col sm:flex-row gap-3 sm:items-center">
                            @csrf
                            <input type="text" name="name" required
                                   placeholder="タグ名を入力してください。"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring-[#59B9C6]">
                            <button type="submit"
                                    class="inline-flex items-center justify-center rounded-md bg-[#59B9C6] text-white px-4 py-2 text-sm font-medium shadow hover:bg-[#4AA0AB] transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                追加
                            </button>
                        </form>
                    </div>

                    {{-- タグ一覧 --}}
                    <div class="bg-white rounded-lg shadow-sm border">
                        <div class="px-4 py-4 border-b flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700">タグ一覧</h2>
                            <p class="text-xs text-gray-500">クリックで関連するタスクを表示</p>
                        </div>

                        <div class="p-4">
                            @if(isset($tags) && count($tags) > 0)
                                <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                    @foreach($tags as $tag)
                                        <li class="group border rounded-lg p-3 bg-white hover:bg-[#59B9C6]/5 transition">
                                            <div class="flex items-start justify-between gap-3">
                                                {{-- タグの表示（クリックでモーダル） --}}
                                                <button type="button"
                                                        class="text-left flex-1"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->name }}"
                                                        data-action="open-tag-modal">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#59B9C6]/10 text-[#59B9C6]">
                                                            タグ
                                                        </span>
                                                        <span class="text-sm font-semibold text-gray-800 group-hover:text-[#59B9C6]">
                                                            {{ $tag->name }}
                                                        </span>
                                                    </div>
                                                    @php
                                                        // タグに関連付けられたタスク数を表示
                                                        $task = collect($tasks)->where('name', $tag->name)->first();
                                                        $taskCount = $task ? $task->tasks_count : 0;
                                                    @endphp
                                                    @if($taskCount > 0)
                                                        <div class="mt-1 text-xs text-gray-500">
                                                            関連するタスク: {{ $taskCount }} 件
                                                        </div>
                                                    @endif
                                                </button>

                                                {{-- 編集/削除ボタン --}}
                                                <div class="flex items-center gap-2">
                                                    {{-- 名前編集（インライン） --}}
                                                    <details class="relative">
                                                        <summary class="list-none cursor-pointer p-2 rounded hover:bg-gray-100 text-gray-500" aria-label="編集">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                            </svg>
                                                        </summary>
                                                        <div class="absolute right-0 mt-2 w-56 bg-white border rounded-lg shadow-lg p-3 z-10">
                                                            <form method="POST" action="{{ route('tags.update', $tag->id) }}" class="space-y-2">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="text" name="name" value="{{ $tag->name }}" required
                                                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#59B9C6] focus:ring-[#59B9C6]">
                                                                <button type="submit"
                                                                        class="w-full rounded-md bg-[#59B9C6] text-white px-3 py-2 text-sm font-medium hover:bg-[#4AA0AB] transition">
                                                                    保存
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </details>

                                                    {{-- 削除 --}}
                                                    <form method="POST" action="{{ route('tags.destroy', $tag->id) }}"
                                                          onsubmit="return confirm('タグ「{{ $tag->name }}」を削除しますか？この操作は取り消せません。');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-2 rounded hover:bg-red-50 text-red-500" aria-label="削除">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m-3 0h14"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="empty-state bg-white rounded-lg border text-center p-8">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">タグがありません。上のフォームから作成してください。</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>

        {{-- タグのタスク管理モーダル --}}
        @include('tags.modal-tags-list')
    </div>

    @push('scripts')
        <!-- Alpine.js を先に読み込む -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        @vite(['resources/js/tags/tags.js'])
    @endpush
</x-app-layout>