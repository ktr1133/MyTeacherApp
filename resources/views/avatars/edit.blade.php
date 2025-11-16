<x-app-layout>
    @push('styles')
        @vite(['resources/css/avatar/avatar.css', 'resources/css/dashboard.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/avatar/avatar-edit.js', 'resources/js/avatar/avatar-form.js'])
    @endpush

    <div x-data="avatarEdit()" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-screen max-h-screen auth-gradient-bg relative overflow-hidden">
        
        {{-- 背景装飾 --}}
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl avatar-floating-decoration"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl avatar-floating-decoration" style="animation-delay: -10s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
            
            {{-- ヘッダー --}}
            <header class="shrink-0 border-b border-gray-200/50 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm shadow-sm">
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
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">
                                    教師アバター設定
                                </h1>
                                <p class="hidden sm:block text-xs text-gray-600 dark:text-gray-400">アバターの外見と性格を編集</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツエリア --}}
            <main class="flex-1 overflow-y-auto px-3 lg:px-6 py-3 lg:py-6">
                <div class="max-w-6xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
                        
                        {{-- ★ 現在のアバター表示（スライダー版） --}}
                        <div class="lg:col-span-1">
                            <div class="avatar-card rounded-2xl p-4 lg:p-6 hero-fade-in sticky top-0">
                                <h2 class="text-base lg:text-lg font-bold text-gray-900 dark:text-white mb-4">現在のアバター</h2>
                                
                                @if($avatar->generation_status === 'completed')
                                    {{-- ★ 表情スライダー --}}
                                    <div 
                                        x-data="expressionSlider({{ json_encode($expressionImages) }})"
                                        class="space-y-4"
                                    >
                                        {{-- 画像表示エリア --}}
                                        <div class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                                            {{-- 左ボタン --}}
                                            <button
                                                type="button"
                                                @click="prevExpression()"
                                                :disabled="currentIndex === 0"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/90 dark:bg-gray-800/90 shadow-lg flex items-center justify-center transition hover:bg-white dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                                                aria-label="前の表情"
                                            >
                                                <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>

                                            {{-- 右ボタン --}}
                                            <button
                                                type="button"
                                                @click="nextExpression()"
                                                :disabled="currentIndex === expressions.length - 1"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/90 dark:bg-gray-800/90 shadow-lg flex items-center justify-center transition hover:bg-white dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                                                aria-label="次の表情"
                                            >
                                                <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>

                                            {{-- 画像スライダー --}}
                                            <div 
                                                class="relative touch-pan-y"
                                                @touchstart="handleTouchStart($event)"
                                                @touchmove="handleTouchMove($event)"
                                                @touchend="handleTouchEnd($event)"
                                            >
                                                <template x-for="(expr, index) in expressions" :key="expr.type">
                                                    <div
                                                        x-show="currentIndex === index"
                                                        x-transition:enter="transition-transform duration-300 ease-out"
                                                        x-transition:enter-start="opacity-0 transform scale-95"
                                                        x-transition:enter-end="opacity-100 transform scale-100"
                                                        x-transition:leave="transition-transform duration-300 ease-in"
                                                        x-transition:leave-start="opacity-100 transform scale-100"
                                                        x-transition:leave-end="opacity-0 transform scale-95"
                                                        class="relative"
                                                    >
                                                        {{-- 表情ラベル --}}
                                                        <div class="absolute top-2 left-2 z-10 bg-black/60 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full">
                                                            <span x-text="expr.label"></span>
                                                        </div>

                                                        {{-- 画像 --}}
                                                        <template x-if="expr.image">
                                                            <img 
                                                                :src="expr.image.s3_url || expr.image.public_url" 
                                                                :alt="expr.label"
                                                                class="w-full h-auto rounded-lg border-2 border-gray-200 dark:border-gray-700"
                                                            />
                                                        </template>

                                                        {{-- 画像がない場合 --}}
                                                        <template x-if="!expr.image">
                                                            <div class="aspect-square flex flex-col items-center justify-center text-center p-6 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
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

                                        {{-- インジケーター（ドットのみ） --}}
                                        <div class="flex items-center justify-center gap-2 py-2">
                                            <template x-for="(expr, index) in expressions" :key="'dot-' + expr.type">
                                                <button
                                                    type="button"
                                                    @click="goToExpression(index)"
                                                    :class="{
                                                        'w-2 h-2 bg-gray-300 dark:bg-gray-600': currentIndex !== index,
                                                        'w-3 h-3 bg-[#59B9C6]': currentIndex === index
                                                    }"
                                                    class="rounded-full transition-all duration-300"
                                                    :aria-label="expr.label"
                                                ></button>
                                            </template>
                                        </div>

                                        {{-- 表情カウンター --}}
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="currentIndex + 1"></span> / <span x-text="expressions.length"></span>
                                            </p>
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
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">アバター表示</span>
                                            <button 
                                                type="submit"
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
                        <div class="lg:col-span-2">
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
                                                <input type="checkbox" name="is_transparent" class="avatar-form-checkbox" {{ $avatar->is_transparent ? 'checked' : '' }}>
                                                背景を透過する
                                            </label>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                アバター画像の背景を透明にします。モデルによっては動作が安定しない恐れがあります。
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
                                                        お好みのスタイルをお選びください。今後、新しいモデルが追加される予定です。
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ボタン群 --}}
                                    <div class="flex flex-col sm:flex-row justify-between gap-4">
                                        <div>
                                            <button type="submit" class="avatar-btn-primary px-6 py-2.5 w-full sm:w-auto">
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
                                    <form method="POST" action="{{ route('avatars.regenerate') }}" onsubmit="return confirm('画像を再生成しますか?トークンが消費されます。')">
                                        @csrf
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                            <div class="flex-1">
                                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">画像の再生成</h3>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">現在の設定で画像を再生成します</p>
                                            </div>
                                            <button type="submit" class="avatar-btn-secondary px-4 py-2 text-sm w-full sm:w-auto shrink-0">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                再生成
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