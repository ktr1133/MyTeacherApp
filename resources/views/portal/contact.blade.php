{{-- filepath: /home/ktr/mtdev/laravel/resources/views/portal/contact.blade.php --}}
@extends('layouts.portal')

@section('title', 'お問い合わせ')
@section('meta_description', 'MyTeacherへのお問い合わせフォーム')

@section('content')
<!-- Page Header -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl font-bold gradient-text mb-4">お問い合わせ</h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            サービスに関するご質問・ご要望・不具合報告など、お気軽にお問い合わせください
        </p>
    </div>
</section>

<!-- Contact Form -->
<section class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-gray-200 dark:border-gray-700 p-8">
            <form method="POST" action="{{ route('portal.contact.store') }}" class="space-y-6">
                @csrf

                <!-- お名前 -->
                <div>
                    <label for="name" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        お名前 <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', auth()->user()->name ?? '') }}"
                        required
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition @error('name') border-red-500 @enderror"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- メールアドレス -->
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        メールアドレス <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email', auth()->user()->email ?? '') }}"
                        required
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 対象アプリ -->
                <div>
                    <label for="app_name" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        対象アプリ <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="app_name" 
                        name="app_name" 
                        required
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition @error('app_name') border-red-500 @enderror"
                    >
                        <option value="">選択してください</option>
                        @foreach(config('const.app_names') as $key => $label)
                            <option value="{{ $key }}" {{ old('app_name') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('app_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 件名 -->
                <div>
                    <label for="subject" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        件名 <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="subject" 
                        name="subject" 
                        value="{{ old('subject') }}"
                        required
                        maxlength="100"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition @error('subject') border-red-500 @enderror"
                    >
                    @error('subject')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- お問い合わせ内容 -->
                <div>
                    <label for="message" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        お問い合わせ内容 <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="message" 
                        name="message" 
                        required
                        rows="8"
                        minlength="10"
                        maxlength="1000"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition @error('message') border-red-500 @enderror"
                    >{{ old('message') }}</textarea>
                    <div class="mt-1 flex justify-between items-center">
                        @error('message')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">10文字以上、1000文字以内で入力してください</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span id="char-count">0</span> / 1000
                        </p>
                    </div>
                </div>

                <!-- 注意事項 -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-300">
                            <p class="font-semibold mb-2">ご注意事項</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>お問い合わせへの返信には数日かかる場合がございます</li>
                                <li>緊急の不具合報告は件名に【緊急】と記載してください</li>
                                <li>スクリーンショットが必要な場合は、本文内でその旨お知らせください</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center pt-4">
                    <button 
                        type="submit"
                        class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-xl transition font-bold text-lg"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        送信する
                    </button>
                </div>
            </form>
        </div>

        <!-- FAQ Link -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                よくある質問で解決する場合があります
            </p>
            <a href="{{ route('portal.faq') }}" class="inline-flex items-center text-[#59B9C6] hover:text-[#3b82f6] transition font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                FAQを確認する
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Character counter
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    
    messageTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    
    // 初期値設定
    charCount.textContent = messageTextarea.value.length;
</script>
@endpush
