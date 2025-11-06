/**
 * APIクライアントの分離
 * タスク関連のAPIリクエストを管理するクラス。
 */
class TaskAPI {
    /**
     * APIのベースURLを取得する。
     * @returns {string} APIのベースURL。
     */
    static apiBase() {
        const meta = document.querySelector('meta[name="api-base-url"]')?.content?.trim();
        // 相対指定なら location.origin を基準に解決
        const base = meta && !meta.startsWith('http') ? `${location.origin}${meta}` : (meta || '/api');
        // 末尾スラッシュ除去
        return base.replace(/\/+$/, '');
    }

    /**
     * APIのオリジンを取得する。
     * @returns {string} APIのオリジンURL。
     */
    static apiOrigin() {
        try {
            return new URL(this.apiBase(), location.origin).origin;
        } catch {
            return location.origin;
        }
    }

    /**
     * CSRFクッキーを取得する。
     * 異なるオリジンの場合に必要。
     */
    static async ensureCsrfCookie() {
        // 異なるオリジンの場合は Sanctum の CSRF クッキーを取得
        const apiOrigin = this.apiOrigin();
        if (apiOrigin !== location.origin) {
            await fetch(`${apiOrigin}/sanctum/csrf-cookie`, {
                method: 'GET',
                credentials: 'include',
            });
        }
    }

    /**
     * タスクを提案するAPIリクエストを送信する。
     * @param {string} title - タスクのタイトル。
     * @param {string} span - タスクの期間。
     * @param {string} context - タスクの文脈。
     * @param {boolean} isRefinement - 再提案かどうか。
     * @returns {Promise<Object>} 提案されたタスクのレスポンス。
     */
    static async propose(title, span, context, isRefinement) {
        // エンドポイントは /api を含めない（apiBase に委譲）
        return this._post('tasks/propose', {
            title,
            span,
            context,
            is_refinement: isRefinement,
        });
    }

    /**
     * 提案を採用するAPIリクエストを送信する。
     * @param {string} proposalId - 提案のID。
     * @param {Array} tasks - 採用するタスクの配列。
     * @returns {Promise<Object>} 採用結果のレスポンス。
     */
    static async adopt(proposalId, tasks) {
        return this._post('tasks/adopt', {
            proposal_id: proposalId,
            tasks,
        });
    }

    /**
     * POSTリクエストを送信する。
     * @param {string} endpoint - APIエンドポイント。
     * @param {Object} data - 送信するデータ。
     * @returns {Promise<Object>} レスポンスデータ。
     * @throws {Error} リクエストが失敗した場合。
     */
    static async _post(endpoint, data) {
        await this.ensureCsrfCookie();

        const base = this.apiBase();
        const path = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
        const url = `${base}${path}`;

        const headers = {
            'Content-Type': 'application/json',
        };
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;

        const response = await fetch(url, {
            method: 'POST',
            headers,
            credentials: 'include',
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const text = await response.text().catch(() => '');
            throw new Error(`API request to ${url} failed: ${response.status} ${text}`);
        }
        return response.json();
    }
}

/**
 * モーダル制御クラス
 * DOM操作によるモーダルの開閉を管理
 */
class ModalController {
    constructor() {
        this.wrapper = document.getElementById('task-modal-wrapper');
        this.content = document.getElementById('task-modal-content');
        this.loadingOverlay = document.getElementById('modal-loading-overlay');
        this.views = {
            input: document.getElementById('modal-state-1'),
            decomposition: document.getElementById('modal-state-2'),
            refine: document.getElementById('modal-state-3')
        };
    }

