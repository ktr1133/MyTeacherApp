{{-- filepath: /home/ktr/mtdev/laravel/resources/views/portal/index.blade.php --}}
@extends('layouts.portal')

@section('title', 'ポータルトップ')
@section('meta_description', 'MyTeacherポータルサイト - AIタスク管理アプリケーションの総合情報')
@section('og_title', 'MyTeacher ポータル')

@section('content')
<!-- Hero Section -->
<section class="px-4 sm:px-6 lg:px-8 py-16 relative overflow-hidden">
    <!-- 背景装飾 -->
    <div class="absolute inset-0 -z-10">
        <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl floating-icon"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl floating-icon"></div>
    </div>

    <div class="max-w-7xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full border border-gray-200 dark:border-gray-700 mb-6">
            <svg class="w-4 h-4 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">総合情報ポータル</span>
        </div>

        <h1 class="text-5xl sm:text-6xl font-bold mb-6">
            <span class="gradient-text">MyTeacher Portal</span>
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-12">
            AIタスク管理アプリケーションの使い方ガイド、<br class="hidden sm:inline">
            メンテナンス情報、FAQ、お問い合わせ窓口
        </p>

        <!-- Quick Links -->
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('portal.guide.index') }}" class="btn-primary inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                使い方ガイド
            </a>
            <a href="{{ route('portal.faq') }}" class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-lg transition font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                FAQ
            </a>
            <a href="{{ route('portal.contact') }}" class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-lg transition font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                お問い合わせ
            </a>
        </div>
    </div>
</section>

<!-- Maintenance Section -->
@if($maintenances->isNotEmpty())
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold gradient-text">メンテナンス情報</h2>
            <a href="{{ route('portal.maintenance') }}" class="link-underline text-[#59B9C6] hover:text-[#3b82f6] transition">すべて見る →</a>
        </div>

        <div class="grid gap-6">
            @foreach($maintenances as $maintenance)
            <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] transition">
                <div class="flex items-start gap-4">
                    <!-- Status Badge -->
                    @php
                        $statusConfig = [
                            'scheduled' => ['label' => '予定', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
                            'in_progress' => ['label' => '実施中', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'],
                            'completed' => ['label' => '完了', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
                        ];
                        $status = $statusConfig[$maintenance->status] ?? ['label' => '不明', 'class' => 'bg-gray-100 text-gray-800'];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>

                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $maintenance->title }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $maintenance->description }}</p>
                        
                        <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $maintenance->scheduled_at->format('Y/m/d H:i') }}
                            </div>
                            @if($maintenance->affected_apps)
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    対象: {{ implode(', ', $maintenance->affected_apps) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Updates Section -->
@if($updates->isNotEmpty())
<section class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold gradient-text">最新の更新履歴</h2>
            <a href="{{ route('portal.updates') }}" class="link-underline text-[#59B9C6] hover:text-[#3b82f6] transition">すべて見る →</a>
        </div>

        <div class="grid gap-4">
            @foreach($updates as $update)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition">
                <div class="flex items-start gap-4">
                    <!-- Version Badge -->
                    <span class="px-3 py-1 rounded-lg text-sm font-semibold {{ $update->is_major ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        v{{ $update->version }}
                    </span>

                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-1">{{ $update->title }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $update->description }}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ config('const.app_names')[$update->app_name] ?? $update->app_name }}</span>
                            <span>•</span>
                            <span>{{ $update->released_at->format('Y/m/d') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Features Grid -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-12">ポータルの機能</h2>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Guide -->
            <a href="{{ route('portal.guide.index') }}" class="group bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition">
                <div class="w-12 h-12 bg-[#59B9C6]/10 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">使い方ガイド</h3>
                <p class="text-gray-600 dark:text-gray-400">各機能の詳細な使い方をステップバイステップで解説</p>
            </a>

            <!-- Maintenance -->
            <a href="{{ route('portal.maintenance') }}" class="group bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition">
                <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">メンテナンス情報</h3>
                <p class="text-gray-600 dark:text-gray-400">定期メンテナンスや緊急メンテナンスの予定と実施状況</p>
            </a>

            <!-- FAQ -->
            <a href="{{ route('portal.faq') }}" class="group bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition">
                <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">よくある質問</h3>
                <p class="text-gray-600 dark:text-gray-400">ユーザーから寄せられる質問とその回答集</p>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="px-4 sm:px-6 lg:px-8 py-16">
    <div class="max-w-4xl mx-auto text-center bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] rounded-2xl p-12 text-white">
        <h2 class="text-3xl font-bold mb-4">お困りのことはありませんか?</h2>
        <p class="text-lg mb-8 opacity-90">お問い合わせフォームからお気軽にご連絡ください。運営チームが迅速に対応いたします。</p>
        <a href="{{ route('portal.contact') }}" class="inline-flex items-center px-8 py-4 bg-white text-[#59B9C6] rounded-lg hover:shadow-xl transition font-bold text-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            お問い合わせする
        </a>
    </div>
</section>
@endsection
