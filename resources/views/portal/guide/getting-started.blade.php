@extends('layouts.portal')

@section('title', 'はじめに - 使い方ガイド')
@section('meta_description', 'MyTeacherの基本操作ガイド - アカウント登録からログイン、プロフィール設定、基本画面の使い方まで')
@section('og_title', 'はじめに - MyTeacher 使い方ガイド')

@push('styles')
    @vite(['resources/css/portal-common.css', 'resources/css/portal-features.css', 'resources/css/portal/guide-navigation.css'])
@endpush

@push('scripts')
    @vite(['resources/js/portal/guide-navigation.js'])
@endpush

@php
// セクション定義データ（ナビゲーションとカードで共有）
$sections = [
    [
        'id' => 'account-registration',
        'title' => 'アカウント登録',
        'description' => '無料で今すぐ始められます',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>',
        'color' => 'text-green-600 dark:text-green-400',
        'bgColor' => 'bg-green-500/10',
        'iconColor' => 'text-green-600 dark:text-green-400',
    ],
    [
        'id' => 'login',
        'title' => 'ログイン方法',
        'description' => 'かんたんログインとセキュリティ',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>',
        'color' => 'text-blue-600 dark:text-blue-400',
        'bgColor' => 'bg-blue-500/10',
        'iconColor' => 'text-blue-600 dark:text-blue-400',
    ],
    [
        'id' => 'profile-settings',
        'title' => 'プロフィール設定',
        'description' => 'テーマやアバターをカスタマイズ',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'color' => 'text-purple-600 dark:text-purple-400',
        'bgColor' => 'bg-purple-500/10',
        'iconColor' => 'text-purple-600 dark:text-purple-400',
    ],
    [
        'id' => 'basic-screens',
        'title' => '基本画面の説明',
        'description' => 'タスク一覧とメニューの使い方',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'color' => 'text-[#59B9C6]',
        'bgColor' => 'bg-[#59B9C6]/10',
        'iconColor' => 'text-[#59B9C6]',
    ],
];
@endphp

@section('content')
<!-- 背景装飾 -->
<div class="portal-bg-gradient">
    <div class="portal-bg-blob portal-bg-blob-primary" style="top: 15%; right: 10%; width: 400px; height: 400px;"></div>
    <div class="portal-bg-blob portal-bg-blob-purple" style="bottom: 20%; left: 10%; width: 450px; height: 450px; animation-delay: 0.7s;"></div>
</div>

