/**
 * アバターウィザード（子供テーマ）コントローラー
 * Alpine.jsから移行: Vanilla JavaScript実装
 */
class AvatarWizardChildController {
    /**
     * コンストラクタ
     * 5ステップウィザードの初期化（性別→見た目→性格→画風→確認）
     */
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 5;
        
        // デフォルト値
        this.formData = window.avatarDefaults || {
            sex: 'male',
            hair_color: 'black',
            hair_style: 'short',
            eye_color: 'brown',
            clothing: 'casual',
            accessory: '',
            body_type: 'average',
            tone: 'gentle',
            enthusiasm: 'normal',
            formality: 'polite',
            humor: 'normal',
            draw_model_version: 'anything-v4.0',
            is_transparent: true,
            is_chibi: false,
        };
        
        this.options = window.avatarOptions || {};
        this.models = (window.avatarOptions && window.avatarOptions.draw_models) || {};
        
        this.init();
    }
    
    /**
     * 初期化処理
     * localStorage復元、イベントリスナー登録、UI更新、離脱警告設定を実行
     */
    init() {
        console.log('[Avatar Wizard Child] Initialized');
        this.restoreFromStorage();
        this.setupEventListeners();
        this.updateUI();
        
        // ページ離脱警告
        window.addEventListener('beforeunload', (e) => {
            if (this.currentStep > 1 && this.currentStep < 5) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }
    
    /**
     * イベントリスナーの登録
     * ナビゲーションボタン、選択肢ボタン、モデル選択、トグルボタンの各イベントを設定
     */
    setupEventListeners() {
        // ナビゲーションボタン
        document.querySelectorAll('[data-next-step]').forEach(btn => {
            btn.addEventListener('click', () => this.nextStep());
        });
        
        document.querySelectorAll('[data-prev-step]').forEach(btn => {
            btn.addEventListener('click', () => this.prevStep());
        });
        
        // 選択肢ボタン
        document.querySelectorAll('[data-select-option]').forEach(btn => {
            btn.addEventListener('click', () => {
                const field = btn.dataset.selectOption;
                const value = btn.dataset.value;
                this.selectOption(field, value);
            });
        });
        
        // モデル選択
        document.querySelectorAll('[data-select-model]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modelKey = btn.dataset.selectModel;
                this.selectModel(modelKey);
            });
        });
        
        // トグルボタン
        const transparentToggle = document.querySelector('[data-toggle-transparent]');
        if (transparentToggle) {
            transparentToggle.addEventListener('click', () => {
                this.formData.is_transparent = !this.formData.is_transparent;
                this.updateUI();
                this.saveToStorage();
            });
        }
        
        const chibiToggle = document.querySelector('[data-toggle-chibi]');
        if (chibiToggle) {
            chibiToggle.addEventListener('click', () => {
                this.formData.is_chibi = !this.formData.is_chibi;
                this.updateUI();
                this.saveToStorage();
            });
        }
    }
    
    /**
     * UI全体の更新
     * ステップタイトル、プログレスバー、ステップ表示/非表示、ナビゲーションボタン、
     * hidden inputs、選択肢/モデルのアクティブ状態、トグルボタン、確認画面値、トークンコストを更新
     */
    updateUI() {
        // ステップタイトル更新
        const stepTitles = {
            1: 'ステップ 1: どんなアバターがいい？',
            2: 'ステップ 2: 見た目を選ぼう',
            3: 'ステップ 3: 性格を選ぼう',
            4: 'ステップ 4: 画風を選ぼう',
            5: 'ステップ 5: 確認しよう',
        };
        
        const titleEl = document.querySelector('[data-step-title]');
        if (titleEl) {
            titleEl.textContent = stepTitles[this.currentStep] || '';
        }
        
        // プログレスバー更新
        this.updateProgressBar();
        
        // ステップ表示/非表示
        document.querySelectorAll('[data-wizard-step]').forEach(step => {
            const stepNumber = parseInt(step.dataset.wizardStep);
            if (stepNumber === this.currentStep) {
                step.classList.remove('hidden');
            } else {
                step.classList.add('hidden');
            }
        });
        
        // ナビゲーションボタン表示/非表示
        const prevBtn = document.querySelector('[data-prev-step]');
        if (prevBtn) {
            if (this.currentStep > 1) {
                prevBtn.classList.remove('hidden');
            } else {
                prevBtn.classList.add('hidden');
            }
        }
        
        const nextBtnContainer = document.querySelector('[data-next-btn-container]');
        if (nextBtnContainer) {
            if (this.currentStep < 5) {
                nextBtnContainer.classList.remove('hidden');
            } else {
                nextBtnContainer.classList.add('hidden');
            }
        }
        
        const finalBtnContainer = document.querySelector('[data-final-btn-container]');
        if (finalBtnContainer) {
            if (this.currentStep === 5) {
                finalBtnContainer.classList.remove('hidden');
            } else {
                finalBtnContainer.classList.add('hidden');
            }
        }
        
        // Hidden inputs更新
        Object.keys(this.formData).forEach(key => {
            const input = document.querySelector(`input[name="${key}"]`);
            if (input) {
                if (key === 'is_transparent' || key === 'is_chibi') {
                    input.value = this.formData[key] ? '1' : '0';
                } else {
                    input.value = this.formData[key];
                }
            }
        });
        
        // 選択肢のアクティブ状態更新
        document.querySelectorAll('[data-select-option]').forEach(btn => {
            const field = btn.dataset.selectOption;
            const value = btn.dataset.value;
            if (this.formData[field] === value) {
                btn.classList.add('selection-card-active');
            } else {
                btn.classList.remove('selection-card-active');
            }
        });
        
        // モデル選択のアクティブ状態更新
        document.querySelectorAll('[data-select-model]').forEach(btn => {
            const modelKey = btn.dataset.selectModel;
            if (this.formData.draw_model_version === modelKey) {
                btn.classList.add('model-card-active');
            } else {
                btn.classList.remove('model-card-active');
            }
        });
        
        // トグルボタンの状態更新
        const transparentToggle = document.querySelector('[data-toggle-transparent]');
        if (transparentToggle) {
            if (this.formData.is_transparent) {
                transparentToggle.classList.add('toggle-active');
            } else {
                transparentToggle.classList.remove('toggle-active');
            }
        }
        
        const chibiToggle = document.querySelector('[data-toggle-chibi]');
        if (chibiToggle) {
            if (this.formData.is_chibi) {
                chibiToggle.classList.add('toggle-active');
            } else {
                chibiToggle.classList.remove('toggle-active');
            }
        }
        
        // 確認画面の値表示更新
        this.updateConfirmationValues();
        
        // トークンコスト表示更新
        this.updateTokenCost();
    }
    
    /**
     * プログレスバーの更新
     * 現在のステップに応じて5つの円と区切り線のスタイルを変更
     * （完了: 緑、進行中: オレンジグラデーション、未着手: 灰色）
     */
    updateProgressBar() {
        for (let step = 1; step <= this.totalSteps; step++) {
            const stepCircle = document.querySelector(`[data-progress-step="${step}"]`);
            if (stepCircle) {
                const numberSpan = stepCircle.querySelector('[data-step-number]');
                const dotSpan = stepCircle.querySelector('[data-step-dot]');
                
                // クラスをリセット
                stepCircle.className = 'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300';
                
                if (this.currentStep === step) {
                    stepCircle.classList.add('bg-gradient-to-r', 'from-amber-400', 'to-orange-400', 'text-white', 'shadow-lg', 'scale-110');
                    if (numberSpan) numberSpan.classList.remove('hidden');
                    if (dotSpan) dotSpan.classList.add('hidden');
                } else if (this.currentStep > step) {
                    stepCircle.classList.add('bg-green-500', 'text-white');
                    if (numberSpan) numberSpan.classList.remove('hidden');
                    if (dotSpan) dotSpan.classList.add('hidden');
                } else {
                    stepCircle.classList.add('bg-gray-300', 'text-gray-600');
                    if (numberSpan) numberSpan.classList.add('hidden');
                    if (dotSpan) dotSpan.classList.remove('hidden');
                }
            }
            
            // 区切り線
            const line = document.querySelector(`[data-progress-line="${step}"]`);
            if (line && step < this.totalSteps) {
                line.className = 'h-1 flex-1 mx-2 transition-all duration-300';
                if (this.currentStep > step) {
                    line.classList.add('bg-green-500');
                } else {
                    line.classList.add('bg-gray-300');
                }
            }
        }
    }
    
    /**
     * 確認画面の値表示を更新
     * 12カテゴリ（性別、髪スタイル、髪色、目の色、服装、アクセサリー、体型、
     * 口調、熱意、丁寧さ、ユーモア、描画モデル）の日本語ラベルを表示
     */
    updateConfirmationValues() {
        // 各カテゴリの値を更新
        const updates = {
            'sex': this.getOptionLabel('sex', this.formData.sex),
            'hair_style': this.getOptionLabel('hair_style', this.formData.hair_style),
            'hair_color': this.getOptionLabel('hair_color', this.formData.hair_color),
            'eye_color': this.getOptionLabel('eye_color', this.formData.eye_color),
            'clothing': this.getOptionLabel('clothing', this.formData.clothing),
            'accessory': this.getOptionLabel('accessory', this.formData.accessory),
            'body_type': this.getOptionLabel('body_type', this.formData.body_type),
            'tone': this.getOptionLabel('tone', this.formData.tone),
            'enthusiasm': this.getOptionLabel('enthusiasm', this.formData.enthusiasm),
            'formality': this.getOptionLabel('formality', this.formData.formality),
            'humor': this.getOptionLabel('humor', this.formData.humor),
            'draw_model_version': this.getModelLabel(this.formData.draw_model_version),
            'is_transparent': this.formData.is_transparent ? 'する' : 'しない',
            'is_chibi': this.formData.is_chibi ? 'する' : 'しない',
        };
        console.log('[Avatar Wizard] Confirmation updates:', updates);
        
        Object.keys(updates).forEach(key => {
            const el = document.querySelector(`[data-confirm-${key}]`);
            if (el) {
                el.textContent = updates[key];
            }
        });
    }
    
    /**
     * トークンコスト表示の更新
     * 選択された描画モデルに基づいてトークン消費量を表示
     */
    updateTokenCost() {
        const totalCost = this.getTotalCost();
        const costEl = document.querySelector('[data-token-cost]');
        if (costEl) {
            costEl.textContent = this.formatNumber(totalCost);
        }
    }
    
    /**
     * 次のステップへ進む
     * currentStepを増加し、localStorage保存、UI更新、スクロール移動を実行
     */
    nextStep() {
        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.saveToStorage();
            this.updateUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
    
    /**
     * 前のステップへ戻る
     * currentStepを減少し、localStorage保存、UI更新、スクロール移動を実行
     */
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.saveToStorage();
            this.updateUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
    
    /**
     * 選択肢を選択
     * フォームデータを更新し、ステップ1では自動的に次へ進む（600ms遅延）
     * @param {string} fieldName - フィールド名（sex, hair_color等）
     * @param {string} value - 選択された値
     */
    selectOption(fieldName, value) {
        console.log(`[Avatar Wizard] Selected ${fieldName}:`, value);
        this.formData[fieldName] = value;
        this.saveToStorage();
        this.updateUI();
        
        // ステップ1では自動的に次へ
        if (this.currentStep === 1) {
            setTimeout(() => this.nextStep(), 600);
        }
    }
    
    /**
     * 描画モデルを選択
     * AIモデルを変更し、トークンコスト表示を更新
     * @param {string} modelKey - モデルキー（anything-v4.0等）
     */
    selectModel(modelKey) {
        this.formData.draw_model_version = modelKey;
        this.saveToStorage();
        this.updateUI();
    }
    
    /**
     * 選択肢の日本語ラベルを取得
     * configから該当カテゴリの値に対応するラベルを返す
     * @param {string} category - カテゴリ名（sex, hair_color等）
     * @param {string} value - 値
     * @returns {string} 日本語ラベル
     */
    getOptionLabel(category, value) {
        if (!this.options[category] || !this.options[category][value]) {
            return value || 'なし';
        }
        return this.options[category][value].label;
    }
    
    /**
     * 描画モデルの日本語ラベルを取得
     * @param {string} modelKey - モデルキー
     * @returns {string} モデルのラベル
     */
    getModelLabel(modelKey) {
        return this.models[modelKey] ? this.models[modelKey].label : modelKey;
    }
    
    /**
     * トークン消費量を取得
     * 選択された描画モデルのトークンコストを返す
     * @returns {number} トークン消費量
     */
    getTotalCost() {
        const model = this.models[this.formData.draw_model_version];
        return model ? model.token_cost : 0;
    }
    
    /**
     * 数値を日本語フォーマットに変換
     * @param {number} num - 数値
     * @returns {string} カンマ区切りの文字列（例: 1,000）
     */
    formatNumber(num) {
        return num.toLocaleString('ja-JP');
    }
    
    /**
     * localStorage保存
     * 現在のステップとフォームデータをlocalStorageに保存（ページリロード時の復元用）
     */
    saveToStorage() {
        try {
            localStorage.setItem('avatar_wizard_step', this.currentStep);
            localStorage.setItem('avatar_wizard_data', JSON.stringify(this.formData));
        } catch (error) {
            console.error('[Storage] Save failed:', error);
        }
    }
    
    /**
     * localStorageから復元
     * 保存されたステップとフォームデータを読み込み、ウィザードを復元
     */
    restoreFromStorage() {
        try {
            const savedStep = localStorage.getItem('avatar_wizard_step');
            const savedData = localStorage.getItem('avatar_wizard_data');
            
            if (savedStep) {
                this.currentStep = parseInt(savedStep);
            }
            
            if (savedData) {
                const parsed = JSON.parse(savedData);
                this.formData = { ...this.formData, ...parsed };
            }
        } catch (error) {
            console.error('[Storage] Restore failed:', error);
        }
    }
}

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', () => {
    new AvatarWizardChildController();
});