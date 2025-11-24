{{-- filepath: /home/ktr/mtdev/laravel/resources/views/avatars/create-child.blade.php --}}

<x-app-layout>
    @push('styles')
        @vite(['resources/css/avatar/avatar.css', 'resources/css/avatar/avatar-wizard-child.css'])
    @endpush

    {{-- Alpine.jsのデータをインラインで定義（PHPのデータを安全に埋め込む） --}}
    <script>
        // 設定データをグローバル変数として定義
        window.avatarOptions = {!! json_encode(config('avatar-options'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!};
        window.avatarDefaults = {!! json_encode(config('avatar-options.defaults'), JSON_UNESCAPED_UNICODE) !!};
    </script>

    <div x-data="avatarWizardChild()" class="min-h-screen dashboard-gradient-bg child-theme flex items-center justify-center py-8 px-4">
        <div class="max-w-4xl w-full">
            {{-- プログレスバー --}}
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <template x-for="step in totalSteps" :key="step">
                        <div class="flex-1 flex items-center">
                            {{-- ステップ円 --}}
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                                :class="{
                                    'bg-gradient-to-r from-amber-400 to-orange-400 text-white shadow-lg scale-110': currentStep === step,
                                    'bg-green-500 text-white': currentStep > step,
                                    'bg-gray-300 text-gray-600': currentStep < step
                                }"
                            >
                                <span x-show="currentStep >= step" x-text="step"></span>
                                <span x-show="currentStep < step">●</span>
                            </div>
                            
                            {{-- 区切り線 --}}
                            <div 
                                x-show="step < totalSteps" 
                                class="h-1 flex-1 mx-2 transition-all duration-300"
                                :class="{
                                    'bg-green-500': currentStep > step,
                                    'bg-gray-300': currentStep <= step
                                }"
                            ></div>
                        </div>
                    </template>
                </div>
                <p class="text-center text-lg font-bold text-amber-900 dark:text-amber-100" x-text="stepTitle"></p>
            </div>

            {{-- フォーム --}}
            <form method="POST" action="{{ route('avatars.store') }}" id="avatar-wizard-form">
                @csrf
                
                {{-- Hidden Inputs（選択された値を保存） --}}
                <input type="hidden" name="sex" x-model="formData.sex">
                <input type="hidden" name="hair_style" x-model="formData.hair_style">
                <input type="hidden" name="hair_color" x-model="formData.hair_color">
                <input type="hidden" name="eye_color" x-model="formData.eye_color">
                <input type="hidden" name="clothing" x-model="formData.clothing">
                <input type="hidden" name="accessory" x-model="formData.accessory">
                <input type="hidden" name="body_type" x-model="formData.body_type">
                <input type="hidden" name="tone" x-model="formData.tone">
                <input type="hidden" name="enthusiasm" x-model="formData.enthusiasm">
                <input type="hidden" name="formality" x-model="formData.formality">
                <input type="hidden" name="humor" x-model="formData.humor">
                <input type="hidden" name="draw_model_version" x-model="formData.draw_model_version">
                <input type="hidden" name="is_transparent" x-model="formData.is_transparent ? '1' : '0'">
                <input type="hidden" name="is_chibi" x-model="formData.is_chibi ? '1' : '0'">

                {{-- ステップ1: 性別 --}}
                <div x-show="currentStep === 1" x-transition class="wizard-step-child">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">👤 どんなアバターがいい？</h2>
                        
                        <div class="selection-grid-child">
                            @foreach(config('avatar-options.sex') as $key => $option)
                                <div 
                                    @click="selectOption('sex', '{{ $key }}')"
                                    class="selection-card-child"
                                    :class="{ 'selection-card-active': formData.sex === '{{ $key }}' }"
                                >
                                    @if($option['image'])
                                        <img src="{{ $option['image'] }}" alt="{{ $option['label'] }}" class="card-icon-image">
                                    @else
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                    @endif
                                    <div class="card-label-child">{{ $option['label'] }}</div>
                                    <div class="card-checkmark-child">✓</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ステップ2: 見た目 --}}
                <div x-show="currentStep === 2" x-transition class="wizard-step-child">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">見た目を選ぼう</h2>

                        {{-- 髪型 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">髪型</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.hair_style') as $key => $option)
                                    <div 
                                        @click="selectOption('hair_style', '{{ $key }}')"
                                        class="selection-card-child color-card-child"
                                        :class="{ 'selection-card-active': formData.hair_style === '{{ $key }}' }"
                                    >
                                        @if($option['image'])
                                            <img src="{{ $option['image'] }}" alt="{{ $option['label'] }}" class="card-icon-image">
                                        @endif
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 髪の色 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">髪の色</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.hair_color') as $key => $option)
                                    <div 
                                        @click="selectOption('hair_color', '{{ $key }}')"
                                        class="selection-card-child color-card-child"
                                        :class="{ 'selection-card-active': formData.hair_color === '{{ $key }}' }"
                                        style="--card-color: {{ $option['color'] }};"
                                    >
                                        <div class="color-circle-child"></div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 目の色 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">目の色</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.eye_color') as $key => $option)
                                    <div 
                                        @click="selectOption('eye_color', '{{ $key }}')"
                                        class="selection-card-child color-card-child"
                                        :class="{ 'selection-card-active': formData.eye_color === '{{ $key }}' }"
                                        style="--card-color: {{ $option['color'] }};"
                                    >
                                        <div class="color-circle-child"></div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 服装 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">服装</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.clothing') as $key => $option)
                                    <div 
                                        @click="selectOption('clothing', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.clothing === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- アクセサリー --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">アクセサリー</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.accessory') as $key => $option)
                                    <div 
                                        @click="selectOption('accessory', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.accessory === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 体型 --}}
                        <div>
                            <h3 class="wizard-subtitle-child">体型</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.body_type') as $key => $option)
                                    <div 
                                        @click="selectOption('body_type', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.body_type === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ステップ3: 性格 --}}
                <div x-show="currentStep === 3" x-transition class="wizard-step-child">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">😊 性格を選ぼう</h2>
                        
                        {{-- 口調 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">口調</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.tone') as $key => $option)
                                    <div 
                                        @click="selectOption('tone', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.tone === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 熱意 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">熱意</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.enthusiasm') as $key => $option)
                                    <div 
                                        @click="selectOption('enthusiasm', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.enthusiasm === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- 丁寧さ --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">ていねいさ</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.formality') as $key => $option)
                                    <div 
                                        @click="selectOption('formality', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.formality === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ユーモア --}}
                        <div>
                            <h3 class="wizard-subtitle-child">ユーモア</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.humor') as $key => $option)
                                    <div 
                                        @click="selectOption('humor', '{{ $key }}')"
                                        class="selection-card-child"
                                        :class="{ 'selection-card-active': formData.humor === '{{ $key }}' }"
                                    >
                                        <div class="card-icon-emoji">{{ $option['emoji'] }}</div>
                                        <div class="card-label-child">{{ $option['label'] }}</div>
                                        <div class="card-checkmark-child">✓</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ステップ4: 画風 --}}
                <div x-show="currentStep === 4" x-transition class="wizard-step-child">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">🎨 画風を選ぼう</h2>
                        
                        <div class="model-grid-child">
                            @foreach(config('avatar-options.draw_models') as $key => $model)
                                <div 
                                    @click="selectModel('{{ $key }}')"
                                    class="model-card-child"
                                    :class="{ 'model-card-active': formData.draw_model_version === '{{ $key }}' }"
                                >
                                    <img src="{{ $model['sample_image'] }}" alt="{{ $model['label'] }}" class="model-sample-image">
                                    <div class="model-info">
                                        <h4 class="model-label-child">{{ $model['label'] }}</h4>
                                        <p class="model-description-child">{{ $model['description'] }}</p>
                                        <div class="model-features-child">
                                            @foreach($model['features'] as $feature)
                                                <span class="feature-tag-child">{{ $feature }}</span>
                                            @endforeach
                                        </div>
                                        <div class="model-cost-child">
                                            <span class="coin-icon">🪙</span>
                                            <span class="cost-amount">{{ number_format($model['token_cost']) }}</span>
                                            <span class="cost-label">コイン</span>
                                        </div>
                                    </div>
                                    <div class="card-checkmark-child">✓</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- 背景透過トグル --}}
                        <div class="mt-8 p-6 bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-2xl border-3 border-amber-300">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">✨</span>
                                    <div>
                                        <p class="text-lg font-bold text-amber-900 dark:text-amber-100">背景を透明にする</p>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">アバターのまわりが透けて見えるよ</p>
                                    </div>
                                </div>
                                <button 
                                    type="button"
                                    @click="formData.is_transparent = !formData.is_transparent"
                                    class="toggle-switch-child"
                                    :class="{ 'toggle-active': formData.is_transparent }"
                                >
                                    <span class="toggle-slider-child"></span>
                                </button>
                            </label>
                        </div>

                        {{-- ちびキャラトグル --}}
                        <div class="mt-8 p-6 bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-2xl border-3 border-amber-300">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">✨</span>
                                    <div>
                                        <p class="text-lg font-bold text-amber-900 dark:text-amber-100">ちびキャラにする</p>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">アバターが小さくかわいくなるよ</p>
                                    </div>
                                </div>
                                <button 
                                    type="button"
                                    @click="formData.is_chibi = !formData.is_chibi"
                                    class="toggle-switch-child"
                                    :class="{ 'toggle-active': formData.is_chibi }"
                                >
                                    <span class="toggle-slider-child"></span>
                                </button>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ステップ5: 確認画面 --}}
                <div x-show="currentStep === 5" x-transition class="wizard-step-child">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">✅ これでいいかな？</h2>
                        
                        <div class="confirmation-grid-child">
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">先生のタイプ</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('sex', formData.sex)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">髪型</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('hair_style', formData.hair_style)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">髪の色</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('hair_color', formData.hair_color)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">目の色</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('eye_color', formData.eye_color)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">服装</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('clothing', formData.clothing)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">アクセサリー</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('accessory', formData.accessory)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">体型</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('body_type', formData.body_type)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">口調</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('tone', formData.tone)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">熱意</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('enthusiasm', formData.enthusiasm)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">ていねいさ</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('formality', formData.formality)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">ユーモア</p>
                                <p class="confirmation-value-child" x-text="getOptionLabel('humor', formData.humor)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">画風</p>
                                <p class="confirmation-value-child" x-text="getModelLabel(formData.draw_model_version)"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">背景透過</p>
                                <p class="confirmation-value-child" x-text="formData.is_transparent ? 'する' : 'しない'"></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">ちびキャラ</p>
                                <p class="confirmation-value-child" x-text="formData.is_chibi ? 'する' : 'しない'"></p>
                            </div>
                        </div>

                        {{-- コイン消費の注意 --}}
                        <div class="mt-8 p-6 bg-gradient-to-r from-pink-100 to-purple-100 dark:from-pink-900/30 dark:to-purple-900/30 rounded-2xl border-3 border-pink-300">
                            <div class="flex items-start gap-4">
                                <span class="text-4xl">🪙</span>
                                <div>
                                    <p class="text-lg font-bold text-pink-900 dark:text-pink-100 mb-2">コインを使うよ</p>
                                    <p class="text-sm text-pink-700 dark:text-pink-300">
                                        アバターをつくるには 
                                        <strong class="text-2xl" x-text="formatNumber(getTotalCost())"></strong> 
                                        <strong class="text-lg">コイン</strong> が必要だよ
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ナビゲーションボタン --}}
                <div class="flex justify-between mt-8">
                    <button 
                        type="button"
                        @click="prevStep()"
                        x-show="currentStep > 1"
                        class="wizard-btn-secondary-child"
                    >
                        ← もどる
                    </button>
                    
                    <div x-show="currentStep < 5">
                        <button 
                            type="button"
                            @click="nextStep()"
                            class="wizard-btn-primary-child"
                        >
                            つぎへ →
                        </button>
                    </div>
                    
                    <div x-show="currentStep === 5" class="flex gap-4">
                        <a href="{{ route('dashboard') }}" class="wizard-btn-secondary-child">
                            スキップ
                        </a>
                        <button 
                            type="submit"
                            class="wizard-btn-create-child"
                        >
                            ✨ アバターをつくる
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Alpine.jsコンポーネント定義（インライン） --}}
    @push('scripts')
        <script>
            function avatarWizardChild() {
                return {
                    // ステップ管理
                    currentStep: 1,
                    totalSteps: 5,
                    
                    // フォームデータ（デフォルト値を設定）
                    formData: window.avatarDefaults || {
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
                    },
                    
                    // 設定オプション
                    options: window.avatarOptions || {},
                    
                    // AIモデル情報
                    models: (window.avatarOptions && window.avatarOptions.draw_models) || {},
                    
                    init() {
                        console.log('[Avatar Wizard Child] Initialized');
                        this.restoreFromStorage();
                        
                        window.addEventListener('beforeunload', (e) => {
                            if (this.currentStep > 1 && this.currentStep < 5) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                    },
                    
                    get stepTitle() {
                        const titles = {
                            1: 'ステップ 1: どんなアバターがいい？',
                            2: 'ステップ 2: 見た目を選ぼう',
                            3: 'ステップ 3: 性格を選ぼう',
                            4: 'ステップ 4: 画風を選ぼう',
                            5: 'ステップ 5: 確認しよう',
                        };
                        return titles[this.currentStep] || '';
                    },
                    
                    nextStep() {
                        if (this.currentStep < this.totalSteps) {
                            this.currentStep++;
                            this.saveToStorage();
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    },
                    
                    prevStep() {
                        if (this.currentStep > 1) {
                            this.currentStep--;
                            this.saveToStorage();
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    },
                    
                    selectOption(fieldName, value) {
                        this.formData[fieldName] = value;
                        this.saveToStorage();
                        
                        if (this.currentStep === 1) {
                            setTimeout(() => this.nextStep(), 600);
                        }
                    },
                    
                    selectModel(modelKey) {
                        this.formData.draw_model_version = modelKey;
                        this.saveToStorage();
                    },
                    
                    getOptionLabel(category, value) {
                        if (!this.options[category] || !this.options[category][value]) {
                            return value || 'なし';
                        }
                        return this.options[category][value].label;
                    },
                    
                    getModelLabel(modelKey) {
                        return this.models[modelKey] ? this.models[modelKey].label : modelKey;
                    },
                    
                    getTotalCost() {
                        const model = this.models[this.formData.draw_model_version];
                        return model ? model.token_cost : 0;
                    },
                    
                    formatNumber(num) {
                        return num.toLocaleString('ja-JP');
                    },
                    
                    saveToStorage() {
                        try {
                            localStorage.setItem('avatar_wizard_step', this.currentStep);
                            localStorage.setItem('avatar_wizard_data', JSON.stringify(this.formData));
                        } catch (error) {
                            console.error('[Storage] Save failed:', error);
                        }
                    },
                    
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
                };
            }
        </script>
    @endpush
</x-app-layout>