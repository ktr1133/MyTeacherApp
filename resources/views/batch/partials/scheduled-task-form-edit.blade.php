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
                   x-model="autoAssign"
                   {{ old('auto_assign', $scheduledTask?->auto_assign ?? false) ? 'checked' : '' }}
                   class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">グループメンバーにランダム割り当て</span>
                <p class="text-xs text-gray-500 dark:text-gray-400">タスク作成時にランダムで担当者を決定します</p>
            </div>
        </label>

        {{-- 担当者選択 --}}
        <div x-show="!autoAssign" x-transition>
            <label for="assigned_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                固定担当者
            </label>
            <select id="assigned_user_id" 
                    name="assigned_user_id"
                    class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                <option value="">未設定（担当者なし）</option>
                @foreach ($groupMembers as $member)
                    <option value="{{ $member->id }}" {{ old('assigned_user_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->name }}
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

    <div id="schedules-container" class="space-y-4">
        <template x-for="(schedule, index) in schedules" :key="index">
            <div class="schedule-card border-2 border-gray-200 dark:border-gray-700 p-4 rounded-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        スケジュール <span x-text="index + 1"></span>
                    </h4>
                    <button type="button"
                            @click="removeSchedule(index)"
                            x-show="schedules.length > 1"
                            class="p-1.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- スケジュールタイプ --}}
                <div class="space-y-3">
                    <label class="schedule-type-label">
                        <input type="radio" 
                               class="schedule-type-radio"
                               :name="'schedules[' + index + '][type]'" 
                               value="daily"
                               x-model="schedule.type"
                               required>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">毎日</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">毎日同じ時刻に実行</div>
                            </div>
                        </div>
                    </label>

                    <label class="schedule-type-label">
                        <input type="radio" 
                               class="schedule-type-radio"
                               :name="'schedules[' + index + '][type]'" 
                               value="weekly"
                               x-model="schedule.type"
                               required>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">毎週</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">曜日を指定して実行</div>
                            </div>
                        </div>
                    </label>

                    <label class="schedule-type-label">
                        <input type="radio" 
                               class="schedule-type-radio"
                               :name="'schedules[' + index + '][type]'" 
                               value="monthly"
                               x-model="schedule.type"
                               required>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">毎月</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">日付を指定して実行</div>
                            </div>
                        </div>
                    </label>
                </div>

                {{-- 曜日選択（毎週の場合） --}}
                <div x-show="schedule.type === 'weekly'" x-transition class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        曜日選択 <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(day, dayIndex) in weekdays" :key="dayIndex">
                            <label>
                                <input type="checkbox" 
                                       class="weekday-checkbox"
                                       :name="'schedules[' + index + '][days][]'" 
                                       :value="dayIndex"
                                       x-model="schedule.days">
                                <div class="weekday-label" x-text="day"></div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 日付選択（毎月の場合） --}}
                <div x-show="schedule.type === 'monthly'" x-transition class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        日付選択 <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-7 gap-2">
                        <template x-for="date in 31" :key="date">
                            <label>
                                <input type="checkbox" 
                                       class="date-checkbox"
                                       :name="'schedules[' + index + '][dates][]'" 
                                       :value="date"
                                       x-model="schedule.dates">
                                <div class="date-label" x-text="date"></div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 実行時刻 --}}
                <div>
                    <label :for="'time_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        実行時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           :id="'time_' + index"
                           :name="'schedules[' + index + '][time]'" 
                           x-model="schedule.time"
                           required
                           class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                </div>
            </div>
        </template>
    </div>

    <button type="button"
            @click="addSchedule"
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
            <template x-for="(tag, index) in tags" :key="index">
                <div class="tag-chip">
                    <input type="hidden" :name="'tags[]'" :value="tag">
                    <span x-text="'#' + tag"></span>
                    <button type="button" 
                            @click="removeTag(index)"
                            class="tag-chip-remove">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
            <input type="text" 
                   id="tag-input"
                   x-model="tagInput"
                   @keydown.enter.prevent="addTag"
                   placeholder="タグを入力..."
                   maxlength="50"
                   class="flex-1 min-w-[120px] px-2 py-1 bg-transparent border-none focus:ring-0 text-sm">
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">タスクの分類に使用できます（最大50文字）</p>
    </div>
</div>