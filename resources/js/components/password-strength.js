/**
 * パスワード強度チェッカー
 * 
 * 使用方法:
 * import { PasswordStrengthChecker } from '@/components/password-strength';
 * 
 * const checker = new PasswordStrengthChecker('#password-input', '#strength-meter');
 */

export class PasswordStrengthChecker {
    /**
     * コンストラクタ
     * @param {string} inputSelector - パスワード入力フィールドのセレクタ
     * @param {string} meterSelector - 強度メーターのセレクタ
     */
    constructor(inputSelector, meterSelector) {
        this.inputSelector = inputSelector;
        this.meterSelector = meterSelector;
        this.input = document.querySelector(inputSelector);
        this.meter = document.querySelector(meterSelector);
        
        if (!this.input) {
            console.error('Password strength checker: input element not found:', inputSelector);
            return;
        }
        
        if (!this.meter) {
            console.error('Password strength checker: meter element not found:', meterSelector);
            return;
        }
        
        console.log('[PasswordStrengthChecker] Initialized successfully', {
            input: inputSelector,
            meter: meterSelector
        });
        
        this.init();
    }
    
    /**
     * 初期化
     */
    init() {
        console.log('[PasswordStrengthChecker] Setting up input event listener');
        
        this.input.addEventListener('input', () => {
            const password = this.input.value;
            console.log('[PasswordStrengthChecker] Password changed, length:', password.length);
            const result = this.checkStrength(password);
            console.log('[PasswordStrengthChecker] Check result:', result);
            this.updateUI(result);
        });
    }
    
    /**
     * パスワード強度をチェック
     * @param {string} password - チェックするパスワード
     * @returns {Object} {strength: number, score: number, message: string, errors: Array}
     */
    checkStrength(password) {
        const errors = [];
        let score = 0;
        
        // 1. 最小文字数チェック（8文字以上）
        if (password.length < 8) {
            errors.push('8文字以上必要です');
        } else {
            score += 20;
            // 追加ポイント: 長いほど強い
            if (password.length >= 12) score += 10;
            if (password.length >= 16) score += 10;
        }
        
        // 2. 英字が含まれているかチェック
        if (!/[a-zA-Z]/.test(password)) {
            errors.push('英字を含める必要があります');
        } else {
            score += 20;
        }
        
        // 3. 大文字と小文字の両方が含まれているかチェック
        const hasLowerCase = /[a-z]/.test(password);
        const hasUpperCase = /[A-Z]/.test(password);
        if (!hasLowerCase || !hasUpperCase) {
            errors.push('大文字と小文字の両方を含める必要があります');
        } else {
            score += 20;
        }
        
        // 4. 数字が含まれているかチェック
        if (!/[0-9]/.test(password)) {
            errors.push('数字を含める必要があります');
        } else {
            score += 20;
        }
        
        // 5. 記号が含まれているかチェック
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
            errors.push('記号を含める必要があります');
        } else {
            score += 20;
        }
        
        // 6. 一般的なパターンのチェック（減点）
        const commonPatterns = [
            /123456/,
            /password/i,
            /qwerty/i,
            /abc123/i,
            /111111/,
            /(\w)\1{2,}/, // 同じ文字の3連続以上
        ];
        
        for (const pattern of commonPatterns) {
            if (pattern.test(password)) {
                score -= 10;
                errors.push('一般的すぎるパターンが含まれています');
                break;
            }
        }
        
        // スコアを0-100の範囲に制限
        score = Math.max(0, Math.min(100, score));
        
        // 強度レベルの判定
        let strength, message;
        if (score < 40) {
            strength = 'weak';
            message = '弱い';
        } else if (score < 70) {
            strength = 'medium';
            message = '普通';
        } else {
            strength = 'strong';
            message = '強い';
        }
        
        return {
            strength,
            score,
            message,
            errors,
            isValid: errors.length === 0
        };
    }
    
    /**
     * UIを更新
     * @param {Object} result - checkStrength() の戻り値
     */
    updateUI(result) {
        const { strength, score, message, errors } = result;
        
        console.log('[PasswordStrengthChecker] Updating UI:', { strength, score, message });
        
        // メーター要素の構造:
        // <div class="password-strength-meter">
        //   <div class="strength-bar-container">
        //     <div class="strength-bar"></div>
        //   </div>
        //   <div class="strength-text"></div>
        //   <div class="strength-errors"></div>
        // </div>
        
        const bar = this.meter.querySelector('.strength-bar');
        const text = this.meter.querySelector('.strength-text');
        const errorContainer = this.meter.querySelector('.strength-errors');
        
        console.log('[PasswordStrengthChecker] UI elements:', { 
            bar: !!bar, 
            text: !!text, 
            errorContainer: !!errorContainer 
        });
        
        if (!bar || !text) {
            console.error('Password strength meter: bar or text element not found');
            return;
        }
        
        // バーの幅とクラスを更新
        console.log('[PasswordStrengthChecker] Setting bar width to:', score + '%');
        bar.style.width = `${score}%`;
        bar.className = 'strength-bar';
        bar.classList.add(`strength-${strength}`);
        
        // テキストを更新
        text.textContent = message;
        text.className = 'strength-text';
        text.classList.add(`text-${strength}`);
        
        // エラーメッセージを表示
        if (errorContainer) {
            if (errors.length > 0) {
                errorContainer.innerHTML = errors.map(err => 
                    `<div class="strength-error">• ${err}</div>`
                ).join('');
                errorContainer.style.display = 'block';
            } else {
                errorContainer.style.display = 'none';
            }
        }
    }
    
    /**
     * 現在のパスワードが有効かチェック
     * @returns {boolean}
     */
    isValid() {
        const password = this.input.value;
        const result = this.checkStrength(password);
        return result.isValid;
    }
    
    /**
     * 検証結果を取得
     * @returns {Object}
     */
    getValidationResult() {
        const password = this.input.value;
        return this.checkStrength(password);
    }
}

/**
 * スタンドアロン関数: パスワード強度をチェック
 * @param {string} password - チェックするパスワード
 * @returns {Object} {strength: string, score: number, message: string, errors: Array, isValid: boolean}
 */
export function validatePassword(password) {
    const checker = new PasswordStrengthChecker('body', 'body'); // ダミー
    return checker.checkStrength(password);
}
