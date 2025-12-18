<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/group-tasks/edit.js'])
    @endpush

    <x-layouts.avatar-event-common />

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            <header class="sticky top-0 z-20 border-b border-gray-200/50 dark:border-gray-700/50 dashboard-header-blur shadow-sm {{ $isChildTheme ? 'child-theme' : '' }}">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <a href="{{ route('group-tasks.index') }}" class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div class="flex items-center gap-3">
                            <div class="dashboard-header-icon w-10 h-10 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                @if(!$isChildTheme)
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        グループタスク編集
                                    </h1>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">タスク情報の変更</p>
                                @else
                                    <h1 class="dashboard-header-title text-lg font-bold">
                                        へんしゅう
                                    </h1>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-4xl mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    @if($errors->any())
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl shadow-sm">
                            <ul class="list-disc list-inside text-red-800 dark:text-red-200">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('group-tasks.update', $groupTask['group_task_id'] ?? '') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- 基本情報カード --}}
                        <div class="bento-card rounded-2xl shadow-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                {{ !$isChildTheme ? '基本情報' : 'きほんじょうほう' }}
                            </h2>

                            {{-- タイトル --}}
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    タスク名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" id="title" value="{{ old('title', $groupTask['title'] ?? '') }}" required maxlength="255"
                                    placeholder="例：部屋の掃除"
                                    class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm placeholder-gray-400">
                            </div>

                            {{-- 説明 --}}
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    説明
                                </label>
                                <textarea name="description" id="description" rows="3"
                                    placeholder="タスクの詳細な説明を入力してください"
                                    class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm placeholder-gray-400 resize-none custom-scrollbar">{{ old('description', $groupTask['description'] ?? '') }}</textarea>
                            </div>

                            {{-- 期間と期限 --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- 期間選択 --}}
                                <div>
                                    <label for="span" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="pr-8">期間 <span class="text-red-500">*</span></span>
                                    </label>
                                    <select name="span" id="span" required
                                        class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm">
                                        <option value="{{ config('const.task_spans.short') }}" {{ old('span', $groupTask['span'] ?? 2) == config('const.task_spans.short') ? 'selected' : '' }}>
                                            {{ !$isChildTheme ? '短期' : 'すぐにやる' }}
                                        </option>
                                        <option value="{{ config('const.task_spans.mid') }}" {{ old('span', $groupTask['span'] ?? 2) == config('const.task_spans.mid') ? 'selected' : '' }}>
                                            {{ !$isChildTheme ? '中期' : '今年中' }}
                                        </option>
                                        <option value="{{ config('const.task_spans.long') }}" {{ old('span', $groupTask['span'] ?? 2) == config('const.task_spans.long') ? 'selected' : '' }}>
                                            {{ !$isChildTheme ? '長期' : 'いつかやる' }}
                                        </option>
                                    </select>
                                </div>

                                {{-- 期限入力（spanに応じて表示切替） --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="pr-8">{{ !$isChildTheme ? '期限' : 'しめきり' }}</span>
                                    </label>

                                    @php
                                        // 既存データからspanと期限を判定
                                        $currentSpan = old('span', $groupTask['span'] ?? 3);
                                        $dueDate = old('due_date', $groupTask['due_date'] ?? '');
                                        
                                        // 各フィールドの初期値
                                        $dueDateShort = '';
                                        $dueDateMid = date('Y');
                                        $dueDateLong = '';
                                        
                                        // due_dateから適切なフィールドに値を設定
                                        if (!empty($dueDate)) {
                                            try {
                                                $parsedDate = \Carbon\Carbon::parse($dueDate);
                                                if ($currentSpan == config('const.task_spans.short')) {
                                                    $dueDateShort = $parsedDate->format('Y-m-d');
                                                } elseif ($currentSpan == config('const.task_spans.mid')) {
                                                    $dueDateMid = $parsedDate->format('Y');
                                                }
                                            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                                                // 日本語テキスト等の場合は長期フィールドに設定
                                                if ($currentSpan == config('const.task_spans.long')) {
                                                    $dueDateLong = $dueDate;
                                                }
                                            }
                                        }
                                    @endphp

                                    {{-- 短期: 日付選択 --}}
                                    <div id="due-date-short-container" class="due-date-field" style="display: {{ $currentSpan == config('const.task_spans.short') ? 'block' : 'none' }}">
                                        <input type="date" 
                                            id="due_date_short" 
                                            name="due_date"
                                            value="{{ $dueDateShort }}"
                                            {{ $currentSpan != config('const.task_spans.short') ? 'disabled' : '' }}
                                            class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm"
                                            min="{{ date('Y-m-d') }}">
                                    </div>

                                    {{-- 中期: 年選択 --}}
                                    <div id="due-date-mid-container" class="due-date-field" style="display: {{ $currentSpan == config('const.task_spans.mid') ? 'block' : 'none' }}">
                                        <select id="due_date_mid"
                                                name="due_date"
                                                {{ $currentSpan != config('const.task_spans.mid') ? 'disabled' : '' }}
                                                class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm">
                                            @php
                                                $currentYear = date('Y');
                                                $years = range($currentYear, $currentYear + 5);
                                            @endphp
                                            @foreach($years as $year)
                                                <option value="{{ $year }}" {{ $dueDateMid == $year ? 'selected' : '' }}>{{ $year }}年</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 長期: テキスト入力 --}}
                                    <div id="due-date-long-container" class="due-date-field" style="display: {{ $currentSpan == config('const.task_spans.long') ? 'block' : 'none' }}">
                                        <input type="text" 
                                            id="due_date_long"
                                            name="due_date"
                                            value="{{ $dueDateLong }}"
                                            {{ $currentSpan != config('const.task_spans.long') ? 'disabled' : '' }}
                                            placeholder="例：5年後"
                                            class="w-full px-4 py-2.5 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm placeholder-gray-400">
                                    </div>
                                </div>

                                <div>
                                    <label for="reward" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="pr-8">報酬 <span class="text-red-500">*</span></span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="reward" id="reward" min="0" step="1" required
                                            value="{{ old('reward', $groupTask['reward'] ?? 0) }}"
                                            placeholder="0"
                                            class="w-full px-4 py-2.5 pr-12 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">円</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- タグカード --}}
                        <div class="bento-card rounded-2xl shadow-lg p-6">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 inline-block mr-1 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                                {{ !$isChildTheme ? 'タグ' : 'タグ' }}
                            </label>
                            @php
                                // 既存タグの取得
                                $tagsData = $groupTask['tags'] ?? [];
                                
                                if (is_object($tagsData) && method_exists($tagsData, 'pluck')) {
                                    $existingTagNames = $tagsData->pluck('name')->toArray();
                                } elseif (is_array($tagsData)) {
                                    $existingTagNames = array_column($tagsData, 'name');
                                } else {
                                    $existingTagNames = [];
                                }
                                
                                // 利用可能なタグを取得（ユーザーの全タグ）
                                $availableTags = Auth::user()->tags ?? collect();
                            @endphp
                            <div class="flex flex-wrap gap-2">
                                @foreach($availableTags as $tag)
                                    <label class="group-tag-chip inline-flex items-center px-3 py-1.5 rounded-lg cursor-pointer transition">
                                        <input type="checkbox" 
                                            name="tags[]" 
                                            value="{{ $tag->name }}"
                                            {{ in_array($tag->name, $existingTagNames) ? 'checked' : '' }}
                                            class="sr-only">
                                        <span class="text-xs font-medium">{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                                @if($availableTags->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400">タグがありません。タスク作成時にタグを追加してください。</p>
                                @endif
                            </div>
                        </div>

                        {{-- 設定カード --}}
                        <div class="bento-card rounded-2xl shadow-lg p-6 space-y-4">
                            {{-- 画像必須設定 --}}
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-4 rounded-xl border border-purple-200/50 dark:border-purple-700/50">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="hidden" name="requires_image" value="0">
                                    <input type="checkbox" name="requires_image" value="1"
                                        {{ old('requires_image', $groupTask['requires_image'] ?? false) ? 'checked' : '' }}
                                        class="mt-0.5 w-5 h-5 text-purple-600 focus:ring-purple-600 rounded transition">
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                            </svg>
                                            完了時に画像添付を必須にする
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">タスク完了時に証拠画像のアップロードが必要になります</p>
                                    </div>
                                </label>
                            </div>

                            {{-- 承認必須設定 --}}
                            <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 p-4 rounded-xl border border-amber-200/50 dark:border-amber-700/50">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="hidden" name="requires_approval" value="0">
                                    <input type="checkbox" name="requires_approval" value="1"
                                        {{ old('requires_approval', $groupTask['requires_approval'] ?? false) ? 'checked' : '' }}
                                        class="mt-0.5 w-5 h-5 text-amber-600 focus:ring-amber-600 rounded transition">
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            完了時に承認を必須にする（推奨）
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">タスク完了時に親の承認が必要になります。チェックを外すと即座に完了扱いになります。</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- 割当メンバー情報カード --}}
                        <div class="bento-card rounded-2xl shadow-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ !$isChildTheme ? '割当メンバー' : 'わりあてメンバー' }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">このグループタスクは以下のメンバーに割り当てられています：</p>
                            <div class="space-y-2">
                                @if(!empty($groupTask['assignedUsers']))
                                    @foreach($groupTask['assignedUsers'] as $user)
                                        <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ is_object($user) ? $user->name : ($user['name'] ?? '不明') }} ({{ is_object($user) ? $user->username : ($user['username'] ?? '-') }})</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">割り当て情報がありません。</p>
                                @endif
                            </div>
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">※メンバーの変更はできません。変更する場合は削除して再作成してください。</p>
                        </div>

                        {{-- ボタン --}}
                        <div class="flex gap-3">
                            <a href="{{ route('group-tasks.index') }}" 
                               class="flex-1 inline-flex justify-center items-center px-5 py-2.5 border-2 border-purple-300 dark:border-purple-600 text-sm font-semibold rounded-lg text-purple-700 dark:text-purple-300 bg-white dark:bg-gray-800 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition">
                                {{ !$isChildTheme ? 'キャンセル' : 'もどる' }}
                            </a>
                            <button type="submit" 
                                    class="flex-1 inline-flex justify-center items-center px-5 py-2.5 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ !$isChildTheme ? '更新する' : 'こうしんする' }}
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
