<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $isChildTheme ?? false ? 'child-theme' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="api-base-url" content="{{ url('') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicons -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="alternate icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <meta name="theme-color" content="#59B9C6">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        @if($isChildTheme ?? false)
            {{-- 子ども向けフォント（丸ゴシック風） --}}
            <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800&display=swap" rel="stylesheet" />
        @else
            {{-- 大人向けフォント --}}
            <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @endif

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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/sidebar.css', 'resources/js/app.js'])
        
        <!-- Chart.js CDN -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
        
        {{-- サイドバー制御（Alpine.jsの代わり） --}}
        @vite(['resources/js/common/sidebar.js'])

        {{-- 子ども向けテーマの場合、child-theme.css を読み込む --}}
        @if($isChildTheme ?? false)
            @vite(['resources/css/child-theme.css'])
        @endif
        
        {{-- 認証済みユーザーのみ通知ポーリング読み込み --}}
        @auth
            @vite(['resources/js/common/notification-polling.js'])
            {{-- グループタスク詳細モーダル制御（全ページ共通） --}}
            @vite(['resources/js/dashboard/group-task-detail.js'])
        @endauth
        
        {{-- 花火エフェクト用 CDN（子ども向けのみ） --}}
        @if($isChildTheme ?? false)
            <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
        @endif
    </head>
    <body class="font-sans antialiased">
        {{-- 花火エフェクト用コンテナ（子ども向けのみ） --}}
        @if($isChildTheme ?? false)
            <div class="confetti-container"></div>
        @endif
        
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset
            
            <main>
                @if(isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>

        {{-- フラッシュメッセージコンポーネントを追加 --}}
        <x-flash-message />
        
        {{-- 汎用ダイアログコンポーネント（全ページ共通） --}}
        <x-alert-dialog />
        <x-confirm-dialog />

        {{-- アバターウィジェット（全ページ共通） --}}
        @auth
            @php
                $avatar = auth()->user()->teacherAvatar;
            @endphp
            @include('avatars.components.avatar-widget', ['avatar' => $avatar])
        @endauth
        
        <!-- アバターコントローラー -->
        @vite(['resources/js/avatar/avatar-controller.js'])

        <!-- Page Scripts -->
        @stack('scripts')
    </body>
</html>