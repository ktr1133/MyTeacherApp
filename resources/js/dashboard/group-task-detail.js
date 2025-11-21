/**
 * グループタスク詳細モーダル制御（Vanilla JS）
 */

class GroupTaskDetailModal {
    constructor(taskId) {
        this.taskId = taskId;
        this.modal = document.getElementById(`group-task-detail-modal-${taskId}`);
        this.overlay = this.modal?.querySelector('.modal-overlay');
        this.content = this.modal?.querySelector('.modal-content');
        this.closeBtn = this.modal?.querySelector('.modal-close-btn');
        this.form = document.getElementById(`approval-form-${taskId}`);
        this.fileInput = document.getElementById(`approval-images-${taskId}`);
        this.previewContainer = document.getElementById(`preview-container-${taskId}`);
        this.submitBtn = this.modal?.querySelector('.submit-approval-btn');
        
        this.previewImages = [];
        
        if (this.modal) {
            this.init();
        }
    }
    
    init() {
        // モーダルを開くイベント
        window.addEventListener(`open-task-modal-${this.taskId}`, () => this.open());
        
        // 閉じるボタン
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }
        
        // オーバーレイクリックで閉じる
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });
        }
        
        // ESCキーで閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.close();
            }
        });
        
        // ファイル選択イベント
        if (this.fileInput) {
            this.fileInput.addEventListener('change', (e) => this.handleImageSelect(e));
        }
        
        // 送信ボタンの有効/無効制御
        if (this.submitBtn) {
            this.updateSubmitButton();
        }
    }
    
    open() {
        if (!this.modal) return;
        
        this.modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // アニメーション
        requestAnimationFrame(() => {
            this.overlay?.classList.remove('opacity-0');
            this.overlay?.classList.add('opacity-100');
            this.content?.classList.remove('opacity-0', 'scale-95');
            this.content?.classList.add('opacity-100', 'scale-100');
        });
    }
    
    close() {
        if (!this.modal) return;
        
        // アニメーション
        this.overlay?.classList.remove('opacity-100');
        this.overlay?.classList.add('opacity-0');
        this.content?.classList.remove('opacity-100', 'scale-100');
        this.content?.classList.add('opacity-0', 'scale-95');
        
        setTimeout(() => {
            this.modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            this.clearPreviews();
        }, 300);
    }
    
    handleImageSelect(event) {
        const files = Array.from(event.target.files);
        const existingCount = parseInt(this.fileInput.dataset.existingCount || '0');
        const maxFiles = 3 - existingCount;
        
        // 枚数制限チェック
        if (files.length > maxFiles) {
            alert(`画像は最大${maxFiles}枚までアップロードできます。`);
            event.target.value = '';
            return;
        }
        
        // プレビューを生成
        this.previewImages = [];
        if (this.previewContainer) {
            this.previewContainer.innerHTML = '';
        }
        
        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewImages.push({
                    url: e.target.result,
                    name: file.name,
                    size: (file.size / 1024).toFixed(2) + ' KB',
                    index: index
                });
                
                this.renderPreview({
                    url: e.target.result,
                    name: file.name,
                    size: (file.size / 1024).toFixed(2) + ' KB',
                    index: index
                });
                
                this.updateSubmitButton();
            };
            reader.readAsDataURL(file);
        });
    }
    
    renderPreview(preview) {
        if (!this.previewContainer) return;
        
        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'relative group';
        previewWrapper.dataset.index = preview.index;
        
        previewWrapper.innerHTML = `
            <img src="${preview.url}" 
                 class="w-full h-32 object-cover rounded-lg border"
                 alt="${preview.name}">
            <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs p-1 rounded-b-lg">
                <p class="truncate">${preview.name}</p>
                <p>${preview.size}</p>
            </div>
            <button type="button"
                    class="preview-remove-btn absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition"
                    data-index="${preview.index}">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        `;
        
        // 削除ボタンのイベント
        const removeBtn = previewWrapper.querySelector('.preview-remove-btn');
        removeBtn.addEventListener('click', () => this.removePreview(preview.index));
        
        this.previewContainer.appendChild(previewWrapper);
        
        // プレビューコンテナを表示
        this.previewContainer.parentElement?.classList.remove('hidden');
    }
    
    removePreview(index) {
        // プレビューから削除
        this.previewImages = this.previewImages.filter(p => p.index !== index);
        
        // DOM から削除
        const previewElement = this.previewContainer?.querySelector(`[data-index="${index}"]`);
        if (previewElement) {
            previewElement.remove();
        }
        
        // ファイル入力をクリア
        if (this.fileInput) {
            this.fileInput.value = '';
        }
        
        // プレビューが空になったら非表示
        if (this.previewImages.length === 0 && this.previewContainer) {
            this.previewContainer.parentElement?.classList.add('hidden');
        }
        
        this.updateSubmitButton();
    }
    
    clearPreviews() {
        this.previewImages = [];
        if (this.previewContainer) {
            this.previewContainer.innerHTML = '';
            this.previewContainer.parentElement?.classList.add('hidden');
        }
        if (this.fileInput) {
            this.fileInput.value = '';
        }
    }
    
    updateSubmitButton() {
        if (!this.submitBtn) return;
        
        const requiresImage = this.submitBtn.dataset.requiresImage === 'true';
        const existingImages = parseInt(this.submitBtn.dataset.existingImages || '0');
        const newImages = this.previewImages.length;
        
        if (requiresImage && existingImages === 0 && newImages === 0) {
            this.submitBtn.disabled = true;
        } else {
            this.submitBtn.disabled = false;
        }
    }
}

// DOM読み込み後に初期化
document.addEventListener('DOMContentLoaded', () => {
    // すべてのグループタスク詳細モーダルを初期化
    const modals = document.querySelectorAll('[id^="group-task-detail-modal-"]');
    modals.forEach(modal => {
        const taskId = modal.id.replace('group-task-detail-modal-', '');
        new GroupTaskDetailModal(taskId);
    });
});

// グローバル関数でモーダルを開く
window.openGroupTaskDetailModal = function(taskId) {
    window.dispatchEvent(new CustomEvent(`open-task-modal-${taskId}`));
};
