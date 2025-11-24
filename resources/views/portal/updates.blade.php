{{-- filepath: /home/ktr/mtdev/laravel/resources/views/portal/updates.blade.php --}}
@extends('layouts.portal')

@section('title', '更新履歴')
@section('meta_description', 'MyTeacherの最新アップデート情報とバージョン履歴')

@section('content')
<!-- Page Header -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl font-bold gradient-text mb-4">更新履歴</h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            最新のアップデート情報とバージョン履歴をお知らせします
        </p>
    </div>
</section>

<!-- Filter Section -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <form method="GET" action="{{ route('portal.updates') }}" class="flex flex-wrap gap-3">
            <!-- App Filter -->
            <select 
                name="app" 
                class="px-4 py-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition"
                onchange="this.form.submit()"
            >
                <option value="">すべてのアプリ</option>
                @foreach(config('const.app_names') as $key => $label)
                    <option value="{{ $key }}" {{ request('app') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <!-- Major Only Toggle -->
            <label class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-[#59B9C6] dark:hover:border-[#59B9C6] transition">
                <input 
                    type="checkbox" 
                    name="major_only" 
                    value="1"
                    {{ request('major_only') ? 'checked' : '' }}
                    onchange="this.form.submit()"
                    class="w-4 h-4 text-[#59B9C6] bg-gray-100 border-gray-300 rounded focus:ring-[#59B9C6] focus:ring-2"
                >
                <span class="text-sm font-medium text-gray-900 dark:text-white">メジャーアップデートのみ表示</span>
            </label>

            <!-- Reset Button -->
            @if(request()->hasAny(['app', 'major_only']))
                <a href="{{ route('portal.updates') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    リセット
                </a>
            @endif
        </form>
    </div>
</section>

<!-- Updates List -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        @if($updates->isEmpty())
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-lg text-gray-600 dark:text-gray-400">該当する更新履歴はありません</p>
            </div>
        @else
            <!-- Timeline -->
            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                <div class="space-y-8">
                    @foreach($updates as $update)
                    <div class="relative pl-20">
                        <!-- Timeline Dot -->
                        <div class="absolute left-5 top-2 w-6 h-6 rounded-full {{ $update->is_major ? 'bg-purple-600' : 'bg-[#59B9C6]' }} border-4 border-white dark:border-gray-900 shadow-lg"></div>

                        <!-- Update Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] transition">
                            <!-- Header -->
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <!-- Version Badge -->
                                    <span class="px-4 py-1.5 rounded-lg text-sm font-bold {{ $update->is_major ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-[#59B9C6]/10 text-[#59B9C6]' }}">
                                        @if($update->is_major)
                                            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endif
                                        v{{ $update->version }}
                                    </span>

                                    <!-- App Badge -->
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ config('const.app_names')[$update->app_name] ?? $update->app_name }}
                                    </span>

                                    @if($update->is_major)
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-600 text-white">
                                            Major Update
                                        </span>
                                    @endif
                                </div>

                                <!-- Release Date -->
                                <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $update->released_at->format('Y/m/d') }}
                                </div>
                            </div>

                            <!-- Title & Description -->
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $update->title }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4 whitespace-pre-line">{{ $update->description }}</p>

                            <!-- Changes -->
                            @if($update->changes)
                            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                    主な変更内容
                                </h4>
                                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                    @foreach($update->changes as $change)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>{{ $change }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

<!-- Legend -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">バージョンについて</h3>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <!-- Major Update -->
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-purple-600 flex-shrink-0 mt-0.5"></div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white mb-1">メジャーアップデート</p>
                        <p class="text-gray-600 dark:text-gray-400">
                            大きな機能追加や仕様変更を含むアップデート。操作方法が大きく変わる場合があります。
                        </p>
                    </div>
                </div>

                <!-- Minor Update -->
                <div class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-[#59B9C6] flex-shrink-0 mt-0.5"></div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white mb-1">マイナーアップデート</p>
                        <p class="text-gray-600 dark:text-gray-400">
                            機能改善やバグ修正など、小規模な更新。既存の操作方法は変わりません。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto text-center bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] rounded-2xl p-8 text-white">
        <h2 class="text-2xl font-bold mb-4">アップデート内容で不明な点がありますか?</h2>
        <p class="text-lg mb-6 opacity-90">
            使い方ガイドで詳細な説明をご覧いただけます
        </p>
        <a href="{{ route('portal.guide.index') }}" class="inline-flex items-center px-6 py-3 bg-white text-[#59B9C6] rounded-lg hover:shadow-xl transition font-bold">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            使い方ガイドを見る
        </a>
    </div>
</section>
@endsection
