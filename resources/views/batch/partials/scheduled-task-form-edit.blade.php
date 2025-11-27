@props (['scheduledTask', 'groupMembers'])

<div data-scheduled-form 
     data-auto-assign="{{ old('auto_assign', $scheduledTask?->auto_assign ?? false) ? 'true' : 'false' }}"
     data-schedules='{{ json_encode(old('schedules', $scheduledTask?->schedules ?? [['type' => 'daily', 'time' => '09:00', 'days' => [], 'dates' => []]])) }}'
     data-tags='{{ json_encode(old('tags', $scheduledTask?->tags ?? [])) }}'>

{{-- 基本情報 --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">基本情報</h3>
    </div>

    {{-- タイトル --}}
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            タイトル <span class="text-red-500">*</span>
        </label>
        <input type="text" 
               id="title" 
               name="title" 
               value="{{ old('title', $scheduledTask?->title ?? '') }}"
               required
               maxlength="255"
               class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
               placeholder="例: 毎週月曜日のゴミ出し">
        @error('title')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 説明 --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            説明
        </label>
        <textarea id="description" 
                  name="description" 
                  rows="4"
                  maxlength="5000"
                  class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition resize-none"
                  placeholder="タスクの詳細説明を入力してください">{{ old('description', $scheduledTask?->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 画像要求・報酬 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" 
                       name="requires_image" 
                       value="1"
                       {{ old('requires_image', $scheduledTask?->requires_image ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">完了時に画像を要求</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">タスク完了時に証拠画像の提出を必須にします</p>
                </div>
            </label>
        </div>

        <div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" 
                       name="requires_approval" 
                       value="1"
                       {{ old('requires_approval', $scheduledTask?->requires_approval ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">完了申請後に承認が必要</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">グループメンバーが完了申請したタスクの承認を必要とするかどうかを設定します。</p>
                </div>
            </label>
        </div>

        <div>
            <label for="reward" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                報酬 <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="reward" 
                       name="reward" 
                       value="{{ old('reward', $scheduledTask?->reward ?? 0) }}"
                       required
                       min="0"
                       step="1"
                       class="w-full px-4 py-2.5 pr-12 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">円</span>
                </div>
            </div>
            @error('reward')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

{{-- 担当者設定 --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-600 to-emerald-600 flex items-center justify-center shadow">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">担当者設定</h3>
    </div>

    <div class="space-y-4">
        {{-- ランダム割り当て --}}
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" 
                   name="auto_assign" 
                   value="1"
                   data-auto-assign
                   {{ old('auto_assign', $scheduledTask?->auto_assign ?? false) ? 'checked' : '' }}
                   class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">グループメンバーにランダム割り当て</span>
                <p class="text-xs text-gray-500 dark:text-gray-400">タスク作成時にランダムで担当者を決定します</p>
            </div>
        </label>

        {{-- 担当者選択 --}}
        <div data-assigned-user-container class="hidden">
            <label for="assigned_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                固定担当者
            </label>
            <select id="assigned_user_id" 
                    name="assigned_user_id"
                    class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                <option value="">未設定（担当者なし）</option>
                @foreach ($groupMembers as $member)
                    <option value="{{ $member->id }}" {{ old('assigned_user_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->username }}
                    </option>
                @endforeach
            </select>
            @error('assigned_user_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

{{-- スケジュール設定 --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center shadow">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">スケジュール設定 <span class="text-red-500">*</span></h3>
    </div>

    <div data-schedules-container class="space-y-4">
        {{-- JSが動的にスケジュールカードを生成 --}}
    </div>

    <button type="button"
            data-add-schedule
            class="add-schedule-btn w-full">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        スケジュールを追加
    </button>
</div>

{{-- 期限設定 --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-600 to-red-600 flex items-center justify-center shadow">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">期限設定</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="due_duration_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                期限（日数）
            </label>
            <div class="relative">
                <input type="number" 
                       id="due_duration_days" 
                       name="due_duration_days" 
                       value="{{ old('due_duration_days', $scheduledTask?->due_duration_days ?? 0) }}"
                       min="0"
                       max="365"
                       placeholder="0"
                       class="w-full px-4 py-2.5 pr-12 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">日</span>
                </div>
            </div>
        </div>

        <div>
            <label for="due_duration_hours" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                期限（時間）
            </label>
            <div class="relative">
                <input type="number" 
                       id="due_duration_hours" 
                       name="due_duration_hours" 
                       value="{{ old('due_duration_hours', $scheduledTask?->due_duration_hours ?? 0) }}"
                       min="0"
                       max="8760"
                       placeholder="0"
                       class="w-full px-4 py-2.5 pr-12 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">時間</span>
                </div>
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        タスク作成から期限までの時間を設定します。未設定の場合は期限なしになります。
    </p>
</div>

{{-- 実行期間 --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-600 to-teal-600 flex items-center justify-center shadow">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">実行期間</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- 開始日 --}}
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                開始日 <span class="text-red-500">*</span>
            </label>
            <input type="date" 
                   name="start_date" 
                   id="start_date"
                   value="{{ old('start_date', $scheduledTask->start_date ? $scheduledTask->start_date->format('Y-m-d') : '') }}"
                   required
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            @error('start_date')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- 終了日 --}}
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                終了日
                <span class="text-xs text-gray-500 ml-2">(未設定の場合は無期限)</span>
            </label>
            <input type="date" 
                   name="end_date" 
                   id="end_date"
                   value="{{ old('end_date', $scheduledTask->end_date ? $scheduledTask->end_date->format('Y-m-d') : '') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            @error('end_date')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

{{-- その他の設定 --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-600 to-gray-800 flex items-center justify-center shadow">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">その他の設定</h3>
    </div>

    <div class="space-y-4">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" 
                   name="skip_holidays" 
                   value="1"
                   {{ old('skip_holidays', $scheduledTask?->skip_holidays ?? false) ? 'checked' : '' }}
                   class="mt-1 w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">祝日をスキップ</span>
                <p class="text-xs text-gray-500 dark:text-gray-400">実行日が祝日の場合、タスクを作成しません</p>
            </div>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" 
                   name="move_to_next_business_day" 
                   value="1"
                   {{ old('move_to_next_business_day', $scheduledTask?->move_to_next_business_day ?? false) ? 'checked' : '' }}
                   class="mt-1 w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">祝日の場合は翌営業日に移動</span>
                <p class="text-xs text-gray-500 dark:text-gray-400">実行日が祝日の場合、次の平日に実行します</p>
            </div>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" 
                   name="delete_incomplete_previous" 
                   value="1"
                   {{ old('delete_incomplete_previous', $scheduledTask?->delete_incomplete_previous ?? false) ? 'checked' : '' }}
                   class="mt-1 w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">未完了の前回タスクを削除</span>
                <p class="text-xs text-gray-500 dark:text-gray-400">新しいタスクを作成する際、前回作成した未完了タスクを削除します</p>
            </div>
        </label>
    </div>
</div>

{{-- タグ --}}
<div class="bento-card rounded-xl p-6 space-y-6">
    <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-600 to-rose-600 flex items-center justify-center shadow">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">タグ</h3>
    </div>

    <div>
        <label for="tag-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            タグを追加（Enter で追加）
        </label>
        <div class="tag-input-wrapper">
            <div data-tags-container class="flex flex-wrap gap-2">
                {{-- JSが動的にタグチップを生成 --}}
            </div>
            <input type="text" 
                   id="tag-input"
                   data-tag-input
                   placeholder="タグを入力..."
                   maxlength="50"
                   class="flex-1 min-w-[120px] px-2 py-1 bg-transparent border-none focus:ring-0 text-sm">
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">タスクの分類に使用できます（最大50文字）</p>
    </div>
</div>
</div>