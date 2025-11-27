/**
 * スケジュールタスクフォームコントローラー
 * Alpine.jsから移行: Vanilla JavaScript実装
 */
class ScheduledTaskFormController {
    /**
     * コンストラクタ
     * スケジュールタスク作成フォームを初期化（自動割当、スケジュール動的管理、タグ管理）
     */
    constructor() {
        this.autoAssign = false;
        this.schedules = [];
        this.weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        this.tags = [];
        this.tagInput = '';
        
        this.init();
    }
    
    /**
     * 初期化処理
     * 既存データ（old値）を読み込み、イベントリスナーを登録、初期UI更新を実行
     */
    init() {
        console.log('[Scheduled Task Form] Initialized');
        
        // データ属性から初期値を取得
        const formElement = document.querySelector('[data-scheduled-form]');
        if (!formElement) return;
        
        // Old値の読み込み
        this.loadOldValues(formElement);
        
        // 最低1つのスケジュールを保証
        if (this.schedules.length === 0) {
            this.addSchedule();
        }
        
        this.setupEventListeners();
        this.updateUI();
    }
    
    /**
     * Laravel old値の読み込み
     * フォームエラー時の値復元用にdata属性から初期値を取得
     * @param {HTMLElement} formElement - フォーム要素
     */
    loadOldValues(formElement) {
        // 自動割当
        const autoAssignData = formElement.dataset.autoAssign;
        if (autoAssignData !== undefined) {
            this.autoAssign = autoAssignData === 'true' || autoAssignData === '1';
        }
        
        // スケジュール
        const schedulesData = formElement.dataset.schedules;
        if (schedulesData) {
            try {
                this.schedules = JSON.parse(schedulesData);
            } catch (e) {
                console.error('Failed to parse schedules:', e);
                this.schedules = [];
            }
        }
        
        // タグ
        const tagsData = formElement.dataset.tags;
        if (tagsData) {
            try {
                this.tags = JSON.parse(tagsData);
            } catch (e) {
                console.error('Failed to parse tags:', e);
                this.tags = [];
            }
        }
    }
    
