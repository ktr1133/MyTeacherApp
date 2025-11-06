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
     */
    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
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
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentTagId = null;
        currentTagName = '';
        linkedList.innerHTML = '';
        availableSelect.innerHTML = '';
        attachBtn.disabled = true;
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
            linkedList.innerHTML = '<li class="p-3 text-sm text-gray-500">このタグに紐づくタスクはありません。</li>';
            linkedCount.textContent = '0 件';
            return;
        }
        linkedCount.textContent = `${tasks.length} 件`;
        tasks.forEach(t => {
            const li = document.createElement('li');
            li.className = 'p-3 flex items-center justify-between gap-3';
            li.innerHTML = `
                <div class="min-w-0">
                    <p class="text-sm text-gray-800 truncate" title="${t.title}">${t.title}</p>
                </div>
                <button class="inline-flex items-center px-2 py-1 rounded-md text-red-600 hover:bg-red-50 text-xs"
                        data-action="detach" data-task-id="${t.id}">
                    解除
                </button>
            `;
            linkedList.appendChild(li);
        });
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
        if (!tasks || tasks.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '追加可能なタスクがありません';
            availableSelect.appendChild(opt);
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
        attachBtn.disabled = false;
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
            method: 'POST', // DELETE を使うならここを 'DELETE' にし、サーバールートも合わせる
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
        console.log('タグクリック', btn);
        if (!btn) return;

        currentTagId = btn.getAttribute('data-tag-id');
        currentTagName = btn.getAttribute('data-tag-name') || '';
        badge.textContent = currentTagName;

        try {
            const data = await fetchTagTasks(currentTagId);
            renderLinkedTasks(data.linked || []);
            renderAvailableTasks(data.available || []);
            openModal();
        } catch (err) {
            console.error(err);
            alert('タスクの取得に失敗しました。');
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
        const taskId = btn.getAttribute('data-task-id');
        try {
            await detachTask(currentTagId, taskId);
            const data = await fetchTagTasks(currentTagId);
            renderLinkedTasks(data.linked || []);
            renderAvailableTasks(data.available || []);
        } catch (err) {
            console.error(err);
            alert('タスクの解除に失敗しました。');
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
            alert('タスクの追加に失敗しました。');
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
    closeBtns.forEach(b => b.addEventListener('click', closeModal));

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
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
})();