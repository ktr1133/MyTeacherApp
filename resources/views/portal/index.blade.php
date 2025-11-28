@extends('layouts.portal')

@section('title', 'ポータルトップ')
@section('meta_description', 'Famico ポータルサイト - AIタスク管理アプリケーションの総合情報')
@section('og_title', 'Famico ポータル')

@section('content')
<!-- ページ全体の背景装飾 -->
<div class="fixed inset-0 -z-10 pointer-events-none">
    <!-- グラデーション背景 -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl floating-icon"></div>
    <div class="absolute top-1/4 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl floating-icon"></div>
    <div class="absolute top-1/3 right-20 w-64 h-64 bg-pink-500/10 rounded-full blur-3xl floating-icon" style="animation-delay: 0.5s;"></div>
    <div class="absolute bottom-1/4 left-1/4 w-80 h-80 bg-[#59B9C6]/10 rounded-full blur-3xl floating-icon" style="animation-delay: 0.7s;"></div>
    <div class="absolute bottom-20 right-1/3 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl floating-icon" style="animation-delay: 1s;"></div>
    
    <!-- アイコングリッド - 各サービスを象徴 -->
    <div class="absolute inset-0 opacity-3 dark:opacity-5">
        <!-- MyTeacher: タスク管理 -->
        <svg class="absolute top-20 left-1/4 w-16 h-16 text-[#59B9C6] floating-icon" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
        </svg>
        
        <!-- コミュニティアプリ: 人々の繋がり -->
        <svg class="absolute top-32 right-1/3 w-20 h-20 text-purple-500 floating-icon" style="animation-delay: 0.3s;" fill="currentColor" viewBox="0 0 20 20">
            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
        </svg>
        
        <!-- AI学習アプリ: 脳・学習 -->
        <svg class="absolute top-1/3 left-1/3 w-18 h-18 text-pink-500 floating-icon" style="animation-delay: 0.6s;" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
        </svg>
        
        <!-- ハート: 家族の絆 -->
        <svg class="absolute top-2/3 right-1/4 w-14 h-14 text-[#59B9C6] floating-icon" style="animation-delay: 0.9s;" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
        </svg>
        
        <!-- 本: 知識・ガイド -->
        <svg class="absolute top-1/2 right-1/4 w-16 h-16 text-purple-400 floating-icon" style="animation-delay: 0.4s;" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
        </svg>
        
        <!-- スター: 成長・達成 -->
        <svg class="absolute bottom-1/3 left-1/4 w-12 h-12 text-pink-400 floating-icon" style="animation-delay: 0.7s;" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
        
        <!-- チャート: データ・進捗 -->
        <svg class="absolute bottom-1/4 left-1/3 w-14 h-14 text-[#59B9C6] floating-icon" style="animation-delay: 1s;" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
        </svg>
        
        <!-- 電球: アイデア・創造性 -->
        <svg class="absolute top-48 left-1/3 w-14 h-14 text-yellow-500 floating-icon" style="animation-delay: 0.5s;" fill="currentColor" viewBox="0 0 20 20">
            <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z"/>
        </svg>
    </div>
    
    <!-- 装飾的な線 - サービス間の繋がりを表現 -->
    <svg class="absolute inset-0 w-full h-full opacity-3 dark:opacity-5" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="line-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#59B9C6;stop-opacity:1" />
                <stop offset="50%" style="stop-color:#8b5cf6;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
            </linearGradient>
        </defs>
        <path d="M 100 150 Q 300 100 500 200 T 900 150" stroke="url(#line-gradient)" stroke-width="2" fill="none" class="floating-icon"/>
        <path d="M 200 400 Q 400 350 600 450 T 1000 400" stroke="url(#line-gradient)" stroke-width="2" fill="none" class="floating-icon" style="animation-delay: 0.5s;"/>
        <path d="M 150 800 Q 350 750 550 850 T 950 800" stroke="url(#line-gradient)" stroke-width="2" fill="none" class="floating-icon" style="animation-delay: 0.8s;"/>
    </svg>
</div>

<!-- Hero Section -->
<section class="px-4 sm:px-6 lg:px-8 py-16 relative overflow-hidden">
    <div class="max-w-7xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full border border-gray-200 dark:border-gray-700 mb-6">
            <svg class="w-4 h-4 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">総合情報ポータル</span>
        </div>

        <h1 class="text-5xl sm:text-6xl font-bold mb-6">
            <span class="gradient-text">Famico Portal</span>
        </h1>
        
        <!-- メインキャッチコピー（グラスモーフィズム） -->
        <div class="inline-block mb-6 px-8 py-6 rounded-2xl bg-white/70 dark:bg-gray-800/70 backdrop-blur-md border border-white/20 dark:border-gray-700/30 shadow-lg">
            <p class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white leading-relaxed">
                学びも、記録も、サポートも。<br>
                スマートな子育ては、ここから始まる。
            </p>
        </div>
        
        <!-- サブコピー（リード文・グラスモーフィズム） -->
        <div class="max-w-3xl mx-auto mb-12 px-6 py-5 rounded-xl bg-white/60 dark:bg-gray-800/60 backdrop-blur-md border border-white/20 dark:border-gray-700/30 shadow-md">
            <p class="text-base sm:text-lg text-gray-700 dark:text-gray-200 leading-relaxed">
                Famico（ファミコ）は、「MyTeacher」および関連サービスの利用をサポートする公式ポータルです。<br class="hidden sm:inline">
                各アプリの最新情報やメンテナンス状況の確認、使い方ガイド、お問い合わせ窓口など、<br class="hidden sm:inline">
                快適にご利用いただくための全ての機能にここからアクセスできます。
            </p>
        </div>

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

<- batch:execute-scheduled-tasks に withoutOverlapping ( ) と runInBackground ( ) CI/CD Test: 2025-11-28 14:28:28 -->
