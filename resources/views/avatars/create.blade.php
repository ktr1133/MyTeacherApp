<x-app-layout>
    @push('styles')
        @vite(['resources/css/avatar/avatar.css'])
    @endpush
    @push('scripts')
        @vite(['resources/js/avatar/avatar-controller.js', 'resources/js/avatar/avatar-form.js'])
    @endpush

    <div class="min-h-screen auth-gradient-bg flex items-center justify-center py-12 px-4">
        <div class="max-w-2xl w-full">
            <div class="text-center mb-8 hero-fade-in">
                <h1 class="text-3xl font-bold gradient-text">
                    あなた専用の教師アバターを作成しましょう
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    外見と性格を選んで、あなたをサポートする先生を誕生させましょう
                </p>
            </div>

            <div class="avatar-card rounded-2xl p-8 hero-fade-in-delay">
                <form 
                    method="POST" 
                    action="{{ route('avatars.store') }}"
                    class="avatar-card rounded-2xl p-6"
                >
                    @csrf

                    {{-- 外見設定 --}}
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            外見の設定
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="avatar-form-group">
                                <label class="avatar-form-label">性別</label>
                                <select name="sex" class="avatar-form-input" required>
                                    @foreach (config('services.sex') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">髪の色</label>
                                <select name="hair_color" class="avatar-form-input" required>
                                    @foreach (config('services.hair_color') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">目の色</label>
                                <select name="eye_color" class="avatar-form-input" required>
                                    @foreach (config('services.eye_color') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">服装</label>
                                <select name="clothing" class="avatar-form-input" required>
                                    @foreach (config('services.clothing') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">アクセサリー</label>
                                <select name="accessory" class="avatar-form-input">
                                    @foreach (config('services.accessory') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">体型</label>
                                <select name="body_type" class="avatar-form-input" required>
                                    @foreach (config('services.body_type') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 性格設定 --}}
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                            </svg>
                            性格の設定
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="avatar-form-group">
                                <label class="avatar-form-label">口調</label>
                                <select name="tone" class="avatar-form-input" required>
                                    @foreach (config('services.tone') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">熱意</label>
                                <select name="enthusiasm" class="avatar-form-input" required>
                                    @foreach (config('services.enthusiasm') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">丁寧さ</label>
                                <select name="formality" class="avatar-form-input" required>
                                    @foreach (config('services.formality') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">ユーモア</label>
                                <select name="humor" class="avatar-form-input" required>
                                    @foreach (config('services.humor') as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 描画モデル --}}
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <div class="model-icon-gradient w-6 h-6 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            描画モデルの選択
                        </h2>
                        
                        <div class="avatar-form-group">
                            <label class="avatar-form-label flex items-center gap-2">
                                <svg class="hidden md:block w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                                イラストスタイル
                            </label>
                            <select id="draw-model-version" name="draw_model_version" class="avatar-form-input model-select" required>
                                @foreach (config('services.draw_model_versions') as $key => $value)
                                    <option value="{{ $key }}">
                                        {{ $key }} - 
                                        @if($key === 'anything-v4.0')
                                            線の細いタッチで描画
                                        @elseif($key === 'animagine-xl-3.1')
                                            豊かな色彩のイラスト
                                        @elseif($key === 'stable-diffusion-3.5-medium')
                                            25億のパラメータで高品質描画
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 背景透過フラグ --}}
                        <div class="mb-8">
                            <label class="avatar-form-label flex items-center gap-2">
                                <input type="checkbox" name="is_transparent" class="avatar-form-checkbox">
                                背景を透過する
                            </label>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                アバター画像の背景を透明にします。<br>
                                一部のモデルでは背景透過がサポートされていない場合があります。<br>
                                背景透過にはトークンが使用されます。
                            </p>
                        </div>

                        {{-- ちびキャラフラグ --}}
                        <div class="mb-8">
                            <label class="avatar-form-label flex items-center gap-2">
                                <input type="checkbox" name="is_chibi" class="avatar-form-checkbox">
                                ちびキャラにする
                            </label>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                デフォルメされた可愛らしいちびキャラクターを生成します。<br>
                                ちびキャラの場合、バストアップ画像は生成されず、すべて全身の画像になります。
                            </p>
                        </div>

                        {{-- モデル情報ヒント --}}
                        <div class="mt-4 p-4 model-info-card rounded-lg">
                            <div class="flex items-start gap-3">
                                <div class="hidden md:flex model-info-icon w-10 h-10 rounded-lg items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">描画モデルについて</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        描画モデルによって、アバターのイラストタッチが変わります。<br>
                                        モデルによって消費するトークンは異なります。<br>
                                        お好みのスタイルをお選びください。今後、新しいモデルが追加される予定です。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- トークン消費の注意 --}}
                    <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">トークン消費について</p>
                                <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">アバター作成には <strong id="token-amount">{{ number_format(config('services.estimated_token_usages')['anything-v4.0']) }}</strong><strong>トークン</strong> が必要です。</p>
                            </div>
                        </div>
                    </div>

                    {{-- 送信ボタン --}}
                    <div class="flex justify-center gap-2 min-[377px]:gap-4">
                        <a href="{{ route('dashboard') }}" class="avatar-btn-secondary px-4 min-[377px]:px-6 min-[618px]:px-8 py-2.5 min-[377px]:py-3 text-sm min-[377px]:text-base min-[618px]:text-lg">
                            スキップ
                        </a>
                        <button type="submit" class="avatar-btn-primary px-4 min-[377px]:px-6 min-[618px]:px-8 py-2.5 min-[377px]:py-3 text-sm min-[377px]:text-base min-[618px]:text-lg">
                            <svg class="w-4 h-4 min-[377px]:w-5 min-[377px]:h-5 min-[618px]:w-6 min-[618px]:h-6 mr-1 min-[377px]:mr-1.5 min-[618px]:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="hidden min-[618px]:inline">アバターを作成する</span>
                            <span class="inline min-[618px]:hidden">作成</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>