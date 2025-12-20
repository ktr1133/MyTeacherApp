<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicons -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="alternate icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#59B9C6">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS CDN (フォールバック - モバイルアクセス対応) -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            primary: '#59B9C6',
                        }
                    }
                }
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/guest.css'])

        <!-- Inline Styles (モバイルアクセス対応) -->
        <style>
            /* 背景グラデーション */
            .auth-gradient-bg {
                background: linear-gradient(135deg, #F3F3F2 0%, #ffffff 50%, #e5e7eb 100%);
            }
            .dark .auth-gradient-bg {
                background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #0f172a 100%);
            }

            /* グラスモーフィズムカード */
            .auth-card {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(20px) saturate(180%);
                -webkit-backdrop-filter: blur(20px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.5);
                box-shadow: 0 8px 32px 0 rgba(89, 185, 198, 0.15);
            }
            .dark .auth-card {
                background: rgba(31, 41, 55, 0.85);
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
            }

            /* フローティングアニメーション */
            @keyframes float-slow {
                0%, 100% { transform: translate(0, 0) rotate(0deg); }
                33% { transform: translate(30px, -30px) rotate(120deg); }
                66% { transform: translate(-20px, 20px) rotate(240deg); }
            }
            @keyframes float-medium {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50% { transform: translate(-20px, -20px) scale(1.1); }
            }
            .floating-decoration {
                animation: float-slow 20s ease-in-out infinite;
            }
            .floating-decoration:nth-child(2) {
                animation: float-medium 15s ease-in-out infinite;
                animation-delay: 2s;
            }

            /* 入力フィールド */
            .input-glow {
                background-color: white;
                color: #1f2937;
            }
            .dark .input-glow {
                background-color: rgb(55 65 81);
                color: white;
                border-color: rgb(75 85 99);
            }
            .input-glow:focus {
                box-shadow: 0 0 0 3px rgba(89, 185, 198, 0.1), 0 0 20px rgba(89, 185, 198, 0.2);
                border-color: #59B9C6;
            }
            .dark .input-glow:focus {
                box-shadow: 0 0 0 3px rgba(89, 185, 198, 0.2), 0 0 20px rgba(89, 185, 198, 0.3);
                border-color: #59B9C6;
            }

            /* ボタンアニメーション */
            .auth-button {
                position: relative;
                overflow: hidden;
                transition: all 0.3s ease;
            }
            .auth-button::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: translate(-50%, -50%);
                transition: width 0.6s, height 0.6s;
            }
            .auth-button:hover::before {
                width: 300px;
                height: 300px;
            }
        </style>

        <!-- Dark Mode Script -->
        <script>
            // ダークモードの初期化（フリッカー防止のためhead内で実行）
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Page Styles -->
        @stack('styles')
        @stack('scripts')
    </head>
    <body class="font-sans antialiased">
        {{ $slot }}
    </body>
</html>