    /**
     * イベントリスナーの登録
     * 自動割当チェックボックス、スケジュール追加/削除ボタン、タグ入力/削除の各イベントを設定
     */
    setupEventListeners() {
        // 自動割当チェックボックス
        const autoAssignCheckbox = document.querySelector('[data-auto-assign]');
        if (autoAssignCheckbox) {
            autoAssignCheckbox.addEventListener('change', () => {
                this.autoAssign = autoAssignCheckbox.checked;
                this.updateUI();
            });
        }
        
        // スケジュール追加ボタン
        const addScheduleBtn = document.querySelector('[data-add-schedule]');
        if (addScheduleBtn) {
            addScheduleBtn.addEventListener('click', () => this.addSchedule());
        }
        
        // タグ入力
        const tagInput = document.querySelector('[data-tag-input]');
        if (tagInput) {
            tagInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.tagInput = tagInput.value;
                    this.addTag();
                    tagInput.value = '';
                }
            });
        }
    }
    
    /**
     * UI全体の更新
     * 自動割当に応じた担当者選択表示、全スケジュールカードのレンダリング、タグチップ表示を更新
     */
    updateUI() {
        // 自動割当の表示/非表示
        this.updateAutoAssignUI();
        
        // スケジュールカードを再描画
        this.renderSchedules();
        
        // タグチップを再描画
        this.renderTags();
    }
    
    /**
     * 自動割当UI更新
     * チェックボックスの状態に応じて担当者選択フィールドの表示/非表示を制御
     */
    updateAutoAssignUI() {
        const assignedUserContainer = document.querySelector('[data-assigned-user-container]');
        if (!assignedUserContainer) return;
        
        if (this.autoAssign) {
            assignedUserContainer.classList.add('hidden');
        } else {
            assignedUserContainer.classList.remove('hidden');
        }
    }
    
    /**
     * スケジュールカードのレンダリング
     * 全スケジュールをDOMに描画し、各カードのタイプ変更、削除イベントを設定
     */
    renderSchedules() {
        const container = document.querySelector('[data-schedules-container]');
        if (!container) return;
        
        container.innerHTML = '';
        
        this.schedules.forEach((schedule, index) => {
            const card = this.createScheduleCard(schedule, index);
            container.appendChild(card);
        });
    }
    
    /**
     * スケジュールカードの作成
     * 単一スケジュールのHTML要素を生成し、タイプ別（daily/weekly/monthly）の入力欄を含む
     * @param {Object} schedule - スケジュールデータ
     * @param {number} index - スケジュールのインデックス
     * @returns {HTMLElement} スケジュールカードのDOM要素
     */
    createScheduleCard(schedule, index) {
        const card = document.createElement('div');
        card.className = 'schedule-card border-2 border-gray-200 dark:border-gray-700 p-4 rounded-xl space-y-4';
        card.dataset.scheduleCard = index;
        
        card.innerHTML = `
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    スケジュール ${index + 1}
                </h4>
                <button type="button"
                        data-remove-schedule="${index}"
                        class="p-1.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors ${this.schedules.length <= 1 ? 'hidden' : ''}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            ${this.renderScheduleTypeOptions(schedule, index)}
            ${this.renderWeeklyOptions(schedule, index)}
            ${this.renderMonthlyOptions(schedule, index)}
            ${this.renderTimeInput(schedule, index)}
        `;
        
        // イベントリスナー登録
        this.attachScheduleCardEvents(card, index);
        
        return card;
    }
    
    /**
     * スケジュールタイプ選択オプションのHTML生成
     * daily/weekly/monthlyの3つのラジオボタンを生成
     * @param {Object} schedule - スケジュールデータ
     * @param {number} index - インデックス
     * @returns {string} HTML文字列
     */
    renderScheduleTypeOptions(schedule, index) {
        return `
            <div class="space-y-3">
                <label class="schedule-type-label">
                    <input type="radio" 
                           class="schedule-type-radio"
                           name="schedules[${index}][type]" 
                           value="daily"
                           ${schedule.type === 'daily' ? 'checked' : ''}
                           data-schedule-type="${index}"
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
                           name="schedules[${index}][type]" 
                           value="weekly"
                           ${schedule.type === 'weekly' ? 'checked' : ''}
                           data-schedule-type="${index}"
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
                           name="schedules[${index}][type]" 
                           value="monthly"
                           ${schedule.type === 'monthly' ? 'checked' : ''}
                           data-schedule-type="${index}"
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
        `;
    }
    
    /**
     * 週次スケジュールオプションのHTML生成
     * 曜日チェックボックス（日〜土）を生成、weekly選択時のみ表示
     * @param {Object} schedule - スケジュールデータ
     * @param {number} index - インデックス
     * @returns {string} HTML文字列
     */
    renderWeeklyOptions(schedule, index) {
        const isVisible = schedule.type === 'weekly';
        const days = schedule.days || [];
        
        const checkboxes = this.weekdays.map((day, dayIndex) => {
            const isChecked = days.includes(dayIndex);
            return `
                <label>
                    <input type="checkbox" 
                           class="weekday-checkbox"
                           name="schedules[${index}][days][]" 
                           value="${dayIndex}"
                           ${isChecked ? 'checked' : ''}>
                    <div class="weekday-label">${day}</div>
                </label>
            `;
        }).join('');
        
        return `
            <div data-weekly-container="${index}" class="${isVisible ? '' : 'hidden'} space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    曜日選択 <span class="text-red-500">*</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    ${checkboxes}
                </div>
            </div>
        `;
    }
    
    /**
     * 月次スケジュールオプションのHTML生成
     * 1〜31日のチェックボックスを生成、monthly選択時のみ表示
     * @param {Object} schedule - スケジュールデータ
     * @param {number} index - インデックス
     * @returns {string} HTML文字列
     */
    renderMonthlyOptions(schedule, index) {
        const isVisible = schedule.type === 'monthly';
        const dates = schedule.dates || [];
        
        const checkboxes = Array.from({length: 31}, (_, i) => i + 1).map(date => {
            const isChecked = dates.includes(date);
            return `
                <label>
                    <input type="checkbox" 
                           class="date-checkbox"
                           name="schedules[${index}][dates][]" 
                           value="${date}"
                           ${isChecked ? 'checked' : ''}>
                    <div class="date-label">${date}</div>
                </label>
            `;
        }).join('');
        
        return `
            <div data-monthly-container="${index}" class="${isVisible ? '' : 'hidden'} space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    日付選択 <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-7 gap-2">
                    ${checkboxes}
                </div>
            </div>
        `;
    }
    
    /**
     * 実行時刻入力のHTML生成
     * time型input要素を生成
     * @param {Object} schedule - スケジュールデータ
     * @param {number} index - インデックス
     * @returns {string} HTML文字列
     */
    renderTimeInput(schedule, index) {
        return `
            <div>
                <label for="time_${index}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    実行時刻 <span class="text-red-500">*</span>
                </label>
                <input type="time" 
                       id="time_${index}"
                       name="schedules[${index}][time]" 
                       value="${schedule.time || '09:00'}"
                       required
                       class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
            </div>
        `;
    }
    
    /**
     * スケジュールカードのイベントリスナー登録
     * タイプ変更ラジオボタンと削除ボタンにイベントを設定
     * @param {HTMLElement} card - スケジュールカード要素
     * @param {number} index - インデックス
     */
    attachScheduleCardEvents(card, index) {
        // タイプ変更
        const typeRadios = card.querySelectorAll(`[data-schedule-type="${index}"]`);
        typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.schedules[index].type = radio.value;
                this.updateScheduleTypeVisibility(card, index);
            });
        });
        
        // 削除ボタン
        const removeBtn = card.querySelector(`[data-remove-schedule="${index}"]`);
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeSchedule(index));
        }
    }
    
    /**
     * スケジュールタイプ別の表示/非表示更新
     * 選択されたタイプ（daily/weekly/monthly）に応じて週次/月次オプションの表示を切り替え
     * @param {HTMLElement} card - スケジュールカード要素
     * @param {number} index - インデックス
     */
    updateScheduleTypeVisibility(card, index) {
        const schedule = this.schedules[index];
        
        const weeklyContainer = card.querySelector(`[data-weekly-container="${index}"]`);
        const monthlyContainer = card.querySelector(`[data-monthly-container="${index}"]`);
        
        if (weeklyContainer) {
            if (schedule.type === 'weekly') {
                weeklyContainer.classList.remove('hidden');
            } else {
                weeklyContainer.classList.add('hidden');
            }
        }
        
        if (monthlyContainer) {
            if (schedule.type === 'monthly') {
                monthlyContainer.classList.remove('hidden');
            } else {
                monthlyContainer.classList.add('hidden');
            }
        }
    }
    
    /**
     * スケジュールを追加
     * デフォルト値（daily, 09:00）の新規スケジュールを追加し、UIを更新
     */
    addSchedule() {
        this.schedules.push({
            type: 'daily',
            time: '09:00',
            days: [],
            dates: []
        });
        this.updateUI();
    }
    
    /**
     * スケジュールを削除
     * 指定インデックスのスケジュールを削除（最低1つは保持）
     * @param {number} index - 削除するスケジュールのインデックス
     */
    removeSchedule(index) {
        if (this.schedules.length > 1) {
            this.schedules.splice(index, 1);
            this.updateUI();
        }
    }
    
    /**
     * タグチップのレンダリング
     * 全タグをDOMに描画し、各タグの削除ボタンにイベントを設定
     */
    renderTags() {
        const container = document.querySelector('[data-tags-container]');
        if (!container) return;
        
        container.innerHTML = '';
        
        this.tags.forEach((tag, index) => {
            const chip = this.createTagChip(tag, index);
            container.appendChild(chip);
        });
    }
    
    /**
     * タグチップの作成
     * 単一タグのHTML要素（hidden input + 表示テキスト + 削除ボタン）を生成
     * @param {string} tag - タグ名
     * @param {number} index - インデックス
     * @returns {HTMLElement} タグチップのDOM要素
     */
    createTagChip(tag, index) {
        const chip = document.createElement('div');
        chip.className = 'tag-chip';
        chip.innerHTML = `
            <input type="hidden" name="tags[]" value="${tag}">
            <span>#${tag}</span>
            <button type="button" 
                    data-remove-tag="${index}"
                    class="tag-chip-remove">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        
        const removeBtn = chip.querySelector(`[data-remove-tag="${index}"]`);
        removeBtn.addEventListener('click', () => this.removeTag(index));
        
        return chip;
    }
    
    /**
     * タグを追加
     * 入力されたタグ名をバリデーション後（重複・長さチェック）、リストに追加してUIを更新
     */
    addTag() {
        const tag = this.tagInput.trim();
        if (tag && !this.tags.includes(tag) && tag.length <= 50) {
            this.tags.push(tag);
            this.tagInput = '';
            this.updateUI();
        }
    }
    
    /**
     * タグを削除
     * 指定インデックスのタグを削除してUIを更新
     * @param {number} index - 削除するタグのインデックス
     */
    removeTag(index) {
        this.tags.splice(index, 1);
        this.updateUI();
    }
}

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', () => {
    // フォームが存在する場合のみ初期化
    if (document.querySelector('[data-scheduled-form]')) {
        new ScheduledTaskFormController();
    }
});
