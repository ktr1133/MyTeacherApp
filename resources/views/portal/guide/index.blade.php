{{-- filepath: /home/ktr/mtdev/laravel/resources/views/portal/guide/index.blade.php --}}
@extends('layouts.portal')

@section('title', '使い方ガイド')
@section('meta_description', 'MyTeacherの使い方を詳しく解説するガイドページ')

@section('content')
<!-- Page Header -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl font-bold gradient-text mb-4">使い方ガイド</h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            MyTeacherの各機能を詳しく解説します
        </p>
    </div>
</section>

<!-- Quick Start -->
<section class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-5xl mx-auto">
        <div class="bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] rounded-2xl p-8 text-white mb-12">
            <div class="flex items-start gap-6">
                <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-2">クイックスタートガイド</h2>
                    <p class="text-lg opacity-90 mb-4">
                        初めての方でも5分で基本操作をマスターできます
                    </p>
                    <a href="#getting-started" class="inline-flex items-center px-4 py-2 bg-white text-[#59B9C6] rounded-lg hover:shadow-lg transition font-semibold">
                        今すぐ始める
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Guide Categories -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Getting Started -->
            <a href="{{ route('portal.guide.getting-started') }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">はじめに</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            アカウント登録からログイン、初期設定までの基本操作
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• アカウント登録</li>
                            <li>• ログイン方法</li>
                            <li>• プロフィール設定</li>
                            <li>• 基本画面の説明</li>
                        </ul>
                    </div>
                </div>
            </a>

            <!-- Task Management -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">タスク管理</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            タスクの作成・編集・削除、優先度設定、期限管理
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• タスクの作成方法</li>
                            <li>• 優先度の設定</li>
                            <li>• 期限の管理</li>
                            <li>• タスクの完了・削除</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Group Tasks -->
            <a href="{{ route('portal.features.group-tasks') }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">クエスト</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            複数のユーザーに同時にクエストを割り当てる機能
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• クエストの作成</li>
                            <li>• メンバーの選択</li>
                            <li>• 承認フロー</li>
                            <li>• 進捗の確認</li>
                        </ul>
                    </div>
                </div>
            </a>

            <!-- AI Decomposition -->
            <a href="{{ route('portal.features.ai-decomposition') }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-[#59B9C6]/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-[#59B9C6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">AIタスク分解</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            GPT-4o-miniを使って大きなタスクを自動分解
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• AI分解の使い方</li>
                            <li>• 分解結果の編集</li>
                            <li>• トークン消費について</li>
                            <li>• ベストプラクティス</li>
                        </ul>
                    </div>
                </div>
            </a>

            <!-- Teacher Avatar -->
            <a href="{{ route('portal.features.avatar') }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-pink-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">アバター</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            Stable Diffusionで生成されるAI教師キャラクター
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• アバターの作成</li>
                            <li>• 画像の編集・削除</li>
                            <li>• イベントコメント設定</li>
                            <li>• アバターの選択</li>
                        </ul>
                    </div>
                </div>
            </a>

            <!-- Token System -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">トークンシステム</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            AI機能の利用コストを管理
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• トークンとは</li>
                            <li>• トークンの購入方法</li>
                            <li>• アバター生成・月次レポート・タスク分解</li>
                            <li>• 消費履歴の確認</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Auto Schedule -->
            <a href="{{ route('portal.features.auto-schedule') }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">クエスト自動作成</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            定期的なタスクを自動生成して習慣化をサポート
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• スケジュールの設定</li>
                            <li>• 繰り返しパターン</li>
                            <li>• 祝日対応機能</li>
                            <li>• 担当者設定</li>
                        </ul>
                    </div>
                </div>
            </a>

            <!-- Monthly Report -->
            <a href="{{ route('portal.features.monthly-report') }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition group">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">月次レポート</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            成長を見える化、実績・統計をグラフで確認
                        </p>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>• レポートの見方</li>
                            <li>• PDF出力機能</li>
                            <li>• 成長グラフの読み方</li>
                            <li>• プラン別の機能差</li>
                        </ul>
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Video Tutorials (Placeholder) -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold gradient-text text-center mb-8">動画チュートリアル</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @for($i = 1; $i <= 3; $i++)
            <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 overflow-hidden hover:border-[#59B9C6] dark:hover:border-[#59B9C6] transition">
                <div class="aspect-video bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">チュートリアル #{{ $i }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Coming Soon...</p>
                </div>
            </div>
            @endfor
        </div>
    </div>
</section>

<!-- FAQ & Contact CTA -->
<section class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-6">
        <!-- FAQ -->
        <a href="{{ route('portal.faq') }}" class="group bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] hover:shadow-xl transition">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">よくある質問</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                ユーザーから寄せられる質問とその回答をまとめています
            </p>
        </a>

        <!-- Contact -->
        <a href="{{ route('portal.contact') }}" class="group bg-gradient-to-r from-[#59B9C6] to-[#3b82f6] rounded-lg p-6 text-white hover:shadow-xl transition">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold">お問い合わせ</h3>
            </div>
            <p class="opacity-90">
                解決しない問題やご要望はお問い合わせフォームからご連絡ください
            </p>
        </a>
    </div>
</section>
@endsection
