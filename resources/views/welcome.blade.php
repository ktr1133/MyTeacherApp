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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/css/welcome.css'])
    </head>
    <body class="antialiased bg-gradient-to-br from-[#F3F3F2] via-white to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- ヘッダー -->
        <header class="fixed top-0 left-0 right-0 z-50 header-blur bg-white/70 dark:bg-gray-900/70 border-b border-gray-200/50 dark:border-gray-700/50">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-8 h-8 text-[#59B9C6] transition-transform group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                            </svg>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-600 rounded-full animate-ping"></div>
                        </div>
                        <span class="text-xl font-bold gradient-text">MyTeacher</span>
                    </div>
                    
                    @if (Route::has('login'))
                        <div class="flex items-center gap-3">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn-primary inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-[#59B9C6] to-[#4AA0AB] text-white rounded-lg hover:shadow-lg transition font-semibold text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    ダッシュボード
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm px-4 py-2">
                                    ログイン
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn-primary inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-[#59B9C6] to-purple-600 text-white rounded-lg hover:shadow-lg transition font-semibold text-sm">
                                        無料で始める
                                    </a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </nav>
        </header>

        <!-- ヒーローセクション -->
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
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">AIが学習をサポート</span>
                    </div>

                    <h1 class="hero-title text-5xl sm:text-6xl lg:text-7xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                        AIがサポートする<br class="sm:hidden">
                        <span class="gradient-text">次世代タスク管理</span>
                    </h1>
                    
                    <p class="hero-subtitle text-xl sm:text-2xl text-gray-600 dark:text-gray-300 mb-10 max-w-3xl mx-auto leading-relaxed">
                        個人タスクとグループタスクを効率的に管理。<br>
                        AI機能でタスクを自動分解し、実績をビジュアル化。
                    </p>
                    
                    <div class="hero-cta flex flex-col sm:flex-row items-center justify-center gap-4">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary group inline-flex items-center px-8 py-4 bg-gradient-to-r from-[#59B9C6] to-purple-600 text-white rounded-xl hover:shadow-2xl transition font-bold text-lg">
                                <svg class="w-5 h-5 mr-2 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                今すぐ始める
                                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        @endif
                        <a href="#features" class="group inline-flex items-center px-8 py-4 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:border-[#59B9C6] hover:text-[#59B9C6] transition font-bold text-lg bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
                            機能を見る
                            <svg class="w-5 h-5 ml-2 group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- スクリーンショットエリア -->
                <div class="hero-image mt-20 relative screenshot-glow">
                    <div class="glass-card rounded-3xl shadow-2xl p-6 border">
                        <div class="aspect-video bg-gradient-to-br from-[#59B9C6]/30 via-purple-500/20 to-pink-500/30 rounded-2xl flex items-center justify-center overflow-hidden relative">
                            <div class="absolute inset-0 shimmer"></div>
                            <svg class="w-32 h-32 text-[#59B9C6]/40" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                            </svg>
                        </div>
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

        <!-- 機能セクション -->
        <section id="features" class="py-24 bg-white dark:bg-gray-900 relative">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-[#59B9C6]/5 to-transparent"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="text-center mb-20">
                    <span class="inline-block px-4 py-2 bg-[#59B9C6]/10 text-[#59B9C6] rounded-full text-sm font-semibold mb-4">Features</span>
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">主な機能</h2>
                    <div class="section-divider mb-6"></div>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">効率的なタスク管理を実現する多彩な機能</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- AI機能 -->
                    <div class="feature-card bg-gradient-to-br from-[#59B9C6]/10 to-transparent p-8 rounded-2xl border border-[#59B9C6]/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#59B9C6] to-[#4AA0AB] rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">AI タスク分解</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">複雑なタスクをAIが自動で細かく分解。実行可能なステップに変換します。</p>
                    </div>

                    <!-- 弁当箱レイアウト -->
                    <div class="feature-card bg-gradient-to-br from-purple-500/10 to-transparent p-8 rounded-2xl border border-purple-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 12a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2H5zM12 5a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V5zM12 14a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">弁当箱レイアウト</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">タグ別に整理された見やすいカードレイアウトでタスクを一覧表示。</p>
                    </div>

                    <!-- グループ管理 -->
                    <div class="feature-card bg-gradient-to-br from-green-500/10 to-transparent p-8 rounded-2xl border border-green-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">グループ管理</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">チームでのタスク管理、承認フロー、報酬設定が可能。</p>
                    </div>

                    <!-- 実績レポート -->
                    <div class="feature-card bg-gradient-to-br from-orange-500/10 to-transparent p-8 rounded-2xl border border-orange-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">実績レポート</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">週間・月間・年間の実績をグラフで可視化。進捗を一目で把握。</p>
                    </div>

                    <!-- タグ管理 -->
                    <div class="feature-card bg-gradient-to-br from-blue-500/10 to-transparent p-8 rounded-2xl border border-blue-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">柔軟なタグ機能</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">プロジェクト、カテゴリー別にタスクを整理して管理。</p>
                    </div>

                    <!-- レスポンシブ -->
                    <div class="feature-card bg-gradient-to-br from-pink-500/10 to-transparent p-8 rounded-2xl border border-pink-500/20 group">
                        <div class="w-14 h-14 bg-gradient-to-br from-pink-600 to-pink-700 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">レスポンシブ対応</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">PC、タブレット、スマートフォンに最適化された UI。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 使い方セクション -->
        <section class="py-24 bg-gradient-to-br from-[#F3F3F2] to-gray-100 dark:from-gray-800 dark:to-gray-900 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-[#59B9C6]/5 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="text-center mb-20">
                    <span class="inline-block px-4 py-2 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-full text-sm font-semibold mb-4">How It Works</span>
                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">シンプルな3ステップ</h2>
                    <div class="section-divider mb-6"></div>
                    <p class="text-lg text-gray-600 dark:text-gray-300">今すぐ始められます</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="step-card text-center">
                        <div class="step-number w-20 h-20 bg-gradient-to-br from-[#59B9C6] to-[#4AA0AB] text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-6 shadow-xl">1</div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">アカウント作成</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">メールアドレスで簡単に登録。数分で完了します。</p>
                    </div>
                    <div class="step-card text-center">
                        <div class="step-number w-20 h-20 bg-gradient-to-br from-purple-600 to-purple-700 text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-6 shadow-xl">2</div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">タスクを追加</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">やることを入力するだけ。シンプルで直感的。</p>
                    </div>
                    <div class="step-card text-center">
                        <div class="step-number w-20 h-20 bg-gradient-to-br from-green-600 to-green-700 text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-6 shadow-xl">3</div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">AIで最適化</h3>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">自動分解で効率アップ。生産性が向上します。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTAセクション -->
        <section class="py-24 cta-gradient relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">今すぐ始めましょう</h2>
                <p class="text-xl text-white/90 mb-10 max-w-2xl mx-auto">アカウント作成は無料。数分で始められます。<br>今日からタスク管理を効率化しましょう。</p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-primary group inline-flex items-center px-10 py-5 bg-white text-[#59B9C6] rounded-xl hover:bg-gray-50 transition font-bold text-lg shadow-2xl hover:shadow-3xl">
                        無料で始める
                        <svg class="w-6 h-6 ml-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @endif
            </div>
        </section>

        <!-- フッター -->
        <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
                    <div>
                        <div class="flex items-center gap-2 mb-6">
                            <svg class="w-8 h-8 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                            </svg>
                            <span class="text-xl font-bold gradient-text">MyTeacher</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">AIがサポートする次世代タスク管理アプリ。学習効率を最大化します。</p>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-6 text-lg">機能</h3>
                        <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                            <li class="link-underline inline-block">AI タスク分解</li>
                            <li class="link-underline inline-block">グループ管理</li>
                            <li class="link-underline inline-block">実績レポート</li>
                            <li class="link-underline inline-block">タグ管理</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-6 text-lg">リンク</h3>
                        <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                            @if (Route::has('login'))
                                <li><a href="{{ route('login') }}" class="link-underline inline-block hover:text-[#59B9C6] transition">ログイン</a></li>
                            @endif
                            @if (Route::has('register'))
                                <li><a href="{{ route('register') }}" class="link-underline inline-block hover:text-[#59B9C6] transition">新規登録</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="section-divider mb-8"></div>
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-gray-600 dark:text-gray-400">
                    <p>&copy; {{ date('Y') }} MyTeacher. All rights reserved.</p>
                    <div class="flex gap-6">
                        <a href="#" class="hover:text-[#59B9C6] transition">プライバシーポリシー</a>
                        <a href="#" class="hover:text-[#59B9C6] transition">利用規約</a>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>