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
                    x-data="avatarForm()"
                    @submit="submitForm($event)"
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
                                    <option value="male">男性</option>
                                    <option value="female">女性</option>
                                    <option value="other">その他</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">髪の色</label>
                                <select name="hair_color" class="avatar-form-input" required>
                                    <option value="black">黒</option>
                                    <option value="brown">茶</option>
                                    <option value="blonde">金</option>
                                    <option value="silver">銀</option>
                                    <option value="red">赤</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">目の色</label>
                                <select name="eye_color" class="avatar-form-input" required>
                                    <option value="brown">茶</option>
                                    <option value="blue">青</option>
                                    <option value="green">緑</option>
                                    <option value="gray">灰</option>
                                    <option value="purple">紫</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">服装</label>
                                <select name="clothing" class="avatar-form-input" required>
                                    <option value="suit">スーツ</option>
                                    <option value="casual">カジュアル</option>
                                    <option value="kimono">和服</option>
                                    <option value="robe">ローブ</option>
                                    <option value="dress">ドレス</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">アクセサリー</label>
                                <select name="accessory" class="avatar-form-input">
                                    <option value="">なし</option>
                                    <option value="glasses">眼鏡</option>
                                    <option value="hat">帽子</option>
                                    <option value="tie">ネクタイ</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">体型</label>
                                <select name="body_type" class="avatar-form-input" required>
                                    <option value="average">標準</option>
                                    <option value="slim">細身</option>
                                    <option value="sturdy">がっしり</option>
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
                                    <option value="gentle">優しい</option>
                                    <option value="strict">厳しい</option>
                                    <option value="friendly">フレンドリー</option>
                                    <option value="intellectual">知的</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">熱意</label>
                                <select name="enthusiasm" class="avatar-form-input" required>
                                    <option value="high">高い</option>
                                    <option value="normal">普通</option>
                                    <option value="modest">控えめ</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">丁寧さ</label>
                                <select name="formality" class="avatar-form-input" required>
                                    <option value="polite">丁寧</option>
                                    <option value="casual">カジュアル</option>
                                    <option value="formal">フォーマル</option>
                                </select>
                            </div>

                            <div class="avatar-form-group">
                                <label class="avatar-form-label">ユーモア</label>
                                <select name="humor" class="avatar-form-input" required>
                                    <option value="high">高い</option>
                                    <option value="normal">普通</option>
                                    <option value="low">控えめ</option>
                                </select>
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
                                <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">アバター作成には <strong>{{ number_format(config('const.estimate_token')) }}トークン</strong> が必要です。</p>
                            </div>
                        </div>
                    </div>

                    {{-- 送信ボタン --}}
                    <div class="flex justify-center gap-4">
                        <a href="{{ route('dashboard') }}" class="avatar-btn-secondary px-8 py-3 text-lg">
                            スキップ
                        </a>
                        <button type="submit" class="avatar-btn-primary px-8 py-3 text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            アバターを作成する
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>