<!-- パンくずリスト -->
<section class="px-4 sm:px-6 lg:px-8 py-6">
    <div class="portal-container">
        <nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('portal.home') }}" class="hover:text-[#59B9C6] transition">ポータル</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('portal.guide.index') }}" class="hover:text-[#59B9C6] transition">使い方ガイド</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white font-medium">はじめに</span>
        </nav>
    </div>
</section>

<!-- Hero -->
<section class="portal-section">
    <div class="portal-container">
        <div class="text-center mb-12">
            <div class="feature-icon-wrapper inline-flex mb-6" role="img" aria-label="再生アイコン">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold gradient-text mb-4 sm:mb-6">
                はじめに
            </h1>
            <p class="text-lg sm:text-xl text-gray-700 dark:text-gray-300 max-w-3xl mx-auto px-4">
                MyTeacherの基本操作を5分でマスターしましょう。<br class="hidden sm:inline">
                初めての方でも安心して使い始められます。
            </p>
        </div>
    </div>
</section>

<!-- モバイル用アコーディオンナビゲーション -->
@include('portal.guide.components.mobile-nav', ['sections' => $sections])

<!-- クイックナビゲーションカード -->
@include('portal.guide.components.quick-nav-cards', ['sections' => $sections])

<!-- 1. アカウント登録 -->
<section id="account-registration" data-section class="portal-section">
    <div class="portal-container">
        <div class="max-w-7xl mx-auto">
            <div class="md:flex md:gap-8">
                <!-- メインコンテンツ -->
                <div class="flex-1 md:max-w-4xl">
                    <h2 class="portal-section-title gradient-text flex items-center gap-3 sm:gap-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <span>アカウント登録</span>
                    </h2>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                        <div class="space-y-4 sm:space-y-6">
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-3 sm:mb-4">登録の流れ（所要時間: 約2分）</h3>
                                <div class="space-y-3 sm:space-y-4">
                                    @include('portal.guide.components.step-item', [
                                        'number' => '1',
                                        'bgColor' => 'bg-[#59B9C6]',
                                        'title' => 'トップページの「Free Start」をタップ',
                                        'content' => '画面右上の緑色のボタンからアカウント登録画面へ進みます。',
                                    ])

                                    @include('portal.guide.components.step-item', [
                                        'number' => '2',
                                        'bgColor' => 'bg-[#59B9C6]',
                                        'title' => '必要情報を入力',
                                        'content' => '<ul class="space-y-2">
                                            <li class="flex items-start gap-2">
                                                <span class="text-[#59B9C6] mt-1" aria-hidden="true">•</span>
                                                <span><strong>ユーザー名:</strong> 半角英数字、ハイフン、アンダースコア（3-20文字）</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-[#59B9C6] mt-1" aria-hidden="true">•</span>
                                                <span><strong>メールアドレス:</strong> 確認メール送信用</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-[#59B9C6] mt-1" aria-hidden="true">•</span>
                                                <span><strong>パスワード:</strong> 8文字以上（英大文字・小文字・数字・記号を含む）</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-[#59B9C6] mt-1" aria-hidden="true">•</span>
                                                <span><strong>テーマ選択:</strong> 大人向け または 子ども向け</span>
                                            </li>
                                        </ul>',
                                    ])

                                    @include('portal.guide.components.step-item', [
                                        'number' => '3',
                                        'bgColor' => 'bg-[#59B9C6]',
                                        'title' => '登録完了・ログイン',
                                        'content' => '「登録する」ボタンをタップすると、自動的にログインしてダッシュボードが表示されます。',
                                    ])
                                </div>
                            </div>

                            <!-- 情報ボックス -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 sm:p-4">
                                <div class="flex items-start gap-2 sm:gap-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-blue-900 dark:text-blue-300 mb-1 text-sm sm:text-base">テーマについて</p>
                                        <p class="text-xs sm:text-sm text-blue-800 dark:text-blue-400">
                                            <strong>大人向け:</strong> シンプルで落ち着いたデザイン<br>
                                            <strong>子ども向け:</strong> カラフルで楽しいデザイン、タップターゲット48px以上<br>
                                            ※テーマは後から変更できます
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- スクリーンショット -->
                    <div class="screenshot-wrapper">
                        <img src="{{ asset('images/portal/create-account.gif') }}" 
                             alt="アカウント登録画面のスクリーンショット" 
                             class="screenshot-img"
                             loading="lazy">
                    </div>
                </div>

                @include('portal.guide.components.sidebar-nav')
            </div>
        </div>
    </div>
</section>

<!-- 2. ログイン方法 -->
<section id="login" data-section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <div class="max-w-7xl mx-auto">
            <div class="md:flex md:gap-8">
                <div class="flex-1 md:max-w-4xl">
                    <h2 class="portal-section-title gradient-text flex items-center gap-3 sm:gap-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <span>ログイン方法</span>
                    </h2>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                        <div class="space-y-4 sm:space-y-6">
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-3 sm:mb-4">ログイン手順</h3>
                                <div class="space-y-3 sm:space-y-4">
                                    @include('portal.guide.components.step-item', [
                                        'number' => '1',
                                        'bgColor' => 'bg-blue-600',
                                        'title' => 'ログインページへアクセス',
                                        'content' => 'トップページの「Login」リンク、またはURLに直接アクセス（/login）',
                                    ])

                                    @include('portal.guide.components.step-item', [
                                        'number' => '2',
                                        'bgColor' => 'bg-blue-600',
                                        'title' => '認証情報を入力',
                                        'content' => '<ul class="space-y-2">
                                            <li class="flex items-start gap-2">
                                                <span class="text-blue-600 mt-1" aria-hidden="true">•</span>
                                                <span><strong>ユーザー名 または メールアドレス</strong></span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-blue-600 mt-1" aria-hidden="true">•</span>
                                                <span><strong>パスワード</strong></span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-blue-600 mt-1" aria-hidden="true">•</span>
                                                <span>（オプション）「ログイン状態を保持する」にチェック → 30日間自動ログイン</span>
                                            </li>
                                        </ul>',
                                    ])

                                    @include('portal.guide.components.step-item', [
                                        'number' => '3',
                                        'bgColor' => 'bg-blue-600',
                                        'title' => 'ログインボタンをタップ',
                                        'content' => '認証成功後、ダッシュボードへ自動遷移します。',
                                    ])
                                </div>
                            </div>

                            <!-- 警告ボックス -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 sm:p-4">
                                <div class="flex items-start gap-2 sm:gap-3">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-yellow-900 dark:text-yellow-300 mb-1 text-sm sm:text-base">パスワードを忘れた場合</p>
                                        <p class="text-xs sm:text-sm text-yellow-800 dark:text-yellow-400">
                                            ログイン画面の「パスワードをお忘れですか？」リンクから、メールアドレスを入力してパスワードリセット手続きを行ってください。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- スクリーンショット -->
                    <div class="screenshot-wrapper">
                        <img src="{{ asset('images/portal/loging.gif') }}" 
                             alt="ログイン画面のスクリーンショット" 
                             class="screenshot-img"
                             loading="lazy">
                    </div>
                </div>

                @include('portal.guide.components.sidebar-nav')
            </div>
        </div>
    </div>
</section>

<!-- 3. プロフィール設定 -->
<section id="profile-settings" data-section class="portal-section">
    <div class="portal-container">
        <div class="max-w-7xl mx-auto">
            <div class="md:flex md:gap-8">
                <div class="flex-1 md:max-w-4xl">
                    <h2 class="portal-section-title gradient-text flex items-center gap-3 sm:gap-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span>プロフィール設定</span>
                    </h2>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4 sm:p-6 md:p-8 mb-6 sm:mb-8">
                        <div class="space-y-4 sm:space-y-6">
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-3 sm:mb-4">プロフィール編集画面へのアクセス</h3>
                                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mb-3 sm:mb-4">
                                    ダッシュボード右上の「<svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> ユーザー名」をタップ → 「プロフィール設定」を選択
                                </p>
                            </div>

                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-3 sm:mb-4">設定可能な項目</h3>
                                <div class="space-y-3 sm:space-y-4">
                                    <div class="border-l-4 border-[#59B9C6] pl-3 sm:pl-4">
                                        <h4 class="font-bold text-gray-900 dark:text-white mb-2 text-sm sm:text-base">基本情報</h4>
                                        <ul class="space-y-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                            <li>• <strong>ユーザー名:</strong> 一意の識別子(変更可能、重複チェックあり)</li>
                                            <li>• <strong>メールアドレス:</strong> 通知受信先(変更可能)</li>
                                            <li>• <strong>グループ作成:</strong> 家族や友人とタスクを共有するグループを作成できます</li>
                                        </ul>
                                    </div>

                                    <div class="border-l-4 border-purple-600 pl-3 sm:pl-4">
                                        <h4 class="font-bold text-gray-900 dark:text-white mb-2 text-sm sm:text-base">テーマ設定</h4>
                                        <ul class="space-y-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                            <li>• <strong>大人向けテーマ:</strong> シンプルで落ち着いたデザイン</li>
                                            <li>• <strong>子ども向けテーマ:</strong> カラフルで楽しいデザイン、大きなボタン</li>
                                            <li>• いつでも切り替え可能</li>
                                        </ul>
                                    </div>

                                    <div class="border-l-4 border-blue-600 pl-3 sm:pl-4">
                                        <h4 class="font-bold text-gray-900 dark:text-white mb-2 text-sm sm:text-base">タイムゾーン設定</h4>
                                        <ul class="space-y-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                            <li>• <strong>Asia/Tokyo (JST):</strong> 日本標準時</li>
                                            <li>• タスクの期限管理、通知時刻に影響</li>
                                        </ul>
                                    </div>

                                    <div class="border-l-4 border-green-600 pl-3 sm:pl-4">
                                        <h4 class="font-bold text-gray-900 dark:text-white mb-2 text-sm sm:text-base">パスワード変更</h4>
                                        <ul class="space-y-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                            <li>• 現在のパスワードを入力後、新しいパスワードを設定</li>
                                            <li>• セキュリティ強度チェッカー搭載</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 情報ボックス -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 sm:p-4">
                        <div class="flex items-start gap-2 sm:gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-green-900 dark:text-green-300 mb-1 text-sm sm:text-base">おすすめ設定</p>
                                <p class="text-xs sm:text-sm text-green-800 dark:text-green-400">
                                    初回ログイン後は、テーマの確認をおすすめします。特に子ども向けテーマは、小学生のお子さんが使いやすいデザインになっています。家族でタスクを共有したい場合は、グループを作成しましょう。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- スクリーンショット -->
                    <div class="screenshot-wrapper">
                        <img src="{{ asset('images/portal/profile-edit.gif') }}" 
                             alt="プロフィール設定画面のスクリーンショット" 
                             class="screenshot-img"
                             loading="lazy">
                    </div>
                </div>

                @include('portal.guide.components.sidebar-nav')
            </div>
        </div>
    </div>
</section>

<!-- 4. 基本画面の説明 -->
<section id="basic-screens" data-section class="portal-section bg-white/50 dark:bg-gray-800/30">
    <div class="portal-container">
        <div class="max-w-7xl mx-auto">
            <div class="md:flex md:gap-8">
                <div class="flex-1 md:max-w-4xl">
                    <h2 class="portal-section-title gradient-text flex items-center gap-3 sm:gap-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span>基本画面の説明</span>
                    </h2>

                    <div class="space-y-6">
                        <!-- ダッシュボード -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                            <h3 class="text-2xl font-bold gradient-text mb-4">📊 ダッシュボード</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                ログイン後に最初に表示される画面。左側のサイドバーから各機能へアクセスできます。
                            </p>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-3">サイドバーメニュー</h4>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-[#59B9C6]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">タスクリスト / ToDo</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">未完了タスクの一覧と件数を表示。すべてのタスクを確認・管理できます</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-yellow-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">承認待ち</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">グループ管理者のみ表示。タスク承認やトークン購入リクエストの件数を表示</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-blue-600">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">タグ管理 / タグ</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">タスクのカテゴリ(「勉強」「お手伝い」等)を作成・編集できます</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-pink-600">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">教師アバター / サポートアバター</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">AI生成の応援キャラクターを作成・編集できます</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-green-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">実績</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">完了率、タスク達成状況、月次レポートを確認できます</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-amber-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">トークン / コイン</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">AI機能利用のトークン残高確認・購入。残高少ない場合は警告表示</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-indigo-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">サブスクリプション</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">グループ管理者のみ表示。月額プランの管理と支払い設定</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 flex-shrink-0 text-teal-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">はじめに / つかいかた</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">スタートガイド。基本操作や各機能の使い方を確認できます</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- タスク一覧 -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                            <h3 class="text-2xl font-bold gradient-text mb-4">📝 タスク一覧</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                サイドメニューの「タスク」から全タスクを確認・管理できます。
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <span class="text-2xl">🔍</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">絞り込み・検索</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">ステータス、優先度、タグで絞り込み可能</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="text-2xl">🏷️</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">タグ管理</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">カテゴリ分けでタスクを整理（「勉強」「お手伝い」等）</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="text-2xl">✅</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">タスク完了</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">チェックボックスをタップで完了、画像アップロードも可能</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ヘッダーメニュー -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                            <h3 class="text-2xl font-bold gradient-text mb-4">🎯 ヘッダーメニュー</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                画面上部のナビゲーションバーから主要機能へアクセスできます。
                            </p>
                            <div class="grid gap-3">
                                <div class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="w-10 h-10 bg-green-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">タスク登録</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">個人タスクを新規作成</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="w-10 h-10 bg-indigo-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">グループタスク登録</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">複数人に同時にタスクを割り当て</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="w-10 h-10 bg-blue-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">通知</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">タスク期限、承認依頼などの通知</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="w-10 h-10 bg-yellow-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">トークン残高</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">AI機能利用可能なトークン数</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="w-10 h-10 bg-purple-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">ユーザーメニュー</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">アカウント管理、ログアウト</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- スクリーンショット -->
                        <div class="screenshot-wrapper">
                            <img src="{{ asset('images/portal/nav-list.gif') }}" 
                                 alt="ダッシュボード画面のスクリーンショット" 
                                 class="screenshot-img"
                                 loading="lazy">
                        </div>
                    </div>
                </div>

                @include('portal.guide.components.sidebar-nav')
            </div>
        </div>
    </div>
</section>

<!-- 次のステップ -->
<section class="portal-section">
    <div class="portal-container">
        <div class="max-w-4xl mx-auto">
            <h2 class="portal-section-title text-center gradient-text mb-6 sm:mb-8">
                次のステップ
            </h2>

            <div class="grid gap-4 sm:gap-6 md:grid-cols-2">
                <!-- AIタスク分解カード -->
                <a href="{{ route('portal.features.ai-decomposition') }}" 
                   class="group block bg-gradient-to-br from-[#59B9C6] to-[#3b82f6] rounded-2xl p-6 sm:p-8 text-white hover:shadow-xl transition-all duration-300 hover:scale-105"
                   aria-label="AIタスク分解機能について詳しく見る">
                    <div class="flex items-center gap-3 sm:gap-4 mb-3 sm:mb-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0" aria-hidden="true">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold flex-1">AIタスク分解を使ってみる</h3>
                    </div>
                    <p class="opacity-90 text-sm sm:text-base">大きなタスクをAIが自動で分解。家族の時間を増やしましょう。</p>
                </a>

                <!-- 他のガイドカード -->
                <a href="{{ route('portal.guide.index') }}" 
                   class="group block bg-white dark:bg-gray-800 rounded-2xl border-2 border-gray-200 dark:border-gray-700 p-6 sm:p-8 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition-all duration-300 hover:scale-105"
                   aria-label="他の使い方ガイドを見る">
                    <div class="flex items-center gap-3 sm:gap-4 mb-3 sm:mb-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 bg-green-500/10 rounded-lg flex items-center justify-center flex-shrink-0" aria-hidden="true">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex-1">他のガイドを見る</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm sm:text-base">グループタスク、アバター、トークンシステムなどの使い方を学びましょう。</p>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ CTA -->
<section class="portal-section bg-gradient-to-r from-purple-600 to-blue-600" role="region" aria-label="お問い合わせ">
    <div class="portal-container">
        <div class="max-w-3xl mx-auto text-center text-white px-4">
            <h2 class="text-2xl sm:text-3xl font-bold mb-3 sm:mb-4">まだ分からないことがありますか?</h2>
            <p class="text-lg sm:text-xl opacity-90 mb-4 sm:mb-6">
                よくある質問や、お問い合わせフォームで解決できます
            </p>
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
                <!-- FAQボタン（48px以上確保） -->
                <a href="{{ route('portal.faq') }}" 
                   class="inline-flex items-center justify-center px-6 sm:px-8 py-4 bg-white text-purple-600 rounded-lg hover:shadow-lg transition-all duration-300 font-bold text-base sm:text-lg min-h-[48px]"
                   aria-label="よくある質問ページへ移動">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>よくある質問</span>
                </a>
                
                <!-- お問い合わせボタン（48px以上確保） -->
                <a href="{{ route('portal.contact') }}" 
                   class="inline-flex items-center justify-center px-6 sm:px-8 py-4 bg-white/20 backdrop-blur-sm text-white border-2 border-white rounded-lg hover:bg-white/30 transition-all duration-300 font-bold text-base sm:text-lg min-h-[48px]"
                   aria-label="お問い合わせフォームへ移動">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>お問い合わせ</span>
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