    /**
     * モーダルを開く
     */
    open() {
        if (!this.wrapper) return;
        
        // Alpine.js Store の入力値をリセット
        const store = Alpine.store?.('dashboard');
        if (store) {
            store.taskTitle = '';
            store.taskSpan = 2; // デフォルトは中期
            store.due_date = new Date().getFullYear().toString(); // 現在年
            store.refinementPoints = '';
            store.selectedTags = [];
            store.proposedTasks = [];
            store.selectedTaskSpans = {};
            store.selectedTaskDueDates = [];
            store.generatedTag = '';
        }
        
        // 初期状態にリセット
        this.switchView('input');
        this.hideLoading();
        
        // モーダルを表示
        this.wrapper.classList.remove('hidden');
        this.wrapper.classList.add('flex');
        
        // トランジション用の遅延
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.wrapper.classList.remove('opacity-0');
                this.wrapper.classList.add('opacity-100');
                
                this.content.classList.remove('translate-y-4', 'scale-95');
                this.content.classList.add('translate-y-0', 'scale-100');
            });
        });
        
        this.wrapper.setAttribute('data-modal-state', 'open');
        
        // タイトル入力欄にフォーカス
        setTimeout(() => {
            const titleInput = document.getElementById('taskTitle');
            if (titleInput) {
                titleInput.focus();
            }
        }, 350); // トランジション完了後
    }

    /**
     * モーダルを閉じる
     */
    close() {
        if (!this.wrapper) return;
        
        // トランジション開始
        this.wrapper.classList.remove('opacity-100');
        this.wrapper.classList.add('opacity-0');
        
        this.content.classList.remove('translate-y-0', 'scale-100');
        this.content.classList.add('translate-y-4', 'scale-95');
        
        // トランジション終了後に非表示
        setTimeout(() => {
            this.wrapper.classList.remove('flex');
            this.wrapper.classList.add('hidden');
            this.wrapper.setAttribute('data-modal-state', 'closed');
            
            // Store もリセット
            const store = Alpine.store?.('dashboard');
            if (store) {
                store.taskTitle = '';
                store.taskSpan = 2;
                store.due_date = new Date().getFullYear().toString();
                store.refinementPoints = '';
                store.selectedTags = [];
                store.proposedTasks = [];
                store.selectedTaskSpans = {};
                store.selectedTaskDueDates = [];
                store.generatedTag = '';
            }
        }, 300); // transition duration と一致
    }

    /**
     * ビューを切り替える
     * @param {'input'|'decomposition'|'refine'} viewName
     */
    switchView(viewName) {
        Object.entries(this.views).forEach(([name, element]) => {
            if (element) {
                if (name === viewName) {
                    element.style.display = 'block';
                    
                    if (window.Alpine && name === 'decomposition') {
                        // Alpine.js の Store から proposedTasks を取得
                        Alpine.nextTick(() => {
                            const store = Alpine.store('dashboard');
                            
                            // DOM に直接タスクリストをレンダリング
                            this.renderProposedTasks(store?.proposedTasks || [], store?.generatedTag || '');
                        });
                    }
                } else {
                    element.style.display = 'none';
                }
            }
        });
    }

    /**
     * 提案されたタスクを DOM に直接レンダリング
     * @param {Array} tasks - 提案されたタスクの配列
     * @param {string} generatedTag - 生成されたタグ
     */
    renderProposedTasks(tasks, generatedTag) {
        // タグ表示を更新
        const tagDisplay = document.getElementById('generated-tag-display');
        if (tagDisplay) {
            tagDisplay.textContent = generatedTag || 'タグなし';
        }
        
        // 提案タスク数を更新
        const taskCountDisplay = document.getElementById('proposed-task-count');
        const adoptTaskCountDisplay = document.getElementById('adopt-task-count');
        if (taskCountDisplay) {
            taskCountDisplay.textContent = tasks.length;
        }
        if (adoptTaskCountDisplay) {
            adoptTaskCountDisplay.textContent = tasks.length;
        }
        
        // 「この提案を受け入れる」ボタンの有効/無効を切り替え
        const adoptBtn = document.getElementById('adopt-proposal-btn');
        if (adoptBtn) {
            adoptBtn.disabled = tasks.length === 0;
        }
        
        // タスクリストコンテナとメッセージを取得
        const tasksListContainer = document.getElementById('tasks-list');
        const noTasksMessage = document.getElementById('no-tasks-message');
        
        if (!tasksListContainer || !noTasksMessage) return;
        
        // タスクがない場合
        if (!tasks || tasks.length === 0) {
            noTasksMessage.style.display = 'block';
            tasksListContainer.innerHTML = '';
            return;
        }
        
        // タスクがある場合
        noTasksMessage.style.display = 'none';
        
        // タスクリストを生成
        const store = Alpine.store('dashboard');
        const tasksHTML = tasks.map((task, index) => {
            const selectedSpan = store?.selectedTaskSpans?.[index] || 2;
            const selectedDueDate = store?.selectedTaskDueDates?.[index] || '';
            
            return `
                <div class="task-list flex items-start gap-3 p-3 border-2 border-gray-200 rounded-lg hover:bg-gray-50 transition bg-white">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[#59B9C6] text-white text-xs font-bold">${index + 1}</span>
                            <p class="font-medium break-words">${this.escapeHtml(task.title || '無題のタスク')}</p>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <label class="text-sm text-gray-600 whitespace-nowrap">期間:</label>
                            <select
                                class="task-span-select text-sm border rounded px-2 py-1 pr-8 bg-white"
                                data-task-index="${index}">
                                <option value="1" ${selectedSpan == 1 ? 'selected' : ''}>短期</option>
                                <option value="2" ${selectedSpan == 2 ? 'selected' : ''}>中期</option>
                                <option value="3" ${selectedSpan == 3 ? 'selected' : ''}>長期</option>
                            </select>
                        </div>

                        <div class="mt-2 due-date-container" data-task-index="${index}">
                            ${this.renderDueDateField(index, selectedSpan, selectedDueDate)}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="task-remove-btn text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded flex-shrink-0 transition"
                        data-remove-index="${index}"
                        title="このタスクを削除">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
        }).join('');
        
        tasksListContainer.innerHTML = tasksHTML;
        
        // スパン変更・期限変更・削除イベントをバインド
        this.bindTaskSpanChangeEvents();
        this.bindTaskRemoveEvents();
    }

    /**
     * 期限入力フィールドをレンダリング
     * @param {number} index - タスクのインデックス
     * @param {number} span - スパン値
     * @param {string} dueDate - 期限値
     * @returns {string} HTML文字列
     */
    renderDueDateField(index, span, dueDate) {
        const shortSpan = 1;
        const midSpan = 2;
        const longSpan = 3;

        const today = new Date().toISOString().split('T')[0];
        const currentYear = new Date().getFullYear();
        const years = Array.from({ length: 6 }, (_, i) => currentYear + i);
        
        if (span == shortSpan) {
            // 短期：日付
            return `
                <div>
                    <label class="block text-xs text-gray-600 mb-1">期限（日付）</label>
                    <input type="date"
                        class="task-due-date w-full border rounded px-2 py-1 text-sm"
                        data-task-index="${index}"
                        min="${today}"
                        value="${dueDate}">
                </div>
            `;
        } else if (span == midSpan) {
            // 中期：年
            return `
                <div>
                    <label class="block text-xs text-gray-600 mb-1">期限（年）</label>
                    <select
                        class="task-due-date w-full border rounded px-2 py-1 text-sm pr-8"
                        data-task-index="${index}">
                        <option value="">選択してください</option>
                        ${years.map(year => `<option value="${year}" ${dueDate == year ? 'selected' : ''}>${year}年</option>`).join('')}
                    </select>
                </div>
            `;
        } else {
            // 長期：自由入力
            return `
                <div>
                    <label class="block text-xs text-gray-600 mb-1">期限（任意）</label>
                    <input type="text"
                        class="task-due-date w-full border rounded px-2 py-1 text-sm"
                        data-task-index="${index}"
                        placeholder="例：5年後"
                        value="${dueDate}">
                </div>
            `;
        }
    }

    /**
     * タスクのスパン変更イベントをバインド
     */
    bindTaskSpanChangeEvents() {
        const spanSelects = document.querySelectorAll('.task-span-select');
        spanSelects.forEach(select => {
            select.addEventListener('change', (e) => {
                const index = parseInt(e.target.getAttribute('data-task-index'));
                const newSpan = parseInt(e.target.value);
                
                // Store を更新
                const store = Alpine.store('dashboard');
                if (store) {
                    store.setTaskSpan(index, newSpan);
                    
                    // 期限フィールドを再レンダリング
                    const dueDateContainer = document.querySelector(`.due-date-container[data-task-index="${index}"]`);
                    if (dueDateContainer) {
                        dueDateContainer.innerHTML = this.renderDueDateField(index, newSpan, '');
                        this.bindDueDateChangeEvents();
                    }
                }
            });
        });
        
        this.bindDueDateChangeEvents();
    }

    /**
     * 期限変更イベントをバインド
     */
    bindDueDateChangeEvents() {
        const dueDateInputs = document.querySelectorAll('.task-due-date');
        dueDateInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.getAttribute('data-task-index'));
                const newDueDate = e.target.value;
                
                // Store を更新
                const store = Alpine.store('dashboard');
                if (store && store.selectedTaskDueDates) {
                    store.selectedTaskDueDates[index] = newDueDate;
                }
            });
        });
    }

    /**
     * タスク削除ボタンのイベントをバインド
     */
    bindTaskRemoveEvents() {
        const removeButtons = document.querySelectorAll('.task-remove-btn');
        removeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const index = parseInt(btn.getAttribute('data-remove-index'));
                const store = Alpine.store('dashboard');
                
                if (store && !isNaN(index)) {
                    store.removeProposedTask(index);
                    
                    // DOM を再レンダリング
                    this.renderProposedTasks(store.proposedTasks, store.generatedTag);
                }
            });
        });
    }

    /**
     * HTML エスケープ
     * @param {string} text - エスケープする文字列
     * @returns {string} エスケープされた文字列
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * ローディング表示
     */
    showLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.classList.remove('hidden');
            this.loadingOverlay.classList.add('flex');
        }
    }

    /**
     * ローディング非表示
     */
    hideLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.classList.remove('flex');
            this.loadingOverlay.classList.add('hidden');
        }
    }

    /**
     * モーダルが開いているか確認
     */
    isOpen() {
        return this.wrapper?.getAttribute('data-modal-state') === 'open';
    }
}

/**
 * 状態管理クラス
 * ダッシュボードの状態を管理するクラス。
 */
class DashboardState {
    constructor() {
        this.showTaskModal = false;
        this.showDecompositionModal = false;
        this.showRefineModal = false;
        this.isProposing = false;
        this.taskTitle = '';
        this.taskSpan = '';
        this.refinementPoints = '';
        this.decompositionProposal = null;
        this.proposedTasks = []; // 提案されたタスクのリスト
        this.selectedTaskSpans = {}; // 各タスクのスパンを保持 {index: 'short'|'mid'|'long'}
        this.selectedTaskDueDates = []; // 各タスクの期限を保持
        this.generatedTag = ''; // 生成されたタグ
    }

    /**
     * 状態をリセットする。
     */
    reset() {
        this.showTaskModal = false;
        this.showDecompositionModal = false;
        this.showRefineModal = false;
        this.isProposing = false;
        this.taskTitle = '';
        this.refinementPoints = '';
        this.proposedTasks = [];
        this.selectedTaskSpans = {};
        this.selectedTaskDueDates = [];
        this.generatedTag = '';
    }
}

/**
 * Alpine.jsのデータストアを初期化する。
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('dashboard', {
        // 初期状態
        showTaskModal: false,
        showDecompositionModal: false,
        showRefineModal: false,
        isProposing: false,
        taskTitle: '',
        taskSpan: 2,
        due_date: '',
        refinementPoints: '',
        decompositionProposal: null,
        selectedTags: [],
        proposedTasks: [],
        selectedTaskSpans: {},
        selectedTaskDueDates: [],
        generatedTag: '',

        /**
         * 提案されたタスクを削除する
         * @param {number} index - 削除するタスクのインデックス
         */
        removeProposedTask(index) {
            this.proposedTasks = this.proposedTasks.filter((_, i) => i !== index);
            // スパン選択も削除
            const newSpans = {};
            Object.keys(this.selectedTaskSpans).forEach(key => {
                const idx = parseInt(key);
                if (idx < index) {
                    newSpans[idx] = this.selectedTaskSpans[key];
                } else if (idx > index) {
                    newSpans[idx - 1] = this.selectedTaskSpans[key];
                }
            });
            this.selectedTaskSpans = newSpans;
            // dueDates（配列の詰め直し）
            this.selectedTaskDueDates = this.selectedTaskDueDates.filter((_, i) => i !== index);
        },

        /**
         * タスクのスパンを設定する
         * @param {number} index - タスクのインデックス
         * @param {string} span - 'short'|'mid'|'long'
         */
        setTaskSpan(index, span) {
            this.selectedTaskSpans[index] = span;
            // スパン変更時は期限をクリア
            this.selectedTaskDueDates[index] = '';
        },

        /**
         * span 変更時の処理
         * - 1: date input 用に今日をデフォルト
         * - 2: 年選択用に現在年をセット
         * - 3: フリーテキスト（空にする）
         */
        handleSpanChange(spanValue) {
            // 文字列で渡される可能性があるため parseInt で数値化
            const span = parseInt(spanValue, 10);
            this.taskSpan = span;
            
            if (span === 1) {
                // 短期: 今日の日付をセット
                const today = new Date().toISOString().split('T')[0];
                this.due_date = today;
            } else if (span === 2) {
                // 中期: 現在年をセット
                this.due_date = new Date().getFullYear().toString();
            } else if (span === 3) {
                // 長期: 空にする
                this.due_date = '';
            }
        },

        /**
         * Controller からの状態更新を受け取るヘルパー
         */
        updateFromController(state) {
            this.showTaskModal = !!state.showTaskModal;
            this.showDecompositionModal = !!state.showDecompositionModal;
            this.showRefineModal = !!state.showRefineModal;
            this.isProposing = !!state.isProposing;
            this.taskTitle = state.taskTitle || this.taskTitle;
            this.taskSpan = state.taskSpan || this.taskSpan;
            this.due_date = state.due_date ?? this.due_date;
            this.refinementPoints = state.refinementPoints || this.refinementPoints;
            this.generatedTag = state.generatedTag ?? this.generatedTag;
            
            if (state.decompositionProposal !== undefined) {
                this.decompositionProposal = state.decompositionProposal;
            }
            
            if (state.selectedTags !== undefined) {
                this.selectedTags = [...(state.selectedTags || [])];
            }
            
            if (state.proposedTasks !== undefined) {
                this.proposedTasks = [...(state.proposedTasks || [])];
            }
            
            if (state.selectedTaskSpans !== undefined) {
                this.selectedTaskSpans = { ...(state.selectedTaskSpans || {}) };
            }
            
            if (state.selectedTaskDueDates !== undefined) {
                this.selectedTaskDueDates = [...(state.selectedTaskDueDates || [])];
            }
        }
    });
});

/**
 * メインのダッシュボードコントローラー
 * ダッシュボードの状態を管理し、APIリクエストを処理するクラス。
 */
class DashboardController {
    constructor() {
        this.state = new DashboardState();
        this.modal = new ModalController();
    }

    /**
     * タスクを分解する。
     * @param {boolean} isRefinement - 再提案かどうか。
     */
    async decomposeTask(isRefinement = false) {
        let title = '';
        let span = 2;
        let context = null;

        const titleInput = document.getElementById('taskTitle');
        const spanSelect = document.getElementById('taskSpan');
        const refinementInput = document.getElementById('refinementPoints');

        if (titleInput) {
            title = titleInput.value.trim();
        }

        if (spanSelect) {
            span = parseInt(spanSelect.value, 10);
        }

        if (isRefinement && refinementInput) {
            context = refinementInput.value.trim();
        }

        const store = (window.Alpine && Alpine.store) ? Alpine.store('dashboard') : null;
        if (!title && store?.taskTitle) {
            title = store.taskTitle.trim();
        }
        if (!span && store?.taskSpan) {
            span = store.taskSpan;
        }
        if (isRefinement && !context && store?.refinementPoints) {
            context = store.refinementPoints.trim();
        }

        if (!title && this.state.taskTitle) {
            title = this.state.taskTitle.trim();
        }

        if (!title || title === '') {
            alert('タスク名を入力してください。');
            if (titleInput) {
                titleInput.focus();
            }
            return;
        }

        if (isRefinement && (!context || context === '')) {
            alert('再提案の観点を入力してください。');
            if (refinementInput) {
                refinementInput.focus();
            }
            return;
        }

        try {
            this.modal.showLoading();
            this.updateState({ isProposing: true });

            const response = await TaskAPI.propose(title, span, context, isRefinement);

            let proposedTasksArray = response.proposed_tasks || [];

            if (typeof proposedTasksArray === 'string') {
                try {
                    proposedTasksArray = JSON.parse(proposedTasksArray);
                } catch (e) {
                    proposedTasksArray = this._parseTaskList(proposedTasksArray);
                }
            }
            
            if (!Array.isArray(proposedTasksArray)) {
                proposedTasksArray = [];
            }

            const proposedTasks = proposedTasksArray.map((task, index) => {
                if (typeof task === 'string') {
                    return {
                        title: task,
                        originalSpan: span,
                    };
                } else if (task && typeof task === 'object') {
                    return {
                        title: task.title || task.name || '',
                        originalSpan: span,
                    };
                }
                
                return { title: '', originalSpan: span };
            }).filter(task => task.title.trim() !== '');

            if (proposedTasks.length === 0) {
                alert('タスクの分解に失敗しました。提案されたタスクがありません。');
                this.modal.hideLoading();
                this.updateState({ isProposing: false });
                return;
            }

            const selectedTaskSpans = {};
            proposedTasks.forEach((_, index) => {
                selectedTaskSpans[index] = span;
            });

            const selectedTaskDueDates = Array(proposedTasks.length).fill('');

            this.updateState({
                decompositionProposal: { 
                    proposal_id: response.proposal_id, 
                    proposed_tasks: response.proposed_tasks, 
                    model_used: response.model_used 
                },
                proposedTasks,
                selectedTaskSpans,
                selectedTaskDueDates,
                generatedTag: title,
                isProposing: false,
            });

            this.modal.hideLoading();
            this.modal.switchView('decomposition');
            
        } catch (error) {
            alert('タスクの分解中にエラーが発生しました。\n' + (error.message || ''));
            this.modal.hideLoading();
            this.updateState({ isProposing: false });
        }
    }

    /**
     * 文字列形式のタスクリストをパースする
     * @param {string} text - パースする文字列
     * @returns {Array} タスクの配列
     */
    _parseTaskList(text) {
        if (!text || typeof text !== 'string') return [];
        
        return text
            .split(/\r?\n/)
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .map(line => {
                // "- タスク名" や "1. タスク名" の形式に対応
                const match = line.match(/^(?:[-*]|\d+\.)\s*(.+)$/);
                return match ? match[1].trim() : line;
            })
            .filter(task => task.length > 0);
    }

    /**
     * 提案を確認する。
     */
    async confirmProposal() {
        const store = (window.Alpine && Alpine.store) ? Alpine.store('dashboard') : null;
        const proposedTasks = store?.proposedTasks ?? this.state.proposedTasks;
        const selectedTaskSpans = store?.selectedTaskSpans ?? this.state.selectedTaskSpans;
        const dueDates = store?.selectedTaskDueDates || [];

        if (!proposedTasks || proposedTasks.length === 0) {
            return;
        }

        // スパンが未選択のタスクをチェック
        const hasUnselectedSpan = proposedTasks.some((_, index) => !selectedTaskSpans[index]);
        if (hasUnselectedSpan) {
            alert('すべてのタスクにスパンを選択してください。');
            return;
        }

        try {
            this.updateState({ isProposing: true });

            // タスクにスパンとタグを付与して登録
            const tasksToAdopt = proposedTasks.map((task, index) => ({
                title: task.title,
                span: selectedTaskSpans[index],
                due_date: dueDates[index] || null,
                tags: [store.generatedTag],
            }));

            const proposal = store?.decompositionProposal ?? this.state.decompositionProposal;
            await TaskAPI.adopt(proposal.proposal_id ?? proposal.id, tasksToAdopt);

            this.updateState({
                showDecompositionModal: false,
                decompositionProposal: null,
                proposedTasks: [],
                selectedTaskSpans: {},
                selectedTaskDueDates: [],
                generatedTag: '',
                isProposing: false
            });

            // ページをリロードしてタスクリストを更新
            window.location.reload();
        } catch (error) {
            this.updateState({ isProposing: false });
            this.updateState({ showDecompositionModal: true });
        }
    }

    /**
     * 状態を更新するヘルパーメソッド。
     * @param {Object} updates - 更新する状態のオブジェクト。
     */
    updateState(updates) {
        Object.assign(this.state, updates);
        if (window.Alpine && Alpine.store && Alpine.store('dashboard')) {
            Alpine.store('dashboard').updateFromController(this.state);
        }
    }
}

/**
 * ダッシュボードのイベントハンドラー
 * ユーザーの操作に応じてダッシュボードの状態を更新するクラス。
 */
class DashboardEventHandler {
    constructor(dashboard) {
        this.dashboard = dashboard;
        this.boundHandlers = new Map();
        this.initialized = false;
    }

    /**
     * イベントハンドラーを初期化する。
     */
    init() {
        if (this.initialized) {
            return;
        }
        
        this.initialized = true;

        // DOMContentLoadedで1回だけ初期化
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.#initializeHandlers();
            });
        } else {
            this.#initializeHandlers();
        }
    }

    #initializeHandlers() {
        // タスク登録ボタン - モーダルを開く
        this.#bindButton('open-task-modal-btn', () => {
            this.dashboard.modal.open();
        });

        // モーダルを閉じる（×ボタン）
        this.#bindButton('close-modal-btn', () => {
            this.dashboard.modal.close();
        });

        // モーダル背景クリックで閉じる
        const modalWrapper = document.getElementById('task-modal-wrapper');
        if (modalWrapper) {
            const wrapperHandler = (e) => {
                if (e.target === modalWrapper) {
                    this.dashboard.modal.close();
                }
            };
            modalWrapper.addEventListener('click', wrapperHandler);
            this.boundHandlers.set('task-modal-wrapper-click', wrapperHandler);
        }

        // ESCキーで閉じる
        const escHandler = (e) => {
            if (e.key === 'Escape' && this.dashboard.modal.isOpen()) {
                this.dashboard.modal.close();
            }
        };
        document.addEventListener('keydown', escHandler);
        this.boundHandlers.set('escape-key', escHandler);

        // タイトル入力欄の変更を監視（Alpine.js のフォールバック）
        const taskTitleInput = document.getElementById('taskTitle');
        if (taskTitleInput) {
            const titleInputHandler = (e) => {
                const store = Alpine.store?.('dashboard');
                if (store) {
                    store.taskTitle = e.target.value;
                }
                this.dashboard.state.taskTitle = e.target.value;
            };
            taskTitleInput.addEventListener('input', titleInputHandler);
            this.boundHandlers.set('taskTitle-input', titleInputHandler);
        }

        // スパン変更時の処理（期限入力フィールドの表示切替を JavaScript で制御）
        const taskSpanSelect = document.getElementById('taskSpan');
        if (taskSpanSelect) {
            const spanHandler = (e) => {
                const span = parseInt(e.target.value, 10);
                const store = Alpine.store?.('dashboard');
                if (store) {
                    store.handleSpanChange(span);
                }
                this.dashboard.state.taskSpan = span;
                
                // 期限入力フィールドの表示切替
                this.#toggleDueDateFields(span);
            };
            taskSpanSelect.addEventListener('change', spanHandler);
            this.boundHandlers.set('taskSpan-change', spanHandler);
            
            // 初期表示
            this.#toggleDueDateFields(parseInt(taskSpanSelect.value, 10));
        }

        // 再提案の観点入力の変更を監視
        const refinementInput = document.getElementById('refinementPoints');
        if (refinementInput) {
            const refinementInputHandler = (e) => {
                const store = Alpine.store?.('dashboard');
                if (store) {
                    store.refinementPoints = e.target.value;
                }
                this.dashboard.state.refinementPoints = e.target.value;
            };
            refinementInput.addEventListener('input', refinementInputHandler);
            this.boundHandlers.set('refinementPoints-input', refinementInputHandler);
        }

        // AI分解ボタン
        this.#bindButton('decompose-btn', () => {
            this.dashboard.decomposeTask(false);
        });

        // 提案を採用
        this.#bindButton('adopt-proposal-btn', () => {
            this.dashboard.confirmProposal();
        });

        // 分解キャンセル - 初期入力画面に戻る
        this.#bindButton('cancel-decomposition-btn', () => {
            this.dashboard.modal.switchView('input');
        });

        // 再提案 - 再提案入力画面へ
        this.#bindButton('refine-proposal-btn', () => {
            this.dashboard.modal.switchView('refine');
        });

        // 再提案キャンセル - 提案レビュー画面に戻る
        this.#bindButton('cancel-refine-btn', () => {
            this.dashboard.modal.switchView('decomposition');
        });

        // 再提案送信
        this.#bindButton('submit-refine-btn', () => {
            this.dashboard.decomposeTask(true);
        });

        // そのまま登録（フォーム送信のみ）
        const simpleRegisterBtn = document.getElementById('simple-register-btn');
        if (simpleRegisterBtn) {
            const simpleRegisterHandler = (ev) => {
                const form = document.getElementById('task-form');
                if (form) {
                    // フォームが有効か確認
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        ev.preventDefault();
                        return;
                    }
                }
            };
            simpleRegisterBtn.addEventListener('click', simpleRegisterHandler, { once: false });
            this.boundHandlers.set('simple-register-btn', simpleRegisterHandler);
        }
    }

    /**
     * 期限入力フィールドの表示切替（JavaScript で制御）
     * @param {number} span - スパン値
     */
    #toggleDueDateFields(span) {
        const shortField = document.querySelector('[name="due_date"][type="date"]')?.closest('div');
        const midField = document.querySelector('[name="due_date"] select')?.closest('div');
        const longField = document.querySelector('[name="due_date"][type="text"]')?.closest('div');

        if (shortField) shortField.style.display = span === 1 ? 'block' : 'none';
        if (midField) midField.style.display = span === 2 ? 'block' : 'none';
        if (longField) longField.style.display = span === 3 ? 'block' : 'none';
    }

    #bindButton(id, handler) {
        if (this.boundHandlers.has(id)) {
            return;
        }

        const element = document.getElementById(id);
        if (!element) {
            return;
        }
        
        const boundHandler = (ev) => {
            ev?.preventDefault?.();
            ev?.stopPropagation?.();
            handler.call(this, ev);
        };
        
        element.addEventListener('click', boundHandler, { once: false });
        this.boundHandlers.set(id, boundHandler);
    }

    destroy() {
        this.boundHandlers.forEach((handler, id) => {
            if (id === 'escape-key') {
                document.removeEventListener('keydown', handler);
            } else if (id === 'task-modal-wrapper-click') {
                const modalWrapper = document.getElementById('task-modal-wrapper');
                if (modalWrapper) {
                    modalWrapper.removeEventListener('click', handler);
                }
            } else if (id === 'taskSpan-change') {
                const taskSpanSelect = document.getElementById('taskSpan');
                if (taskSpanSelect) {
                    taskSpanSelect.removeEventListener('change', handler);
                }
            } else if (id === 'taskTitle-input') {
                const taskTitleInput = document.getElementById('taskTitle');
                if (taskTitleInput) {
                    taskTitleInput.removeEventListener('input', handler);
                }
            } else if (id === 'refinementPoints-input') {
                const refinementInput = document.getElementById('refinementPoints');
                if (refinementInput) {
                    refinementInput.removeEventListener('input', handler);
                }
            } else {
                const element = document.getElementById(id);
                if (element) {
                    element.removeEventListener('click', handler);
                }
            }
        });
        this.boundHandlers.clear();
        this.initialized = false;
    }
}

// グローバル初期化（1回のみ）
let dashboardInitialized = false;

document.addEventListener('alpine:init', () => {
    if (dashboardInitialized) {
        return;
    }
    
    dashboardInitialized = true;
    
    const dashboard = new DashboardController();
    const handler = new DashboardEventHandler(dashboard);
    
    window.dashboard = dashboard;
    window.decomposeTask = (...args) => dashboard.decomposeTask(...args);
    window.acceptProposal = () => dashboard.confirmProposal();
    
    // DOM準備完了後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            handler.init();
        });
    } else {
        handler.init();
    }
});