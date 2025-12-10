/**
 * グループタスク登録モーダルの制御
 */

// モーダル要素
const groupModal = document.getElementById('group-task-modal-wrapper');
const groupModalContent = document.getElementById('group-task-modal-content');
const openGroupModalBtn = document.getElementById('open-group-task-modal-btn');
const closeGroupModalBtn = document.getElementById('close-group-modal-btn');
const cancelGroupTaskBtn = document.getElementById('cancel-group-task-btn');
const groupTaskForm = document.getElementById('group-task-form');

// タスク作成方式切替
const taskModeRadios = document.querySelectorAll('input[name="task_mode"]');
const newTaskForm = document.getElementById('new-task-form');
const templateTaskForm = document.getElementById('template-task-form');
const taskTemplateSelect = document.getElementById('taskTemplate');
const templatePreview = document.getElementById('template-preview');

// 初期状態の設定（テンプレート選択のrequired属性をfalseに）
if (taskTemplateSelect) {
    taskTemplateSelect.required = false;
}

// モーダル開く
if (openGroupModalBtn) {
    openGroupModalBtn.addEventListener('click', () => {
        openModal(groupModal, groupModalContent);
    });
}

// モーダル閉じる
if (closeGroupModalBtn) {
    closeGroupModalBtn.addEventListener('click', () => {
        closeModal(groupModal, groupModalContent);
        resetForm();
    });
}

if (cancelGroupTaskBtn) {
    cancelGroupTaskBtn.addEventListener('click', () => {
        closeModal(groupModal, groupModalContent);
        resetForm();
    });
}

// 背景クリックで閉じる
if (groupModal) {
    groupModal.addEventListener('click', (e) => {
        if (e.target === groupModal) {
            closeModal(groupModal, groupModalContent);
            resetForm();
        }
    });
}

// タスク作成方式の切替
taskModeRadios.forEach(radio => {
    radio.addEventListener('change', (e) => {
        if (e.target.value === 'new') {
            newTaskForm.style.display = 'block';
            templateTaskForm.style.display = 'none';
            document.getElementById('groupTaskTitle').required = true;
            taskTemplateSelect.required = false;
        } else {
            newTaskForm.style.display = 'none';
            templateTaskForm.style.display = 'block';
            document.getElementById('groupTaskTitle').required = false;
            taskTemplateSelect.required = true;
        }
    });
});

// テンプレート選択時のプレビュー表示と報酬フィールドへの反映
if (taskTemplateSelect) {
    taskTemplateSelect.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        
        if (selectedOption.value) {
            const title = selectedOption.dataset.title || '';
            const description = selectedOption.dataset.description || '説明なし';
            const reward = selectedOption.dataset.reward || '0';
            
            document.getElementById('preview-title').textContent = title;
            document.getElementById('preview-description').textContent = description;
            templatePreview.style.display = 'block';
            
            // 報酬フィールドに値を設定
            const rewardInput = document.getElementById('taskReward');
            if (rewardInput) {
                rewardInput.value = reward;
            }
        } else {
            templatePreview.style.display = 'none';
            // 報酬フィールドをクリア
            const rewardInput = document.getElementById('taskReward');
            if (rewardInput) {
                rewardInput.value = '';
            }
        }
    });
}

// フォーム送信
if (groupTaskForm) {
    groupTaskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(groupTaskForm);
        
        // タスク作成方式に応じて処理を分岐
        const taskMode = formData.get('task_mode');
        if (taskMode === 'template') {
            // テンプレートの場合、選択したタスク情報をコピー
            const selectedOption = taskTemplateSelect.options[taskTemplateSelect.selectedIndex];
            formData.set('title', selectedOption.dataset.title);
            formData.set('description', selectedOption.dataset.description || '');
            formData.set('reward', selectedOption.dataset.reward || '0');
        }
        
        // spanは短期固定
        formData.set('span', '1');
        
        try {
            const response = await fetch(groupTaskForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            if (response.ok) {
                const data = await response.json();
                
                // モーダルを閉じる
                closeModal(groupModal, groupModalContent);
                resetForm();
                
                // アバターイベントが返ってきた場合、表示
                if (data.avatar_event) {
                    console.log('[Group Task] Dispatching avatar event:', data.avatar_event);
                    
                    if (window.dispatchAvatarEvent) {
                        window.dispatchAvatarEvent(data.avatar_event);
                    } else {
                        console.error('[Group Task] dispatchAvatarEvent not found');
                    }
                }
            } else {
                const errorData = await response.json();
                alert('タスクの作成に失敗しました: ' + (errorData.message || '不明なエラー'));
            }
        } catch (error) {
            console.error('[Group Task] Error:', error);
            alert('タスクの作成中にエラーが発生しました。');
        }
    });
}

// フォームリセット
function resetForm() {
    if (groupTaskForm) {
        groupTaskForm.reset();
        newTaskForm.style.display = 'block';
        templateTaskForm.style.display = 'none';
        templatePreview.style.display = 'none';
        
        // required属性を初期状態に戻す
        if (document.getElementById('groupTaskTitle')) {
            document.getElementById('groupTaskTitle').required = true;
        }
        if (taskTemplateSelect) {
            taskTemplateSelect.required = false;
        }
    }
}

// モーダル開閉関数（既存のdashboard.jsと共通化推奨）
function openModal(modal, content) {
    modal.classList.remove('hidden');
    modal.setAttribute('data-modal-state', 'open');
    requestAnimationFrame(() => {
        modal.classList.remove('opacity-0');
        modal.classList.add('flex', 'opacity-100');
        content.classList.remove('translate-y-4', 'scale-95');
        content.classList.add('translate-y-0', 'scale-100');
    });
}

function closeModal(modal, content) {
    modal.classList.remove('opacity-100');
    modal.classList.add('opacity-0');
    content.classList.remove('translate-y-0', 'scale-100');
    content.classList.add('translate-y-4', 'scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-modal-state', 'closed');
    }, 300);
}