<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>MyTeacher - AIタスク管理アプリ</title>

        <!-- Favicons -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="alternate icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <meta name="theme-color" content="#59B9C6">

        <!-- SEO Meta Tags -->
        <meta name="description" content="MyTeacher - GPT-4o-miniとStable Diffusionを活用した次世代タスク管理アプリ。AIがタスクを自動分解し、教師アバターが学習をサポートします。">
        <meta name="keywords" content="タスク管理,AI,教育,学習サポート,GPT-4,DALL-E,アバター">
        
        <!-- OGP Meta Tags -->
        <meta property="og:title" content="MyTeacher - AIタスク管理アプリ">
        <meta property="og:description" content="GPT-4o-miniとStable Diffusionを活用した次世代タスク管理">
        <meta property="og:type" content="website">
        <meta property="og:image" content="{{ asset('apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/css/welcome.css', 'resources/js/welcome-chart.js'])

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

        <!-- Dark Mode Script -->
        <script>
            // ダークモードの初期化（フリッカー防止のためhead内で実行）
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="antialiased bg-gradient-to-br from-[#F3F3F2] via-white to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Header (Fixed) -->
        <header class="fixed top-0 left-0 right-0 z-50 header-blur bg-white/70 dark:bg-gray-900/70 border-b border-gray-200/50 dark:border-gray-700/50">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-8 h-8 text-[#59B9C6] transition-transform group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                            </svg>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-600 rounded-full animate-ping"></div>
                        </div>
                        <span class="text-xl font-bold gradient-text">MyTeacher</span>
                    </div>
                    
                    <!-- Navigation Links -->
                    @if (Route::has('login'))
                        <div class="flex items-center gap-3">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn-primary inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    タスクリスト
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm px-4 py-2">
                                    Login
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn-primary inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold text-sm">
                                        Free Start
                                    </a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="pt-32 pb-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <!-- 背景の装飾 -->
            <div class="absolute inset-0 -z-10">
                <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl floating-icon"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl floating-icon"></div>
            </div>

            <div class="max-w-7xl mx-auto">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full border border-gray-200 dark:border-gray-700 mb-6 hero-subtitle">
                        <svg class="w-4 h-4 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">GPT-4o-mini & Stable Diffusion 搭載</span>
                    </div>

                    <h1 class="hero-title text-5xl sm:text-6xl lg:text-7xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                        AIアバターが応援<br>
                        <span class="gradient-text">家族で使えるタスク管理</span>
                    </h1>
                    
                    <p class="hero-subtitle text-xl sm:text-2xl text-gray-600 dark:text-gray-300 mb-10 max-w-3xl mx-auto leading-relaxed">
                        複雑なタスクはAIが分解。<br>完了したらアバターが祝福。<br>
                        お小遣い管理から自己管理まで、楽しく続けられます。
                    </p>
                    
                    <div class="hero-cta flex flex-col sm:flex-row items-center justify-center gap-4">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary group inline-flex items-center px-8 py-4 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-xl hover:shadow-2xl transition font-bold text-lg">
                                <svg class="w-5 h-5 mr-2 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                無料で始める
                                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        @endif
                        <a href="#features" class="group inline-flex items-center px-8 py-4 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:border-[#59B9C6] hover:text-[#59B9C6] transition font-bold text-lg bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
                            使い方を見る
                            <svg class="w-5 h-5 ml-2 group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Hero Image: ちびキャラのユーザー＋アバター -->
                <div class="hero-image mt-20 relative">
                    <div class="max-w-3xl mx-auto">
                        <img src="{{ asset('images/welcome-hero.png') }}" 
                             alt="ちびキャラの子どもがタスクに取り組み、教師アバターが応援している様子" 
                             class="w-full h-auto object-contain floating-icon drop-shadow-2xl">
                    </div>
                    <div class="mt-8 text-center">
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-300">
                            教師アバターがあなたの成長を見守ります
                        </p>
                    </div>
                </div>
            </div>

            <!-- スクロールインジケーター -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 scroll-indicator">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
        </section>

        <!-- 他と違う3つの理由 -->
        <section class="py-16 bg-white dark:bg-gray-900 border-y border-gray-200/50 dark:border-gray-700/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-3">他と違う3つの理由</h2>
                    <p class="text-gray-600 dark:text-gray-300">MyTeacherだけの特別な機能</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- AI分解 -->
                    <div class="feature-card bg-gradient-to-br from-[#59B9C6]/10 to-transparent p-8 rounded-2xl border border-[#59B9C6]/20 text-center group">
                        <div class="w-20 h-20 bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">AI タスク分解</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            GPT-4o-miniが複雑なタスクを実行可能なステップに自動分解
                        </p>
                    </div>

                    <!-- アバター応援 -->
                    <div class="feature-card bg-gradient-to-br from-[#59B9C6]/10 via-purple-500/10 to-transparent p-8 rounded-2xl border border-purple-500/20 text-center group">
                        <div class="w-20 h-20 bg-gradient-to-br from-[#59B9C6] to-[#8b5cf6] rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">アバター応援</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            Stable Diffusionが生成する教師アバターが励ましのコメントでサポート
                        </p>
                    </div>

                    <!-- 家族共有 -->
                    <div class="feature-card bg-gradient-to-br from-purple-500/10 to-pink-500/10 p-8 rounded-2xl border border-pink-500/20 text-center group">
                        <div class="w-20 h-20 bg-gradient-to-br from-[#9333ea] to-[#ec4899] rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">家族共有</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            グループタスクで家事分担、お小遣いをポイント管理
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 機能紹介（4つ） -->
        <section id="features" class="py-24 bg-gradient-to-br from-[#F3F3F2] to-gray-100 dark:from-gray-800 dark:to-gray-900 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-20">
                    <span class="inline-block px-4 py-2 bg-[#59B9C6]/10 text-[#59B9C6] rounded-full text-sm font-semibold mb-4">Features</span>
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">主な機能</h2>
                    <div class="section-divider mb-6"></div>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">効率的なタスク管理を実現する4つの機能</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    <!-- グループタスク -->
                    <div class="feature-card bg-gradient-to-br from-[#9333ea]/10 to-[#ec4899]/10 p-8 rounded-2xl border border-[#9333ea]/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#9333ea] to-[#ec4899] rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">グループタスク</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                            家族やチームでタスクを共有。承認フローと報酬システム搭載。<br>
                            定期的なタスクは自動作成。
                        </p>
                    </div>

                    <!-- AIタスク分解 -->
                    <div class="feature-card bg-gradient-to-br from-[#59B9C6]/10 to-[#3b82f6]/10 p-8 rounded-2xl border border-[#59B9C6]/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">AI タスク分解</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">複雑なタスクをGPT-4o-miniが自動で細かく分解。実行可能なステップに変換。</p>
                    </div>

                    <!-- 実績レポート -->
                    <div class="feature-card bg-gradient-to-br from-[#59B9C6]/10 to-green-500/10 p-8 rounded-2xl border border-green-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#59B9C6] to-[#10b981] rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">実績レポート</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                            週間・月間・年間の実績をグラフで可視化。進捗を一目で確認。<br>
                            アバターが報告。
                        </p>
                    </div>

                    <!-- 教師アバター -->
                    <div class="feature-card bg-gradient-to-br from-[#59B9C6]/10 to-[#8b5cf6]/10 p-8 rounded-2xl border border-purple-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#59B9C6] to-[#8b5cf6] rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">アバター</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">Stable Diffusionが生成するあなた専用の先生。タスク完了時に励ましのコメント。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- アバターが応援！ -->
        <section class="py-24 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        <span class="gradient-text">アバターが応援！</span>
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300">タスク完了時に祝福してくれる、あなた専用のアバター</p>
                </div>

                <div class="grid md:grid-cols-2 gap-12 items-center max-w-6xl mx-auto">
                    <!-- アバター画像 + 吹き出し -->
                    <div class="relative">
                        <div class="w-80 h-96 mx-auto flex items-center justify-center relative">
                            <img src="{{ asset('images/avatar-celebration.png') }}" 
                                 alt="喜びの表情で両手を上げて祝福する教師アバター" 
                                 class="w-full h-full object-contain floating-icon drop-shadow-2xl">
                        </div>
                        <!-- 吹き出し -->
                        <div class="mt-8 max-w-sm mx-auto glass-card p-6 rounded-2xl border relative">
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0 h-0 border-l-8 border-r-8 border-b-8 border-transparent border-b-white dark:border-b-gray-800"></div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white text-center">
                                タスク完了おめでとうございます！<br>
                                素晴らしい進捗ですね！
                            </p>
                        </div>
                    </div>

                    <!-- 特徴リスト -->
                    <div class="space-y-6">
                        <div class="flex items-start gap-4 p-4 glass-card rounded-xl border">
                            <div class="w-12 h-12 bg-gradient-to-br from-[#59B9C6] to-[#8b5cf6] rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white mb-1">励ましのコメント</h4>
                                <p class="text-gray-600 dark:text-gray-300">タスク登録、完了時に応援メッセージを送信</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 glass-card rounded-xl border">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white mb-1">ドラッグで移動可能</h4>
                                <p class="text-gray-600 dark:text-gray-300">邪魔にならない位置に自由に調整できます</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 家族で使うならこんな風に -->
        <section class="py-24 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">家族で使うとこうなります</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300">お小遣いをポイント管理。家事もゲーム感覚で楽しく分担。</p>
                </div>

                <div class="space-y-6">
                    <!-- Step 1 -->
                    <div class="glass-card p-6 rounded-2xl border flex items-center gap-6 group hover:shadow-xl transition">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#9333ea] to-[#ec4899] rounded-full flex items-center justify-center flex-shrink-0 text-3xl shadow-lg">
                            👨‍👩‍👧‍👦
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">親がグループタスクを作成</h3>
                            <p class="text-gray-600 dark:text-gray-300">「部屋の掃除」報酬: 50ポイント</p>
                        </div>
                    </div>

                    <div class="text-center text-2xl text-gray-400">↓</div>

                    <!-- Step 2 -->
                    <div class="glass-card p-6 rounded-2xl border flex items-center gap-6 group hover:shadow-xl transition">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] rounded-full flex items-center justify-center flex-shrink-0 text-3xl shadow-lg">
                            👧
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">子がタスクを完了</h3>
                            <p class="text-gray-600 dark:text-gray-300">掃除が終わったら「完了」ボタン</p>
                        </div>
                    </div>

                    <div class="text-center text-2xl text-gray-400">↓</div>

                    <!-- Step 3 -->
                    <div class="glass-card p-6 rounded-2xl border flex items-center gap-6 group hover:shadow-xl transition">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#9333ea] to-[#ec4899] rounded-full flex items-center justify-center flex-shrink-0 text-3xl shadow-lg">
                            👨‍👩‍👧‍👦
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">親が承認してポイント付与</h3>
                            <p class="text-gray-600 dark:text-gray-300">承認待ち画面から「承認」をクリック</p>
                        </div>
                    </div>

                    <div class="text-center text-2xl text-gray-400">↓</div>

                    <!-- Step 4 -->
                    <div class="glass-card p-6 rounded-2xl border flex items-center gap-6 group hover:shadow-xl transition">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#59B9C6] to-[#8b5cf6] rounded-full flex items-center justify-center flex-shrink-0 text-3xl shadow-lg">
                            💬
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">教師アバターが祝福</h3>
                            <p class="text-gray-600 dark:text-gray-300">「タスク完了おめでとう！50ポイント獲得！」</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- こんな方におすすめ -->
        <section class="py-24 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">こんな方におすすめ</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- 家族 -->
                    <div class="glass-card p-8 rounded-2xl border border-[#9333ea]/30 text-center group hover:shadow-2xl transition">
                        <div class="w-24 h-24 bg-gradient-to-br from-[#9333ea] to-[#ec4899] rounded-full flex items-center justify-center mx-auto mb-6 text-5xl shadow-lg">
                            👨‍👩‍👧‍👦
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">お子様がいる家族</h3>
                        <ul class="text-left space-y-2 text-gray-600 dark:text-gray-300">
                            <li>✅ 定期的な家事を自動でタスク化してポイント付与</li>
                            <li>✅ お小遣いをデジタル管理</li>
                            <li>✅ 子どもの成長を可視化</li>
                        </ul>
                    </div>

                    <!-- 学生 -->
                    <div class="glass-card p-8 rounded-2xl border border-[#59B9C6]/30 text-center group hover:shadow-2xl transition">
                        <div class="w-24 h-24 bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] rounded-full flex items-center justify-center mx-auto mb-6 text-5xl shadow-lg">
                            👨‍🎓
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">課題や試験勉強を管理する学生</h3>
                        <ul class="text-left space-y-2 text-gray-600 dark:text-gray-300">
                            <li>✅ 試験勉強をスケジュール化</li>
                            <li>✅ タグで科目別に整理</li>
                            <li>✅ アバターが励ましてくれる</li>
                        </ul>
                    </div>

                    <!-- チーム -->
                    <div class="glass-card p-8 rounded-2xl border border-orange-500/30 text-center group hover:shadow-2xl transition">
                        <div class="w-24 h-24 bg-gradient-to-br from-[#f59e0b] to-[#f97316] rounded-full flex items-center justify-center mx-auto mb-6 text-5xl shadow-lg">
                            👩‍💼
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">タスク管理を楽しくしたい方</h3>
                        <ul class="text-left space-y-2 text-gray-600 dark:text-gray-300">
                            <li>✅ 気分によってアバターを切り替え</li>
                            <li>✅ アバターが実績を報告</li>
                            <li>✅ タグでプロジェクト別に管理</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 実績を可視化 -->
        <section class="py-24 bg-gradient-to-br from-[#F3F3F2] to-gray-100 dark:from-gray-800 dark:to-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">実績を可視化</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300">週間・月間・年間の進捗をグラフで確認</p>
                </div>

                <div class="glass-card p-8 rounded-3xl border max-w-5xl mx-auto">
                    <!-- グラフタブ -->
                    <div class="flex gap-4 mb-6 border-b border-gray-200 dark:border-gray-700">
                        <button onclick="showChart('weekly')" id="tab-weekly" class="chart-tab active px-6 py-3 font-semibold text-[#59B9C6] border-b-2 border-[#59B9C6] transition">
                            週間
                        </button>
                        <button onclick="showChart('monthly')" id="tab-monthly" class="chart-tab px-6 py-3 font-semibold text-gray-500 dark:text-gray-400 hover:text-[#59B9C6] transition">
                            月間
                        </button>
                        <button onclick="showChart('yearly')" id="tab-yearly" class="chart-tab px-6 py-3 font-semibold text-gray-500 dark:text-gray-400 hover:text-[#59B9C6] transition">
                            年間
                        </button>
                    </div>

                    <!-- グラフコンテナ -->
                    <div class="relative" style="height: 400px;">
                        <canvas id="performanceChart"></canvas>
                    </div>

                    <!-- 統計サマリー -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                        <div class="bg-gradient-to-br from-[#59B9C6]/10 to-transparent p-4 rounded-xl border border-[#59B9C6]/20">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">完了タスク</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white" id="stat-completed">42</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500/10 to-transparent p-4 rounded-xl border border-purple-500/20">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">獲得ポイント</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white" id="stat-tokens">2,850</div>
                        </div>
                        <div class="bg-gradient-to-br from-pink-500/10 to-transparent p-4 rounded-xl border border-pink-500/20">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">達成率</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white" id="stat-rate">87%</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script>
            // ダークモードの初期化（フリッカー防止のためhead内で実行）
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- CTA: 今すぐ始めよう -->
        <section class="py-24 cta-gradient relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">今すぐ始めよう</h2>
                <p class="text-xl text-white/90 mb-10 max-w-2xl mx-auto">
                    アカウント作成は無料。<br>
                    今日からアバターと一緒に成長しましょう。
                </p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-primary group inline-flex items-center px-10 py-5 bg-white text-[#59B9C6] rounded-xl hover:bg-gray-50 transition font-bold text-lg shadow-2xl">
                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        無料で始める
                        <svg class="w-6 h-6 ml-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @endif
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col items-center gap-4 text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                        </svg>
                        <span class="text-lg font-bold gradient-text">MyTeacher</span>
                    </div>
                    <p>&copy; {{ date('Y') }} MyTeacher. All rights reserved.</p>
                    <p class="text-sm">Powered by OpenAI GPT-4o-mini & Stable Diffusion</p>
                </div>
            </div>
        </footer>
    </body>
</html>