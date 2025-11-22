/**
 * タグ管理ページのタスク紐付けモーダル制御
 * 
 * タグをクリックすると、そのタグに紐づくタスク一覧と
 * 未紐付けのタスク一覧をモーダルで表示し、
 * タスクの追加・解除を行うことができる。
 * 
 * @requires fetch API
 * @requires meta[name="csrf-token"] - CSRF トークン
 * @requires #tag-page[data-app-origin] - Laravel アプリケーションのオリジン
 * @requires #tag-page[data-tags-base] - タグ API のベースパス
 */
(function() {
    // DOM 要素の取得
    const modal = document.getElementById('tag-task-modal');
    const closeBtns = [
        document.getElementById('close-tag-task-modal'),
        document.getElementById('close-tag-task-modal-bottom'),
    ];
    const linkedList = document.getElementById('linked-tasks');
    const linkedCount = document.getElementById('linked-count');
    const availableSelect = document.getElementById('available-task-select');
    const attachBtn = document.getElementById('attach-task-btn');
    const badge = document.getElementById('current-tag-badge');

    // DOM要素の存在確認
    if (!modal) {
        console.error('tag-task-modal element not found');
        return;
    }

    /**
     * URL 構築に使う基底情報を Blade から受け取る
     * 
     * Vite の dev サーバー経由でも正しく Laravel 側の API を叩けるよう、
     * data 属性からオリジンとベースパスを取得する。
     */
    const pageRoot = document.getElementById('tag-page');
    const APP_ORIGIN = pageRoot?.dataset.appOrigin || window.location.origin; // 例: http://localhost:8000
    const TAGS_BASE  = pageRoot?.dataset.tagsBase  || '/tags';               // 例: /tags

    /**
     * API の URL を構築する
     * 
     * @param {string|number} tagId - タグ ID
     * @param {string} type - エンドポイントのタイプ ('fetch', 'attach', 'detach')
     * @returns {string} 完全な API URL
     * 
     * @example
     * buildUrl(3, 'fetch')  // => "http://localhost:8000/tags/3/tasks"
     * buildUrl(3, 'attach') // => "http://localhost:8000/tags/3/tasks/attach"
     */
    function buildUrl(tagId, type) {
        switch (type) {
            case 'fetch':  return `${APP_ORIGIN}${TAGS_BASE}/${tagId}/tasks`;
            case 'attach': return `${APP_ORIGIN}${TAGS_BASE}/${tagId}/tasks/attach`;
            case 'detach': return `${APP_ORIGIN}${TAGS_BASE}/${tagId}/tasks/detach`;
            default:       return `${APP_ORIGIN}${TAGS_BASE}`;
        }
    }

    /**
     * 現在選択中のタグ ID
     * @type {string|null}
     */
    let currentTagId = null;

    /**
     * 現在選択中のタグ名
     * @type {string}
     */
    let currentTagName = '';

    /**
     * CSRF トークンを取得する
     * 
     * @returns {string} CSRF トークン
     */
    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * モーダルを開く
     * 
     * hidden クラスを削除し、flex クラスを追加することで
     * モーダルを表示状態にする。
     * トランジション用のクラスを適切に制御する。
     */
    function openModal() {
        // モーダルを表示状態にする
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-modal-state', 'open');
        
        // 次のフレームでトランジションクラスを適用
        // これにより、CSSトランジションがスムーズに動作する
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
                modal.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            });
        });
        
        console.log('Modal opened');
    }

    /**
     * モーダルを閉じる
     * 
     * モーダルを非表示にし、内部の状態をリセットする。
     * - タグ ID とタグ名をクリア
     * - タスク一覧をクリア
     * - セレクトボックスをクリア
     * - 追加ボタンを無効化
     */
    function closeModal() {
        // トランジションクラスを戻す
        modal.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        modal.classList.add('opacity-0', 'translate-y-4', 'scale-95');
        
        // トランジションが完了してから非表示にする
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            modal.setAttribute('data-modal-state', 'closed');
            
            // 状態をリセット
            currentTagId = null;
            currentTagName = '';
            linkedList.innerHTML = '';
            availableSelect.innerHTML = '';
            attachBtn.disabled = true;
        }, 300); // CSSのtransition-durationと合わせる
        // 画面をリロードする
        location.reload();
    }

    /**
     * タグに紐づくタスクと未紐付けタスクを API から取得する
     * 
     * @async
     * @param {string|number} tagId - タグ ID
     * @returns {Promise<{linked: Array<{id: number, title: string}>, available: Array<{id: number, title: string}>}>}
     *          紐づくタスクと未紐付けタスクのリスト
     * @throws {Error} API リクエストが失敗した場合
     * 
     * @example
     * const data = await fetchTagTasks(3);
     * // => { linked: [{id: 1, title: "タスクA"}], available: [{id: 2, title: "タスクB"}] }
     */
    async function fetchTagTasks(tagId) {
        const url = buildUrl(tagId, 'fetch');
        const res = await fetch(url, { 
            headers: { 'Accept': 'application/json' }, 
            credentials: 'include' 
        });
        if (!res.ok) throw new Error('Failed to load tasks');
        return res.json(); // { linked: [{id,title}], available: [{id,title}] }
    }

    /**
     * タグに紐づいているタスク一覧を描画する
     * 
     * タスクがない場合は「紐づくタスクはありません」というメッセージを表示。
     * タスクがある場合は、各タスクに「解除」ボタンを付けてリスト表示。
     * 
     * @param {Array<{id: number, title: string}>} tasks - 紐づくタスクの配列
     * 
     * @example
     * renderLinkedTasks([
     *   { id: 1, title: "タスクA" },
     *   { id: 2, title: "タスクB" }
     * ]);
     */
    function renderLinkedTasks(tasks) {
        linkedList.innerHTML = '';
        if (!tasks || tasks.length === 0) {
            linkedList.innerHTML = `
                <li class="p-8 text-center text-gray-400 dark:text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-sm">このタグに紐づくタスクはありません</p>
                </li>
            `;
            linkedCount.textContent = '0';
            return;
        }
        linkedCount.textContent = `${tasks.length}`;
        tasks.forEach(t => {
            const li = document.createElement('li');
            li.className = 'p-4 flex items-start sm:items-center gap-3 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition group';
            li.innerHTML = `
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-800 dark:text-white break-words" title="${escapeHtml(t.title)}">${escapeHtml(t.title)}</p>
                </div>
                <button class="tag-detach-btn shrink-0 relative inline-flex items-center justify-center w-8 h-8 rounded-full transition-all duration-200 group/btn"
                        data-action="detach" data-task-id="${t.id}"
                        type="button"
                        aria-label="タグから解除">
                    <span class="tag-detach-btn-bg absolute inset-0 rounded-full bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-950/30 dark:to-pink-950/30 opacity-0 group-hover/btn:opacity-100 transition-opacity duration-200"></span>
                    <span class="tag-detach-btn-border absolute inset-0 rounded-full border-2 border-red-200 dark:border-red-800 group-hover/btn:border-red-400 dark:group-hover/btn:border-red-600 transition-colors duration-200"></span>
                    <svg class="tag-detach-btn-icon relative z-10 w-4 h-4 text-red-500 dark:text-red-400 group-hover/btn:text-red-600 dark:group-hover/btn:text-red-300 group-hover/btn:rotate-90 transition-all duration-200" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            linkedList.appendChild(li);
        });
    }

    /**
     * HTMLエスケープ関数
     * XSS対策のため、HTMLの特殊文字をエスケープする
     * 
     * @param {string} text - エスケープする文字列
     * @returns {string} エスケープされた文字列
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * 追加可能なタスク一覧をセレクトボックスに描画する
     * 
     * タスクがない場合は「追加可能なタスクがありません」を表示し、
     * セレクトボックスと追加ボタンを無効化。
     * タスクがある場合は、各タスクを option 要素として追加し、
     * セレクトボックスと追加ボタンを有効化。
     * 
     * @param {Array<{id: number, title: string}>} tasks - 未紐付けタスクの配列
     * 
     * @example
     * renderAvailableTasks([
     *   { id: 3, title: "タスクC" },
     *   { id: 4, title: "タスクD" }
     * ]);
     */
    function renderAvailableTasks(tasks) {
        availableSelect.innerHTML = '';
        
        // プレースホルダーオプション
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'タスクを選択してください';
        availableSelect.appendChild(placeholder);
        
        if (!tasks || tasks.length === 0) {
            availableSelect.disabled = true;
            attachBtn.disabled = true;
            return;
        }
        
        tasks.forEach(t => {
            const opt = document.createElement('option');
            opt.value = String(t.id);
            opt.textContent = t.title;
            availableSelect.appendChild(opt);
        });
        
        availableSelect.disabled = false;
        // セレクトボックスで値が選択されたら追加ボタンを有効化
        availableSelect.addEventListener('change', function() {
            attachBtn.disabled = !this.value;
        });
    }

    /**
     * タスクをタグに紐付ける
     * 
     * API にリクエストを送信し、指定したタスクを現在のタグに紐付ける。
     * 
     * @async
     * @param {string|number} tagId - タグ ID
     * @param {string|number} taskId - タスク ID
     * @returns {Promise<void>}
     * @throws {Error} API リクエストが失敗した場合
     * 
     * @example
     * await attachTask(3, 5); // タグID:3 にタスクID:5 を紐付ける
     */
    async function attachTask(tagId, taskId) {
        const url = buildUrl(tagId, 'attach');
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ task_id: taskId })
        });
        if (!res.ok) throw new Error('Attach failed');
    }

    /**
     * タスクからタグを解除する
     * 
     * API にリクエストを送信し、指定したタスクから現在のタグの紐付けを解除。
     * Laravel の DELETE メソッドを使用するため、_method パラメータを付与。
     * 
     * @async
     * @param {string|number} tagId - タグ ID
     * @param {string|number} taskId - タスク ID
     * @returns {Promise<void>}
     * @throws {Error} API リクエストが失敗した場合
     * 
     * @example
     * await detachTask(3, 5); // タグID:3 からタスクID:5 の紐付けを解除
     */
    async function detachTask(tagId, taskId) {
        const url = buildUrl(tagId, 'detach');
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ task_id: taskId, _method: 'DELETE' })
        });
        if (!res.ok) throw new Error('Detach failed');
    }

    /**
     * イベントハンドラー: タグクリック → モーダルオープン
     * 
     * data-action="open-tag-modal" 属性を持つ要素がクリックされた際、
     * タグ ID とタグ名を取得し、API からタスク一覧を取得してモーダルを開く。
     * 
     * @listens document#click
     */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action="open-tag-modal"]');
        
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();
        
        currentTagId = btn.getAttribute('data-tag-id');
        currentTagName = btn.getAttribute('data-tag-name') || '';
        
        console.log('Tag ID:', currentTagId);
        console.log('Tag Name:', currentTagName);
        
        badge.textContent = currentTagName;

        try {
            const data = await fetchTagTasks(currentTagId);
            renderLinkedTasks(data.linked || []);
            renderAvailableTasks(data.available || []);
            openModal();
        } catch (err) {
            console.error('Failed to fetch tasks:', err);
            if (window.showAlertDialog) {
                window.showAlertDialog('タスクの取得に失敗しました。', 'エラー');
            } else {
                alert('タスクの取得に失敗しました。');
            }
        }
    });

    /**
     * イベントハンドラー: 解除ボタンクリック（イベント委譲）
     * 
     * 紐づくタスクリスト内の「解除」ボタンがクリックされた際、
     * そのタスクをタグから解除し、タスク一覧を再取得して再描画。
     * 
     * @listens linkedList#click
     */
    linkedList.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action="detach"]');
        if (!btn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const taskId = btn.getAttribute('data-task-id');
        try {
            await detachTask(currentTagId, taskId);
            const data = await fetchTagTasks(currentTagId);
            renderLinkedTasks(data.linked || []);
            renderAvailableTasks(data.available || []);
        } catch (err) {
            console.error(err);
            if (window.showAlertDialog) {
                window.showAlertDialog('タスクの解除に失敗しました。', 'エラー');
            } else {
                alert('タスクの解除に失敗しました。');
            }
        }
    });

    /**
     * イベントハンドラー: 追加ボタンクリック
     * 
     * セレクトボックスで選択されたタスクをタグに紐付け、
     * タスク一覧を再取得して再描画。
     * 
     * @listens attachBtn#click
     */
    attachBtn.addEventListener('click', async () => {
        const taskId = availableSelect.value;
        if (!taskId) return;
        try {
            await attachTask(currentTagId, taskId);
            const data = await fetchTagTasks(currentTagId);
            renderLinkedTasks(data.linked || []);
            renderAvailableTasks(data.available || []);
        } catch (err) {
            console.error(err);
            if (window.showAlertDialog) {
                window.showAlertDialog('タスクの追加に失敗しました。', 'エラー');
            } else {
                alert('タスクの追加に失敗しました。');
            }
        }
    });

    /**
     * イベントハンドラー: 閉じるボタンクリック
     * 
     * モーダルのヘッダーまたはフッターにある閉じるボタンで
     * モーダルを閉じる。
     * 
     * @listens closeBtns#click
     */
    closeBtns.forEach(b => b?.addEventListener('click', closeModal));

    /**
     * イベントハンドラー: モーダル背景クリック
     * 
     * モーダルの背景（オーバーレイ）をクリックした際、
     * モーダルを閉じる。
     * 
     * @listens modal#click
     */
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    /**
     * イベントハンドラー: ESC キー押下
     * 
     * Escape キーが押された際、モーダルが開いていれば閉じる。
     * 
     * @listens document#keydown
     */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();