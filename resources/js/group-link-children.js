/**
 * group-link-children.js
 * 
 * 親子アカウント一括紐づけ機能
 * - 検索結果をモーダルで表示
 * - 各子アカウントに「×」ボタン（除外用）
 * - 選択した子アカウントを一括紐づけ
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('search-children-form');
    const searchResultsModal = document.getElementById('search-results-modal');
    const closeModalButtons = document.querySelectorAll('[data-close-modal]');
    const linkChildrenForm = document.getElementById('link-children-form');
    const childrenList = document.getElementById('children-list');
    let selectedChildren = new Set();

    if (!searchForm) {
        return;
    }

    /**
     * 検索フォーム送信処理
     */
    searchForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(searchForm);
        const submitButton = searchForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;

        // ローディング表示
        submitButton.disabled = true;
        submitButton.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> 検索中...';

        try {
            const response = await fetch(searchForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success && data.data.children.length > 0) {
                // 検索結果をモーダルに表示
                displaySearchResults(data.data.children);
                openModal(searchResultsModal);
            } else if (data.data.children.length === 0) {
                // 確認ダイアログで表示
                if (typeof window.showAlertDialog === 'function') {
                    window.showAlertDialog('該当する子アカウントが見つかりませんでした。');
                } else {
                    alert('該当する子アカウントが見つかりませんでした。');
                }
            } else {
                // 確認ダイアログで表示
                if (typeof window.showAlertDialog === 'function') {
                    window.showAlertDialog(data.message || '検索に失敗しました。');
                } else {
                    alert(data.message || '検索に失敗しました。');
                }
            }
        } catch (error) {
            console.error('Search error:', error);
            // 確認ダイアログで表示
            if (typeof window.showAlertDialog === 'function') {
                window.showAlertDialog('検索中にエラーが発生しました。');
            } else {
                alert('検索中にエラーが発生しました。');
            }
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });

    /**
     * 検索結果をモーダルに表示
     */
    function displaySearchResults(children) {
        selectedChildren.clear();
        childrenList.innerHTML = '';

        children.forEach(child => {
            selectedChildren.add(child.id);
            const childCard = createChildCard(child);
            childrenList.appendChild(childCard);
        });

        updateLinkButton();
    }

    /**
     * 子アカウントカードを作成
     */
    function createChildCard(child) {
        const card = document.createElement('div');
        card.className = 'border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-800 transition';
        card.dataset.childId = child.id;

        card.innerHTML = `
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <!-- アバター -->
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center text-white font-semibold text-sm shadow-md">
                            ${child.username.charAt(0).toUpperCase()}
                        </div>
                    </div>

                    <!-- ユーザー情報 -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                ${escapeHtml(child.name || child.username)}
                            </p>
                            ${child.is_minor ? '<span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded">13歳未満</span>' : ''}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                            @${escapeHtml(child.username)}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 truncate">
                            ${escapeHtml(child.email)}
                        </p>
                    </div>
                </div>

                <!-- 除外ボタン -->
                <button type="button" 
                        class="remove-child flex-shrink-0 w-8 h-8 rounded-lg border-2 border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition flex items-center justify-center font-bold"
                        data-child-id="${child.id}"
                        title="除外">
                    ✕
                </button>
            </div>
        `;

        // 除外ボタンのイベントリスナー
        card.querySelector('.remove-child').addEventListener('click', function() {
            removeChild(child.id);
        });

        return card;
    }

    /**
     * 子アカウントを除外
     */
    function removeChild(childId) {
        selectedChildren.delete(childId);
        const card = childrenList.querySelector(`[data-child-id="${childId}"]`);
        if (card) {
            card.remove();
        }
        updateLinkButton();

        // 全て除外された場合はメッセージ表示
        if (selectedChildren.size === 0) {
            childrenList.innerHTML = '<p class="text-center text-gray-500 dark:text-gray-400 py-8">選択された子アカウントがありません</p>';
        }
    }

    /**
     * 紐づけボタンの有効/無効を更新
     */
    function updateLinkButton() {
        const linkButton = linkChildrenForm.querySelector('button[type="submit"]');
        const count = selectedChildren.size;
        
        if (count === 0) {
            linkButton.disabled = true;
            linkButton.querySelector('.button-text').textContent = '紐づける子アカウントを選択してください';
        } else {
            linkButton.disabled = false;
            linkButton.querySelector('.button-text').textContent = `選択した${count}人を紐づける`;
        }
    }

    /**
     * 紐づけフォーム送信処理
     */
    if (linkChildrenForm) {
        linkChildrenForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (selectedChildren.size === 0) {
                // 確認ダイアログで表示
                if (typeof window.showAlertDialog === 'function') {
                    window.showAlertDialog('紐づける子アカウントを選択してください。');
                } else {
                    alert('紐づける子アカウントを選択してください。');
                }
                return;
            }

            const submitButton = linkChildrenForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.querySelector('.button-text').textContent;

            // ローディング表示
            submitButton.disabled = true;
            submitButton.querySelector('.button-text').textContent = '紐づけ中...';

            try {
                const response = await fetch(linkChildrenForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        child_user_ids: Array.from(selectedChildren)
                    })
                });

                // レスポンスをJSON解析
                const data = await response.json();

                // ステータスコード200または206の場合は成功
                if (response.ok || response.status === 206) {
                    // 成功メッセージを表示
                    let successMessage = data.message;
                    
                    // スキップされたアカウントがある場合は詳細を追加
                    if (data.data && data.data.skipped_children && data.data.skipped_children.length > 0) {
                        successMessage += '\n\n以下のアカウントは紐づけできませんでした：\n';
                        data.data.skipped_children.forEach(skipped => {
                            successMessage += `• ${skipped.username}: ${skipped.reason}\n`;
                        });
                    }
                    
                    // 確認ダイアログで表示してからリロード
                    if (typeof window.showAlertDialog === 'function') {
                        window.showAlertDialog(successMessage, () => {
                            window.location.reload();
                        });
                    } else {
                        alert(successMessage);
                        window.location.reload();
                    }
                } else {
                    // エラーメッセージを表示
                    let errorMessage = data.message || '紐づけに失敗しました。';
                    
                    // スキップされたアカウントがある場合は詳細を表示
                    if (data.data && data.data.skipped_children && data.data.skipped_children.length > 0) {
                        errorMessage += '\n\n以下のアカウントは紐づけできませんでした：\n';
                        data.data.skipped_children.forEach(skipped => {
                            errorMessage += `• ${skipped.username}: ${skipped.reason}\n`;
                        });
                    }
                    
                    // 確認ダイアログで表示
                    if (typeof window.showAlertDialog === 'function') {
                        window.showAlertDialog(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            } catch (error) {
                console.error('Link error:', error);
                // 確認ダイアログで表示
                if (typeof window.showAlertDialog === 'function') {
                    window.showAlertDialog('紐づけ中にエラーが発生しました。');
                } else {
                    alert('紐づけ中にエラーが発生しました。');
                }
            } finally {
                submitButton.disabled = false;
                submitButton.querySelector('.button-text').textContent = originalButtonText;
            }
        });
    }

    /**
     * モーダルを開く
     */
    function openModal(modal) {
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * モーダルを閉じる
     */
    function closeModal(modal) {
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    /**
     * モーダル閉じるボタンのイベントリスナー
     */
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-close-modal');
            const modal = document.getElementById(modalId);
            closeModal(modal);
        });
    });

    /**
     * モーダル背景クリックで閉じる
     */
    if (searchResultsModal) {
        searchResultsModal.addEventListener('click', function(e) {
            if (e.target === searchResultsModal) {
                closeModal(searchResultsModal);
            }
        });
    }

    /**
     * HTMLエスケープ
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
