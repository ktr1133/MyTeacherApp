<x-app-layout>
    {{-- ダッシュボード専用CSS --}}
    @push('styles')
        @vite(['resources/css/dashboard.css'])
    @endpush

    {{-- メイン背景色: #F3F3F2 を適用 --}}
    <div x-data="{ 
        showTaskModal: false, 
        showDecompositionModal: false, 
        showRefineModal: false,
        isProposing: false,
        taskTitle: '',
        taskSpan: 'mid', 
        refinementPoints: '',
        decompositionProposal: null,
        showSidebar: false,
        activeTab: 'todo',
        
        // 外部JSファイルから呼び出される関数をラップ
        startDecomposition: function(isRefinement = false) {
            if (typeof window.decomposeTask === 'function') {
                window.decomposeTask.call(this, isRefinement);
            }
        },
        confirmProposal: function() {
            if (typeof window.acceptProposal === 'function') {
                window.acceptProposal.call(this);
            }
        }
    }" class="flex min-h-[100dvh] bg-[#F3F3F2]">

        {{-- A. サイドバー (デスクトップ版) --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツエリア --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            
            {{-- B. ヘッダー / 検索・フィルタ とタスク登録ボタン --}}
            <header class="sticky top-0 z-20 border-b bg-white shadow-sm">
                <!-- トップバー -->
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
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
                        
                        <!-- デスクトップではトップバー内にフィルタを表示 -->
                        <div class="hidden lg:flex flex-1 min-w-0">
                            <x-task-filter />
                        </div>
                    </div>

                    <!-- タスク登録ボタン群 -->
                    <div class="flex items-center gap-2">
                        <!-- 通常のタスク登録ボタン -->
                        <button 
                            id="open-task-modal-btn"
                            class="inline-flex items-center justify-center shrink-0 rounded-full bg-[#59B9C6] text-white shadow hover:bg-[#4AA0AB] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] transition
                                h-10 w-10
                                sm:h-10 sm:w-auto sm:rounded-lg sm:px-4 sm:py-2.5
                                lg:px-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-4 sm:w-4 sm:mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="hidden sm:inline-block text-sm font-medium whitespace-nowrap">タスク登録</span>
                        </button>

                        <!-- グループタスク登録ボタン（グループ編集権限がある場合のみ表示） -->
                        @if(Auth::user()->canEditGroup())
                            <button 
                                id="open-group-task-modal-btn"
                                class="inline-flex items-center justify-center shrink-0 rounded-full bg-purple-600 text-white shadow hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition
                                    h-10 w-10
                                    sm:h-10 sm:w-auto sm:rounded-lg sm:px-4 sm:py-2.5
                                    lg:px-5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-4 sm:w-4 sm:mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                                <span class="hidden sm:inline-block text-sm font-medium whitespace-nowrap">グループタスク</span>
                            </button>
                        @endif
                    </div>

                    <!-- Settings Dropdown -->
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->username }}</div>

                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('アカウント') }}
                                </x-dropdown-link>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('ログアウト') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>

                <!-- モバイル用セカンダリーバー：フィルタ群を下段にフル幅で配置 -->
                <div class="lg:hidden px-4 pb-3 pt-2 border-t border-gray-100 bg-white">
                    <x-task-filter />
                </div>

                {{-- タブナビゲーション --}}
                <div class="flex gap-2 border-t border-gray-200 px-4 lg:px-6 bg-white">
                    <button 
                        @click="activeTab = 'todo'"
                        :class="activeTab === 'todo' ? 'border-[#59B9C6] text-[#59B9C6]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 border-b-2 font-medium text-sm transition">
                        未完了
                    </button>
                    <button 
                        @click="activeTab = 'completed'"
                        :class="activeTab === 'completed' ? 'border-[#59B9C6] text-[#59B9C6]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 border-b-2 font-medium text-sm transition">
                        完了済
                    </button>
                </div>
            </header>

            {{-- C. タグ別ベースの弁当箱レイアウト --}}
            <main class="flex-1">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">

                    @php
                        // 未完了タスクと完了済タスクを分離
                        $todoTasks = $tasks->where('is_completed', false);
                        $completedTasks = $tasks->where('is_completed', true);

                        // タグごとにタスクをバケット化する関数
                        $bucketizeTasks = function($taskCollection) use ($tags) {
                            $bucketMap = [];
                            foreach ($taskCollection as $t) {
                                if ($t->tags && $t->tags->count() > 0) {
                                    foreach ($t->tags as $tg) {
                                        $bid = $tg->id;
                                        if (!isset($bucketMap[$bid])) {
                                            $bucketMap[$bid] = [
                                                'id' => $tg->id,
                                                'name' => $tg->name,
                                                'tasks' => collect(),
                                            ];
                                        }
                                        $bucketMap[$bid]['tasks']->push($t);
                                    }
                                } else {
                                    if (!isset($bucketMap[0])) {
                                        $bucketMap[0] = [
                                            'id' => 0,
                                            'name' => '未分類',
                                            'tasks' => collect(),
                                        ];
                                    }
                                    $bucketMap[0]['tasks']->push($t);
                                }
                            }
                            return collect($bucketMap)->sortByDesc(fn($b) => $b['tasks']->count())->values();
                        };

                        $todoBuckets = $bucketizeTasks($todoTasks);
                        $completedBuckets = $bucketizeTasks($completedTasks);
                    @endphp

                    {{-- 未完了タブ --}}
                    <div x-show="activeTab === 'todo'" x-transition>
                        @if($todoBuckets->isEmpty())
                            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">未完了のタスクがありません。</p>
                            </div>
                        @else
                            @include('dashboard.partials.task-bento-layout', ['buckets' => $todoBuckets, 'tags' => $tags, 'prefix' => 'todo'])
                        @endif
                    </div>

                    {{-- 完了済タブ --}}
                    <div x-show="activeTab === 'completed'" x-transition>
                        @if($completedBuckets->isEmpty())
                            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">完了済のタスクがありません。</p>
                            </div>
                        @else
                            @include('dashboard.partials.task-bento-layout', ['buckets' => $completedBuckets, 'tags' => $tags, 'prefix' => 'completed'])
                        @endif
                    </div>
                </div>
            </main>
        </div>
        {{-- モーダル HTML を x-data スコープ内でインクルード --}}
        @include('dashboard.modal-dashboard-task')
        {{-- グループタスクモーダル --}}
        @if(Auth::user()->canEditGroup())
            @include('dashboard.modal-group-task')
        @endif
    </div>
    
    {{-- JSファイルを読み込む (Viteの読み込みを想定) --}}
    @push('scripts')
        <!-- Alpine.js を先に読み込む -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        @vite(['resources/js/dashboard/dashboard.js'])
        @if(Auth::user()->canEditGroup())
            @vite(['resources/js/dashboard/group-task.js'])
        @endif
    @endpush
</x-app-layout>