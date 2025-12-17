{{-- filepath: /home/ktr/mtdev/laravel/resources/views/avatars/create-child.blade.php --}}

<x-app-layout>
    @push('styles')
        @vite(['resources/css/avatar/avatar.css', 'resources/css/avatar/avatar-wizard-child.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/avatar/avatar-wizard-child.js'])
    @endpush

    {{-- 設定データをグローバル変数として定義 --}}
    <script>
        // 設定データをグローバル変数として定義
        window.avatarOptions = {!! json_encode(config('avatar-options'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!};
        window.avatarDefaults = {!! json_encode(config('avatar-options.defaults'), JSON_UNESCAPED_UNICODE) !!};
    </script>

    <div class="min-h-screen dashboard-gradient-bg child-theme flex items-center justify-center py-8 px-4">
        <div class="max-w-4xl w-full">
            {{-- プログレスバー --}}
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    @for($step = 1; $step <= 5; $step++)
                        <div class="flex-1 flex items-center">
                            {{-- ステップ円 --}}
                            <div 
                                data-progress-step="{{ $step }}"
                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                            >
                                <span data-step-number class="hidden">{{ $step }}</span>
                                <span data-step-dot>●</span>
                            </div>
                            
                            {{-- 区切り線 --}}
                            @if($step < 5)
                                <div 
                                    data-progress-line="{{ $step }}"
                                    class="h-1 flex-1 mx-2 transition-all duration-300"
                                ></div>
                            @endif
                        </div>
                    @endfor
                </div>
                <p data-step-title class="text-center text-lg font-bold text-amber-900 dark:text-amber-100"></p>
            </div>

            {{-- フォーム --}}
            <form method="POST" action="{{ route('avatars.store') }}" id="avatar-wizard-form">
                @csrf
                
                {{-- Hidden Inputs（選択された値を保存） --}}
                <input type="hidden" name="sex" value="">
                <input type="hidden" name="hair_style" value="">
                <input type="hidden" name="hair_color" value="">
                <input type="hidden" name="eye_color" value="">
                <input type="hidden" name="clothing" value="">
                <input type="hidden" name="accessory" value="">
                <input type="hidden" name="body_type" value="">
                <input type="hidden" name="tone" value="">
                <input type="hidden" name="enthusiasm" value="">
                <input type="hidden" name="formality" value="">
                <input type="hidden" name="humor" value="">
                <input type="hidden" name="draw_model_version" value="">
                <input type="hidden" name="is_transparent" value="0">
                <input type="hidden" name="is_chibi" value="0">

                {{-- ステップ1: 性別 --}}
                <div data-wizard-step="1" class="wizard-step-child hidden">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">👤 どんなアバターがいい？</h2>
                        
                        <div class="selection-grid-child">
                            @foreach(config('avatar-options.sex') as $key => $option)
                                <div 
                                    data-select-option="sex"
                                    data-value="{{ $key }}"
                                    class="selection-card-child"
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
                <div data-wizard-step="2" class="wizard-step-child hidden">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">見た目を選ぼう</h2>

                        {{-- 髪型 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">髪型</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.hair_style') as $key => $option)
                                    <div 
                                        data-select-option="hair_style"
                                        data-value="{{ $key }}"
                                        class="selection-card-child color-card-child"
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
                                        data-select-option="hair_color"
                                        data-value="{{ $key }}"
                                        class="selection-card-child color-card-child"
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
                                        data-select-option="eye_color"
                                        data-value="{{ $key }}"
                                        class="selection-card-child color-card-child"
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
                                        data-select-option="clothing"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                                        data-select-option="accessory"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                                        data-select-option="body_type"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                <div data-wizard-step="3" class="wizard-step-child hidden">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">😊 性格を選ぼう</h2>
                        
                        {{-- 口調 --}}
                        <div class="mb-8">
                            <h3 class="wizard-subtitle-child">口調</h3>
                            <div class="selection-grid-child">
                                @foreach(config('avatar-options.tone') as $key => $option)
                                    <div 
                                        data-select-option="tone"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                                        data-select-option="enthusiasm"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                                        data-select-option="formality"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                                        data-select-option="humor"
                                        data-value="{{ $key }}"
                                        class="selection-card-child"
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
                <div data-wizard-step="4" class="wizard-step-child hidden">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">🎨 画風を選ぼう</h2>
                        
                        <div class="model-grid-child">
                            @foreach(config('avatar-options.draw_models') as $key => $model)
                                <div 
                                    data-select-model="{{ $key }}"
                                    class="model-card-child"
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
                                    data-toggle-transparent
                                    class="toggle-switch-child"
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
                                    data-toggle-chibi
                                    class="toggle-switch-child"
                                >
                                    <span class="toggle-slider-child"></span>
                                </button>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ステップ5: 確認画面 --}}
                <div data-wizard-step="5" class="wizard-step-child hidden">
                    <div class="wizard-card-child">
                        <h2 class="wizard-title-child">✅ これでいいかな？</h2>
                        
                        <div class="confirmation-grid-child">
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">先生のタイプ</p>
                                <p class="confirmation-value-child" data-confirm-sex></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">髪型</p>
                                <p class="confirmation-value-child" data-confirm-hair_style></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">髪の色</p>
                                <p class="confirmation-value-child" data-confirm-hair_color></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">目の色</p>
                                <p class="confirmation-value-child" data-confirm-eye_color></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">服装</p>
                                <p class="confirmation-value-child" data-confirm-clothing></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">アクセサリー</p>
                                <p class="confirmation-value-child" data-confirm-accessory></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">体型</p>
                                <p class="confirmation-value-child" data-confirm-body_type></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">口調</p>
                                <p class="confirmation-value-child" data-confirm-tone></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">熱意</p>
                                <p class="confirmation-value-child" data-confirm-enthusiasm></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">ていねいさ</p>
                                <p class="confirmation-value-child" data-confirm-formality></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">ユーモア</p>
                                <p class="confirmation-value-child" data-confirm-humor></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">画風</p>
                                <p class="confirmation-value-child" data-confirm-draw_model_version></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">背景透過</p>
                                <p class="confirmation-value-child" data-confirm-is_transparent></p>
                            </div>
                            <div class="confirmation-item-child">
                                <p class="confirmation-label-child">ちびキャラ</p>
                                <p class="confirmation-value-child" data-confirm-is_chibi></p>
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
                                        <strong class="text-2xl" data-token-cost></strong> 
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
                        data-prev-step
                        class="wizard-btn-secondary-child hidden"
                    >
                        ← もどる
                    </button>
                    
                    <div data-next-btn-container class="hidden">
                        <button 
                            type="button"
                            data-next-step
                            class="wizard-btn-primary-child"
                        >
                            つぎへ →
                        </button>
                    </div>
                    
                    <div data-final-btn-container class="flex gap-4 hidden">
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
</x-app-layout>