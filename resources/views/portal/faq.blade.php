{{-- filepath: /home/ktr/mtdev/laravel/resources/views/portal/faq.blade.php --}}
@extends('layouts.portal')

@section('title', 'よくある質問 (FAQ)')
@section('meta_description', 'MyTeacherに関するよくある質問と回答集')

@section('content')
<!-- Page Header -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl font-bold gradient-text mb-4">よくある質問 (FAQ)</h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            ユーザーから寄せられる質問とその回答をまとめています
        </p>
    </div>
</section>

<!-- Search & Filter -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <form method="GET" action="{{ route('portal.faq') }}" class="space-y-4">
            <!-- Search Box -->
            <div class="relative">
                <input 
                    type="text" 
                    name="keyword" 
                    value="{{ request('keyword') }}"
                    placeholder="キーワードで検索..." 
                    class="w-full px-6 py-4 pl-12 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition"
                >
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <!-- Category Filter -->
                <select 
                    name="category" 
                    class="px-4 py-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition"
                    onchange="this.form.submit()"
                >
                    <option value="">すべてのカテゴリ</option>
                    @foreach(config('const.faq_categories') as $key => $label)
                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <!-- App Filter -->
                <select 
                    name="app_name" 
                    class="px-4 py-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition"
                    onchange="this.form.submit()"
                >
                    <option value="">すべてのアプリ</option>
                    @foreach(config('const.app_names') as $key => $label)
                        <option value="{{ $key }}" {{ request('app_name') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <!-- Reset Button -->
                @if(request()->hasAny(['keyword', 'category', 'app_name']))
                    <a href="{{ route('portal.faq') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        リセット
                    </a>
                @endif

                <!-- Search Button -->
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold">
                    検索
                </button>
            </div>
        </form>
    </div>
</section>

<!-- FAQ List -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        @if($faqs->isEmpty())
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg text-gray-600 dark:text-gray-400">該当するFAQが見つかりませんでした</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($faqs as $faq)
                <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 overflow-hidden hover:border-[#59B9C6] dark:hover:border-[#59B9C6] transition">
                    <!-- Question (Toggle Button) -->
                    <button 
                        type="button"
                        class="w-full text-left px-6 py-5 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                        onclick="toggleFaq(this)"
                    >
                        <div class="flex-shrink-0 w-8 h-8 bg-[#59B9C6]/10 rounded-lg flex items-center justify-center mt-1">
                            <svg class="w-5 h-5 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4 mb-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $faq->question }}</h3>
                                <svg class="faq-icon w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                            
                            <!-- Meta Info -->
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300 rounded">
                                    {{ config('const.faq_categories')[$faq->category] ?? $faq->category }}
                                </span>
                                <span class="px-2 py-1 bg-[#59B9C6]/10 text-xs font-medium text-[#59B9C6] rounded">
                                    {{ config('const.app_names')[$faq->app_name] ?? $faq->app_name }}
                                </span>
                            </div>
                        </div>
                    </button>

                    <!-- Answer (Hidden by default) -->
                    <div class="faq-answer hidden px-6 pb-6">
                        <div class="pl-12 bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                {!! nl2br(e($faq->answer)) !!}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- CTA Section -->
<section class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-4xl mx-auto text-center bg-white dark:bg-gray-800 rounded-2xl border-2 border-gray-200 dark:border-gray-700 p-8">
        <h2 class="text-2xl font-bold gradient-text mb-4">解決しない場合は</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            FAQで解決しない問題は、お問い合わせフォームからお気軽にご連絡ください
        </p>
        <a href="{{ route('portal.contact') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            お問い合わせフォームへ
        </a>
    </div>
</section>
@endsection

@push('scripts')
<script>
    function toggleFaq(button) {
        const answer = button.nextElementSibling;
        const icon = button.querySelector('.faq-icon');
        
        // Toggle answer visibility
        answer.classList.toggle('hidden');
        
        // Rotate icon
        if (answer.classList.contains('hidden')) {
            icon.style.transform = 'rotate(0deg)';
        } else {
            icon.style.transform = 'rotate(180deg)';
        }
    }
</script>
@endpush
