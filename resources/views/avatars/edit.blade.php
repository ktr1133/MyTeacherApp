<x-app-layout>
    @push('styles')
        @vite(['resources/css/avatar/avatar.css', 'resources/css/dashboard.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/avatar/avatar-edit.js', 'resources/js/avatar/avatar-form.js'])
    @endpush

    <div x-data="avatarEdit()" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-screen max-h-screen {{ $isChildTheme ? 'dashboard-gradient-bg child-theme' : 'auth-gradient-bg' }} relative overflow-hidden">
        
        {{-- 背景装飾（大人用のみ） --}}
        @if(!$isChildTheme)
            <div class="absolute inset-0 pointer-events-none z-0">
                <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl avatar-floating-decoration"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl avatar-floating-decoration" style="animation-delay: -10s;"></div>
            </div>
        @endif

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden relative z-10">

            {{-- ヘッダー --}}
            <header class="shrink-0 border-b border-gray-200/50 dark:border-gray-700/50 {{ $isChildTheme ? 'dashboard-header-blur' : 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm' }} shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            @click="toggleSidebar()"
                            aria-label="メニューを開く">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="{{ $isChildTheme ? 'avatar-header-icon' : '' }} w-10 h-10 rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                @if (!$isChildTheme)
                                    <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">
                                        教師アバター設定
                                    </h1>
                                    <p class="avatar-header-subtitle hidden sm:block text-xs text-gray-600 dark:text-gray-400">アバターの外見と性格を編集</p>
                                @else
                                    <h1 class="avatar-header-title text-base lg:text-lg font-bold">
                                        サポートアバター設定
                                    </h1>
                                @endif
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('dashboard') }}"
                       class="{{ $isChildTheme ? 'avatar-back-btn' : 'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 hover:bg-white/80 dark:hover:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700 transition backdrop-blur-sm' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">
                            @if (!$isChildTheme)
                                タスクリストへ戻る
                            @endif
                        </span>
                    </a>
                </div>
            </header>

            {{-- メインコンテンツエリア --}}
            <main class="flex-1 overflow-y-auto px-3 lg:px-6 py-3 lg:py-6">
                <div class="max-w-6xl mx-auto">
                    {{-- 縦並びレイアウト（1カラム） --}}
                    <div class="space-y-4 lg:space-y-6">
                        {{-- 現在のアバター表示（サムネイル版） --}}
                        <div>
                            <div class="avatar-card rounded-2xl p-4 lg:p-6 hero-fade-in">
                                <h2 class="text-base lg:text-lg font-bold text-gray-900 dark:text-white mb-4">
                                    アバター画像
                                </h2>
                                
                                @if($avatar->generation_status === 'completed')
                                    {{-- 表情スライダー（サムネイル版） --}}
                                    <div 
                                        x-data="expressionSlider({{ json_encode($expressionImages) }})"
                                    >
                                        {{-- メイン画像表示エリア（動的背景） --}}
                                        <div 
                                            class="relative overflow-hidden rounded-lg mb-3 avatar-slider-bg-container"
                                            :style="currentImageUrl ? `background-image: url('${currentImageUrl}')` : ''"
                                        >
                                            {{-- 背景オーバーレイ --}}
                                            <div class="avatar-slider-bg-overlay"></div>

                                            {{-- 画像スライダー --}}
                                            <div 
                                                class="relative touch-pan-y z-10"
                                                @touchstart="handleTouchStart($event)"
                                                @touchmove="handleTouchMove($event)"
                                                @touchend="handleTouchEnd($event)"
                                            >
                                                <template x-for="(expr, index) in expressions" :key="expr.type">
                                                    <div
                                                        x-show="currentIndex === index"
                                                        x-transition:enter="transition-transform duration-300 ease-out"
                                                        x-transition:enter-start="transform translate-x-full"
                                                        x-transition:enter-end="transform translate-x-0"
                                                        x-transition:leave="transition-transform duration-300 ease-in"
                                                        x-transition:leave-start="transform translate-x-0"
                                                        x-transition:leave-end="transform -translate-x-full"
                                                        class="relative"
                                                    >
                                                        {{-- 表情ラベル（オーバーレイ） --}}
                                                        <div class="absolute top-3 left-3 z-10 {{ $isChildTheme ? 'avatar-expression-label-child' : 'avatar-expression-label' }}">
                                                            <span x-text="expr.label" class="font-bold"></span>
                                                        </div>

                                                        {{-- 画像 --}}
                                                        <template x-if="expr.image">
                                                            <img 
                                                                :src="expr.image.s3_url || expr.image.public_url" 
                                                                :alt="expr.label"
                                                                class="w-full h-auto rounded-lg relative z-10 {{ $isChildTheme ? 'avatar-image' : '' }}"
                                                            />
                                                        </template>

                                                        {{-- 画像がない場合 --}}
                                                        <template x-if="!expr.image">
                                                            <div class="aspect-square flex flex-col items-center justify-center text-center p-6 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 relative z-10">
                                                                <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                </svg>
                                                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">生成中...</p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1" x-text="expr.label + '画像'"></p>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- サムネイルリスト --}}
                                        <div class="grid grid-cols-6 gap-2">
                                            <template x-for="(expr, index) in expressions" :key="'thumb-' + expr.type">
                                                <button
                                                    type="button"
                                                    @click="goToExpression(index)"
                                                    :class="{
                                                        'avatar-thumbnail-active': currentIndex === index,
                                                        'avatar-thumbnail-inactive': currentIndex !== index
                                                    }"
                                                    class="{{ $isChildTheme ? 'avatar-thumbnail-child' : 'avatar-thumbnail' }}"
                                                    :aria-label="expr.label"
                                                >
                                                    {{-- サムネイル画像 --}}
                                                    <template x-if="expr.image">
                                                        <img 
                                                            :src="expr.image.s3_url || expr.image.public_url" 
                                                            :alt="expr.label"
                                                            class="w-full h-full object-cover"
                                                        />
                                                    </template>

                                                    {{-- 画像がない場合 --}}
                                                    <template x-if="!expr.image">
                                                        <div class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                            </svg>
                                                        </div>
                                                    </template>

                                                    {{-- 表情ラベル（サムネイル下部） --}}
                                                    <div class="absolute bottom-0 left-0 right-0 {{ $isChildTheme ? 'avatar-thumbnail-label-child' : 'avatar-thumbnail-label' }}">
                                                        <span x-text="expr.label" class="text-xs font-semibold truncate block px-1"></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                @elseif($avatar->generation_status === 'generating')
                                    <div class="text-center py-8">
                                        <div class="w-16 h-16 mx-auto mb-4 border-4 border-[#59B9C6] border-t-transparent rounded-full animate-spin"></div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">生成中...</p>
                                    </div>
                                @elseif($avatar->generation_status === 'failed')
                                    <div class="text-center py-8">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-sm text-red-600 dark:text-red-400">生成失敗</p>
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">生成待機中</p>
                                    </div>
                                @endif

                                {{-- 表示設定 --}}
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <form method="POST" action="{{ route('avatars.toggle-visibility') }}">
                                        @csrf
                                        <label class="flex items-center justify-between cursor-pointer">
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                @if (!$isChildTheme)
                                                    アバター表示
                                                @else
                                                    アバターを表示する
                                                @endif
                                            </span>
                                            <button 
                                                type="submit"
                                                id="avatar-display-btn"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                                       {{ $avatar->is_visible ? 'bg-[#59B9C6]' : 'bg-gray-300 dark:bg-gray-600' }}"
                                            >
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                                    {{ $avatar->is_visible ? 'translate-x-6' : 'translate-x-1' }}">
                                                </span>
                                            </button>
                                        </label>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- 設定フォーム --}}
                        <div>
                            <div class="avatar-card rounded-2xl p-4 lg:p-8 hero-fade-in-delay">
                                <form 
                                    method="POST" 
                                    action="{{ route('avatars.update', $avatar) }}"
                                    x-data="avatarForm()"
                                    @submit="submitForm($event)"
                                    class="avatar-card rounded-2xl p-4 lg:p-6 hero-fade-in"
                                >
                                    @csrf
                                    @method('PUT')

                                    {{-- 外見設定 --}}
                                    <div class="mb-8">
                                        <h2 class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                            外見の設定
                                        </h2>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">性別</label>
                                                <select name="sex" class="avatar-form-input" required>
                                                    @foreach (config('services.sex') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->sex === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">髪の色</label>
                                                <select name="hair_color" class="avatar-form-input" required>
                                                    @foreach (config('services.hair_color') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->hair_color === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">目の色</label>
                                                <select name="eye_color" class="avatar-form-input" required>
                                                    @foreach (config('services.eye_color') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->eye_color === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">服装</label>
                                                <select name="clothing" class="avatar-form-input" required>
                                                    @foreach (config('services.clothing') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->clothing === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">アクセサリー</label>
                                                <select name="accessory" class="avatar-form-input">
                                                    @foreach (config('services.accessory') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->accessory === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">体型</label>
                                                <select name="body_type" class="avatar-form-input" required>
                                                    @foreach (config('services.body_type') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->body_type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 性格設定 --}}
                                    <div class="mb-8">
                                        <h2 class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                            </svg>
                                            性格の設定
                                        </h2>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">口調</label>
                                                <select name="tone" class="avatar-form-input" required>
                                                    @foreach (config('services.tone') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->tone === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">熱意</label>
                                                <select name="enthusiasm" class="avatar-form-input" required>
                                                    @foreach (config('services.enthusiasm') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->enthusiasm === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">丁寧さ</label>
                                                <select name="formality" class="avatar-form-input" required>
                                                    @foreach (config('services.formality') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->formality === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="avatar-form-group">
                                                <label class="avatar-form-label">ユーモア</label>
                                                <select name="humor" class="avatar-form-input" required>
                                                    @foreach (config('services.humor') as $key => $label)
                                                        <option value="{{ $key }}" {{ $avatar->humor === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 描画モデル --}}
                                    <div class="mb-8">
                                        <h2 class="text-lg lg:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                            <div class="model-icon-gradient w-5 h-5 lg:w-6 lg:h-6 rounded-lg flex items-center justify-center">
                                                <svg class="w-3 h-3 lg:w-4 lg:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            描画モデルの選択
                                        </h2>
                                        
                                        <div class="avatar-form-group">
                                            <label class="avatar-form-label flex items-center gap-2">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                                </svg>
                                                イラストスタイル
                                            </label>
                                            <select name="draw_model_version" class="avatar-form-input model-select" required>
                                                @foreach (config('services.draw_model_versions') as $key => $value)
                                                    <option value="{{ $key }}" {{ $avatar->draw_model_version === $key ? 'selected' : '' }}>
                                                        {{ $key }} - 
                                                        @if($key === 'anything-v4.0')
                                                            -- {{ !$isChildTheme ? '推定トークン使用量' : '推定コイン使用量' }}: {{ number_format(config('services.estimated_token_usages')['anything-v4.0']) }}
                                                        @elseif($key === 'animagine-xl-3.1')
                                                            -- {{ !$isChildTheme ? '推定トークン使用量' : '推定コイン使用量' }}: {{ number_format(config('services.estimated_token_usages')['animagine-xl-3.1']) }}
                                                        @elseif($key === 'stable-diffusion-3.5-medium')
                                                            -- {{ !$isChildTheme ? '推定トークン使用量' : '推定コイン使用量' }}: {{ number_format(config('services.estimated_token_usages')['stable-diffusion-3.5-medium']) }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- 背景透過フラグ --}}
                                        <div class="mb-8">
                                            <label class="avatar-form-label flex items-center gap-2">
                                                <input type="checkbox" name="is_transparent" class="avatar-form-checkbox" {{ $avatar->is_transparent ? 'checked' : '' }}>
                                                背景を透過する
                                            </label>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                アバター画像の背景を透明にします。<br>
                                                一部のモデルでは背景透過がサポートされていない場合があります。<br>
                                                背景透過には{{ !$isChildTheme ? 'トークン' : 'コイン' }}が使用されます。
                                            </p>
                                        </div>
                                        {{-- モデル情報ヒント --}}
                                        <div class="mt-4 p-4 model-info-card rounded-lg">
                                            <div class="flex items-start gap-3">
                                                <div class="model-info-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">描画モデルについて</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        描画モデルによって、アバターのイラストタッチが変わります。<br>
                                                        モデルによって消費する{{ !$isChildTheme ? 'トークン' : 'コイン' }}は異なります。<br>
                                                        お好みのスタイルをお選びください。今後、新しいモデルが追加される予定です。
                                                     </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ボタン群 --}}
                                    <div class="flex flex-col sm:flex-row justify-between gap-4">
                                        <div>
                                            <button type="submit" id="store-setting-btn" class="avatar-btn-primary px-6 py-2.5 w-full sm:w-auto">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                設定を保存
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                {{-- 画像再生成 --}}
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    {{-- 注意事項カード（子ども向けのみ表示） --}}
                                    @if($isChildTheme)
                                        <div class="regenerate-warning-card mb-4">
                                            <div class="flex items-start gap-3">
                                                <div class="regenerate-warning-icon">
                                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-bold text-pink-900 dark:text-pink-100 mb-1">
                                                        画像をつくりなおすとコインを使います
                                                    </p>
                                                    <p class="text-sm text-pink-800 dark:text-pink-200">
                                                        つくりなおすには 
                                                        <strong class="text-lg">
                                                            <span id="token-amount">{{ number_format(config('services.estimated_token_usages')[$avatar->draw_model_version] ?? 2000) }}</span> コイン
                                                        </strong> 
                                                        が必要です
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('avatars.regenerate') }}" onsubmit="return confirm('画像を再生成しますか?トークンが消費されます。')">
                                        @csrf
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                            <button type="submit" class="{{ $isChildTheme ? 'avatar-btn-regenerate' : 'avatar-btn-secondary px-4 py-2 text-sm' }} w-full sm:w-auto shrink-0">
                                                @if (!$isChildTheme)
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    再生成
                                                @else
                                                    画像をつくりなおす
                                                @endif                                                
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>