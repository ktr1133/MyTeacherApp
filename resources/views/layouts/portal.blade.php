{{-- filepath: /home/ktr/mtdev/laravel/resources/views/layouts/portal.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'ポータルサイト') - Famico</title>

        <!-- Favicons -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="alternate icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <meta name="theme-color" content="#59B9C6">

        <!-- SEO Meta Tags -->
        <meta name="description" content="@yield('meta_description', 'Famicoポータル - AIタスク管理アプリケーションの使い方ガイド、メンテナンス情報、お問い合わせ')">
        <meta name="keywords" content="@yield('meta_keywords', 'タスク管理,AI,教育,学習サポート,ガイド,FAQ')">
        
        <!-- OGP Meta Tags -->
        <meta property="og:title" content="@yield('og_title', 'Famico ポータルサイト')">
        <meta property="og:description" content="@yield('og_description', 'Famicoおよび関連アプリの総合情報サイト')">
        <meta property="og:type" content="website">
        <meta property="og:image" content="{{ asset('images/famico-logo-20251123041515.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/css/welcome.css'])
        @stack('styles')

        <!-- Dark Mode Script (フリッカー防止) -->
        <script>
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
                    <a href="{{ route('portal.home') }}" class="flex items-center gap-2 group">
                        <div class="relative">
                            <img src="{{ asset('images/famico-logo-20251123041515.png') }}" 
                                 alt="Famico Logo" 
                                 class="w-10 h-10 object-contain transition-transform group-hover:scale-110 duration-200">
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-600 rounded-full animate-ping"></div>
                        </div>
                        <span class="text-xl font-bold gradient-text">Famico Portal</span>
                    </a>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-6">
                        <!-- Apps Dropdown -->
                        <div class="relative group">
                            <button type="button" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm inline-flex items-center gap-1">
                                Apps
                                <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div class="absolute left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <a href="{{ url('/') }}" class="block px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-[#59B9C6] transition rounded-t-lg">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                                        </svg>
                                        <span class="font-medium">MyTeacher</span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">タスク管理アプリ</p>
                                </a>
                            </div>
                        </div>
                        <a href="{{ route('portal.guide.index') }}" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm">
                            使い方ガイド
                        </a>
                        <a href="{{ route('portal.maintenance') }}" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm">
                            メンテナンス
                        </a>
                        <a href="{{ route('portal.faq') }}" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm">
                            FAQ
                        </a>
                        <a href="{{ route('portal.contact') }}" class="link-underline text-gray-700 dark:text-gray-300 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition font-medium text-sm">
                            お問い合わせ
                        </a>
                        
                        <!-- Dark Mode Toggle -->
                        <button id="theme-toggle" type="button" class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                            </svg>
                        </button>

                        <!-- Login / Dashboard Button -->
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                タスクリスト
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-primary inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold text-sm">
                                Login
                            </a>
                        @endauth
                    </div>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-button" type="button" class="md:hidden p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu (Hidden by default) -->
                <div id="mobile-menu" class="hidden md:hidden pb-4">
                    <div class="flex flex-col gap-2">
                        <!-- Apps Dropdown (Mobile) -->
                        <button type="button" id="mobile-apps-toggle" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-left flex items-center justify-between">
                            <span>Apps</span>
                            <svg id="mobile-apps-icon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="mobile-apps-menu" class="hidden ml-4 space-y-1">
                            <a href="{{ url('/') }}" class="block px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                MyTeacher
                            </a>
                        </div>
                        <a href="{{ route('portal.guide.index') }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            使い方ガイド
                        </a>
                        <a href="{{ route('portal.maintenance') }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            メンテナンス
                        </a>
                        <a href="{{ route('portal.faq') }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            FAQ
                        </a>
                        <a href="{{ route('portal.contact') }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            お問い合わせ
                        </a>
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-4 py-2 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold text-center">
                                ダッシュボード
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] text-white rounded-lg hover:shadow-lg transition font-semibold text-center">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="pt-20 min-h-screen">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg" role="alert">
                        @if (session('error'))
                            <span class="block sm:inline">{{ session('error') }}</span>
                        @endif
                        @if ($errors->any())
                            <ul class="list-disc list-inside mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- About -->
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <img src="{{ asset('images/famico-logo-20251123041515.png') }}" 
                                 alt="Famico Logo" 
                                 class="w-8 h-8 object-contain">
                            <span class="text-lg font-bold gradient-text">Famico</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">GPT-4o-mini & Stable Diffusion搭載のAIタスク管理プラットフォーム</p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">クイックリンク</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li><a href="{{ route('portal.home') }}" class="hover:text-[#59B9C6] transition">ホーム</a></li>
                            <li><a href="{{ route('portal.guide.index') }}" class="hover:text-[#59B9C6] transition">使い方ガイド</a></li>
                            <li><a href="{{ route('portal.faq') }}" class="hover:text-[#59B9C6] transition">FAQ</a></li>
                            <li><a href="{{ route('portal.updates') }}" class="hover:text-[#59B9C6] transition">更新履歴</a></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">サポート</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li><a href="{{ route('portal.contact') }}" class="hover:text-[#59B9C6] transition">お問い合わせ</a></li>
                            <li><a href="{{ route('portal.maintenance') }}" class="hover:text-[#59B9C6] transition">メンテナンス情報</a></li>
                            <li><a href="{{ route('terms-of-service') }}" class="hover:text-[#59B9C6] transition">利用規約</a></li>
                            <li><a href="{{ route('privacy-policy') }}" class="hover:text-[#59B9C6] transition">プライバシーポリシー</a></li>
                        </ul>
                    </div>

                    <!-- Social Links -->
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">SNS</h3>
                        <div class="flex gap-3">
                            <a href="#" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-[#59B9C6]/10 dark:hover:bg-[#59B9C6]/20 transition">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            <a href="#" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-[#59B9C6]/10 dark:hover:bg-[#59B9C6]/20 transition">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                    <p>&copy; {{ date('Y') }} Famico. All rights reserved.</p>
                    <p class="mt-1">Powered by OpenAI GPT-4o-mini & Stable Diffusion</p>
                </div>
            </div>
        </footer>

        <!-- Scripts -->
        @vite('resources/js/app.js')
        
        <script>
            // Dark Mode Toggle
            const themeToggle = document.getElementById('theme-toggle');
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');

            // 初期表示設定
            if (document.documentElement.classList.contains('dark')) {
                lightIcon.classList.remove('hidden');
            } else {
                darkIcon.classList.remove('hidden');
            }

            themeToggle.addEventListener('click', function() {
                darkIcon.classList.toggle('hidden');
                lightIcon.classList.toggle('hidden');

                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
            });

            // Mobile Menu Toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });

            // Mobile Apps Dropdown
            const mobileAppsToggle = document.getElementById('mobile-apps-toggle');
            const mobileAppsMenu = document.getElementById('mobile-apps-menu');
            const mobileAppsIcon = document.getElementById('mobile-apps-icon');

            if (mobileAppsToggle) {
                mobileAppsToggle.addEventListener('click', function() {
                    mobileAppsMenu.classList.toggle('hidden');
                    mobileAppsIcon.classList.toggle('rotate-180');
                });
            }
        </script>

        @stack('scripts')
    </body>
</html>
