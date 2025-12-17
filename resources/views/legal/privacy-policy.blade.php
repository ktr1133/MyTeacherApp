<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プライバシーポリシー - My Teacher</title>
    <meta name="description" content="My Teacherのプライバシーポリシー。個人情報の取り扱い、AI利用、第三者提供、未成年者対応について説明します。">
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            header, footer { display: none; }
            .no-print { display: none; }
            body { background: white !important; color: black !important; }
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen">
    <!-- ヘッダー -->
    <header class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 sticky top-0 z-10 no-print">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <!-- ロゴ -->
            <div class="flex items-center space-x-2">
                <svg class="w-8 h-8 text-[#59B9C6]" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
                <span class="text-xl font-bold bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
                    MyTeacher
                </span>
            </div>
            
            <!-- ナビゲーション -->
            <nav class="flex items-center space-x-4">
                <a href="{{ url('/') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition-colors">
                    トップへ戻る
                </a>
                <a href="{{ route('terms-of-service') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition-colors">
                    利用規約
                </a>
            </nav>
        </div>
    </header>
    
    <!-- メインコンテンツ -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-16">
        <!-- タイトル -->
        <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
            プライバシーポリシー
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-8">
            最終更新日: 2025年12月16日
        </p>
        
        <!-- 目次 -->
        <nav class="mb-8 p-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 no-print">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">目次</h2>
            <ol class="space-y-2 text-sm">
                <li><a href="#intro" class="text-[#59B9C6] hover:underline">1. はじめに</a></li>
                <li><a href="#operator" class="text-[#59B9C6] hover:underline">2. 事業者情報</a></li>
                <li><a href="#collection" class="text-[#59B9C6] hover:underline">3. 収集する個人情報</a></li>
                <li><a href="#usage" class="text-[#59B9C6] hover:underline">4. 利用目的</a></li>
                <li><a href="#third-party" class="text-[#59B9C6] hover:underline">5. 第三者提供（外部サービス連携）</a></li>
                <li><a href="#retention" class="text-[#59B9C6] hover:underline">6. データ保持期間</a></li>
                <li><a href="#minors" class="text-[#59B9C6] hover:underline">7. 未成年者対応（COPPA対応）</a></li>
                <li><a href="#international" class="text-[#59B9C6] hover:underline">8. 国際データ転送・GDPR対応</a></li>
                <li><a href="#cookies" class="text-[#59B9C6] hover:underline">9. Cookie・トラッキング</a></li>
                <li><a href="#security" class="text-[#59B9C6] hover:underline">10. セキュリティ対策</a></li>
                <li><a href="#rights" class="text-[#59B9C6] hover:underline">11. お客様の権利</a></li>
                <li><a href="#changes" class="text-[#59B9C6] hover:underline">12. プライバシーポリシーの変更</a></li>
                <li><a href="#contact" class="text-[#59B9C6] hover:underline">13. お問い合わせ</a></li>
            </ol>
        </nav>
        
        <!-- 本文 -->
        <div class="prose dark:prose-invert max-w-none">
            <!-- 1. はじめに -->
            <section id="intro" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">1. はじめに</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4 leading-relaxed">
                    My Teacher（以下「本サービス」）は、個人が運営するAI支援タスク管理・教育支援アプリケーションです。当方は、お客様の個人情報保護を最優先とし、個人情報保護法、COPPA（米国児童オンラインプライバシー保護法）、GDPR（EU一般データ保護規則）を遵守し、以下のとおりプライバシーポリシーを定めます。
                </p>
            </section>
            
            <!-- 2. 事業者情報 -->
            <section id="operator" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">2. 事業者情報</h2>
                <ul class="list-none space-y-2 text-gray-700 dark:text-gray-300">
                    <li><strong class="text-gray-900 dark:text-white">サービス名:</strong> My Teacher</li>
                    <li><strong class="text-gray-900 dark:text-white">運営者:</strong> 個人</li>
                    <li><strong class="text-gray-900 dark:text-white">所在地:</strong> 〒133-0061 東京都江戸川区篠崎町4-26-14 タブララサA号室</li>
                    <li><strong class="text-gray-900 dark:text-white">お問い合わせ:</strong> <a href="mailto:famicoapp@gmail.com" class="text-[#59B9C6] hover:underline">famicoapp@gmail.com</a></li>
                </ul>
            </section>
            
            <!-- 3. 収集する個人情報 -->
            <section id="collection" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">3. 収集する個人情報</h2>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">3.1 アカウント情報</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">本サービスのご利用にあたり、以下の情報を収集します:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>メールアドレス:</strong> アカウント管理、通知送信</li>
                    <li><strong>パスワード:</strong> 認証（ハッシュ化して保存）</li>
                    <li><strong>ユーザー名:</strong> 表示名、AI生成アバターのベース情報</li>
                    <li><strong>生年月日:</strong> 年齢確認、未成年者対応（任意。13歳未満の場合は必須）</li>
                    <li><strong>プロフィール画像:</strong> ユーザー識別（任意アップロード）</li>
                    <li><strong>グループメンバー情報:</strong> グループタスク管理機能利用時</li>
                </ul>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                    <strong>保存場所:</strong> PostgreSQL（AWS RDS - 日本リージョン）
                </p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">3.2 タスク・行動データ</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">本サービスの機能提供のため、以下のデータを収集します:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>タスク情報:</strong> タイトル、説明文、タグ、完了状況、スケジュール情報</li>
                    <li><strong>タスク添付画像:</strong> タスク作成・承認時にアップロードされた画像</li>
                    <li><strong>タスク履歴:</strong> 作成日時、完了日時、承認状況</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">3.3 AI利用データ</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">
                    本サービスでは、以下のAIサービスを利用しており、お客様のデータが米国に送信されます:
                </p>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mb-4">
                    <p class="text-yellow-800 dark:text-yellow-300 text-sm font-semibold">
                        ⚠️ 重要: AI利用により、お客様のデータが米国に転送されます
                    </p>
                </div>
                
                <h4 class="text-lg font-semibold mt-4 mb-2 text-gray-900 dark:text-white">OpenAI API（米国）</h4>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4 mb-3">
                    <li><strong>送信データ:</strong> タスクタイトル、説明文</li>
                    <li><strong>利用目的:</strong> タスク自動分解機能の提供</li>
                    <li><strong>保存期間:</strong> OpenAI側では30日間保存（学習には使用しない）</li>
                    <li><strong>プライバシーポリシー:</strong> <a href="https://openai.com/policies/privacy-policy" target="_blank" class="text-[#59B9C6] hover:underline">OpenAI Privacy Policy</a></li>
                </ul>
                
                <h4 class="text-lg font-semibold mt-4 mb-2 text-gray-900 dark:text-white">Replicate API（米国）</h4>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>送信データ:</strong> アバター生成プロンプト（ユーザー名、キャラクター設定）</li>
                    <li><strong>利用目的:</strong> AI教師アバター画像の生成</li>
                    <li><strong>プライバシーポリシー:</strong> <a href="https://replicate.com/privacy" target="_blank" class="text-[#59B9C6] hover:underline">Replicate Privacy</a></li>
                </ul>
            </section>
            
            <!-- 4. 利用目的 -->
            <section id="usage" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">4. 利用目的</h2>
                
                <h3 class="text-xl font-semibold mt-4 mb-3 text-gray-900 dark:text-white">4.1 サービス提供</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li>タスク管理機能の提供</li>
                    <li>AIによるタスク自動分解</li>
                    <li>AI教師アバターの生成</li>
                    <li>グループタスク管理・承認フロー</li>
                    <li>レポート・統計情報の生成</li>
                    <li>プッシュ通知の送信</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">4.2 サービス改善</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>AIモデルの精度向上:</strong> タスク分解結果、アバター生成結果を分析（個人識別情報は除外）</li>
                    <li><strong>機能改善:</strong> タスク完了率、利用傾向等を集計し、UIUXの最適化に活用</li>
                    <li><strong>不具合修正:</strong> エラーログ、デバイス情報を利用した原因調査</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">4.3 統計情報の第三者提供</h3>
                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4">
                    <p class="text-blue-800 dark:text-blue-300 text-sm">
                        当方は、<strong>個人を特定できない形式で匿名化・集計処理したデータ</strong>を第三者に提供する場合があります。
                        氏名、メールアドレス、ユーザー名等の個人識別情報は一切含まれません。
                    </p>
                </div>
            </section>
            
            <!-- 5. 第三者提供 -->
            <section id="third-party" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">5. 第三者提供（外部サービス連携）</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4">本サービスは、機能提供のため以下の外部サービスにデータを送信します:</p>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">サービス名</th>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">提供国</th>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">提供データ</th>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">目的</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">OpenAI</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">米国</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">タスクタイトル、説明文</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">タスク自動分解</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">Replicate</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">米国</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">アバター生成プロンプト</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">アバター画像生成</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">Stripe</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">米国</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">クレジットカード情報</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">決済処理</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">Firebase (Google)</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">米国</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">Push通知トークン</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">プッシュ通知</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">AWS</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">日本</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">全データ</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">インフラ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- 6. データ保持期間 -->
            <section id="retention" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">6. データ保持期間</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">データ種別</th>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">保持期間</th>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900 dark:text-white">削除方法</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">アカウント情報</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">削除後 <strong class="text-[#59B9C6]">90日間</strong></td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">自動削除バッチ（日次実行）</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">タスクデータ</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">削除後 <strong class="text-[#59B9C6]">90日間</strong></td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">自動削除バッチ（日次実行）</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">画像ファイル（S3）</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">削除後 <strong class="text-[#59B9C6]">90日間</strong></td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">自動削除バッチ（日次実行）</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">ログデータ</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">最大 <strong class="text-[#59B9C6]">90日間</strong></td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">自動削除（CloudWatch）</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mt-4">
                    <p class="text-green-800 dark:text-green-300 text-sm">
                        <strong>物理削除の実施:</strong> アカウント削除後90日が経過すると、関連する全てのデータ（ユーザー情報、タスク、画像ファイル等）を物理削除します。この処理は自動化されており、日次バッチで実行されます。
                    </p>
                </div>
            </section>
            
            <!-- 7. 未成年者対応 -->
            <section id="minors" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">7. 未成年者対応（COPPA対応）</h2>
                
                <h3 class="text-xl font-semibold mt-4 mb-3 text-gray-900 dark:text-white">7.1 対象年齢</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    本サービスは <strong class="text-[#59B9C6]">全年齢対応</strong> です。13歳未満の児童もご利用いただけますが、<strong class="text-red-600 dark:text-red-400">保護者の同意が必須</strong> です。
                </p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">7.2 保護者同意プロセス</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">13歳未満のお客様がアカウント登録される場合、以下のプロセスを経て保護者の同意を取得します:</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li>登録時に生年月日を入力</li>
                    <li>13歳未満と判定された場合、保護者のメールアドレスを入力</li>
                    <li>保護者宛に同意確認メールを送信</li>
                    <li>保護者が同意リンクをクリックし、本ポリシーおよび利用規約に同意</li>
                    <li>同意確認後、アカウントが有効化されます</li>
                </ol>
                
                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mt-4">
                    <p class="text-red-800 dark:text-red-300 text-sm">
                        <strong>未同意時の対応:</strong> 保護者同意が得られない場合、アカウントは仮登録状態となり、ログインできません。同意依頼メール送信後 <strong>7日以内</strong> に同意がない場合、アカウントは自動削除されます。
                    </p>
                </div>
            </section>
            
            <!-- 8. 国際データ転送・GDPR対応 -->
            <section id="international" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">8. 国際データ転送・GDPR対応</h2>
                
                <h3 class="text-xl font-semibold mt-4 mb-3 text-gray-900 dark:text-white">8.1 国際データ転送</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">
                    本サービスは、以下の理由により、お客様のデータを日本国外（主に米国）に転送します:
                </p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>法的根拠:</strong> サービス提供に必要な契約履行のための移転</li>
                    <li><strong>転送先:</strong> OpenAI（米国）、Replicate（米国）、Stripe（米国）、Firebase（米国）</li>
                    <li><strong>保護措置:</strong> 各サービスプロバイダーのプライバシーポリシーおよびセキュリティ対策に準拠</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">8.2 GDPR対応（EU圏ユーザー向け）</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">EU圏からアクセスされるお客様には、以下の権利を保証します:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>アクセス権:</strong> ご自身の個人データの開示を請求できます</li>
                    <li><strong>訂正権:</strong> 誤りがある場合、訂正を請求できます</li>
                    <li><strong>削除権（Right to be Forgotten):</strong> アカウント削除により全データの削除を請求できます</li>
                    <li><strong>データポータビリティ権:</strong> JSON形式でのエクスポート（将来実装予定）</li>
                    <li><strong>異議申し立て権:</strong> データ処理に異議がある場合、お問い合わせください</li>
                </ul>
            </section>
            
            <!-- 9. Cookie・トラッキング -->
            <section id="cookies" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">9. Cookie・トラッキング</h2>
                <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <p class="text-gray-700 dark:text-gray-300">
                        <strong>現在の状況:</strong> 現在、本サービスではCookieおよびトラッキングツール（Google Analytics等）を使用していません。
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-3">
                        <strong>将来の導入予定:</strong> Google Analyticsを導入する際は、本ポリシーを更新し、Cookie同意バナーを表示します。
                    </p>
                </div>
            </section>
            
            <!-- 10. セキュリティ対策 -->
            <section id="security" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">10. セキュリティ対策</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-3">当方は、お客様の個人情報を保護するため、以下の対策を実施しています:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong>パスワード:</strong> bcryptによるハッシュ化</li>
                    <li><strong>通信:</strong> HTTPS/TLS暗号化</li>
                    <li><strong>データベース:</strong> AWS RDSによるバックアップ・暗号化</li>
                    <li><strong>アクセス制限:</strong> IAMロール、最小権限の原則</li>
                    <li><strong>監視:</strong> CloudWatch Logsによる異常検知</li>
                </ul>
            </section>
            
            <!-- 11. お客様の権利 -->
            <section id="rights" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">11. お客様の権利</h2>
                <ul class="space-y-4">
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">データ開示請求</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            お客様は、当方が保有するご自身の個人データの開示を請求できます。
                            <a href="mailto:famicoapp@gmail.com" class="text-[#59B9C6] hover:underline">famicoapp@gmail.com</a> までご連絡ください。
                        </p>
                    </li>
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">訂正・削除請求</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            お客様は、ご自身の個人データの訂正・削除を請求できます。アプリ内「プロフィール編集」機能またはお問い合わせ窓口をご利用ください。
                        </p>
                    </li>
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">アカウント削除</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            お客様は、いつでもアカウントを削除できます。削除後90日以内に全てのデータが物理削除されます。
                        </p>
                    </li>
                </ul>
            </section>
            
            <!-- 12. プライバシーポリシーの変更 -->
            <section id="changes" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">12. プライバシーポリシーの変更</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-3">当方は、本ポリシーを変更する場合があります。変更時は、以下の方法で通知します:</p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4 mb-4">
                    <li>アプリ内通知</li>
                    <li>登録メールアドレスへのメール送信</li>
                    <li>本ページ冒頭の「最終更新日」を更新</li>
                </ul>
                <p class="text-gray-700 dark:text-gray-300">
                    重要な変更の場合は、変更内容に同意いただけない場合はサービス利用を停止してくださいとお願いすることがあります。
                </p>
            </section>
            
            <!-- 13. お問い合わせ -->
            <section id="contact" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">13. お問い合わせ</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4">本ポリシーに関するご質問、個人データの開示・訂正・削除請求は、以下の窓口までご連絡ください:</p>
                <ul class="list-none space-y-2 text-gray-700 dark:text-gray-300">
                    <li><strong class="text-gray-900 dark:text-white">メールアドレス:</strong> <a href="mailto:famicoapp@gmail.com" class="text-[#59B9C6] hover:underline">famicoapp@gmail.com</a></li>
                    <li><strong class="text-gray-900 dark:text-white">対応時間:</strong> 平日 10:00〜18:00（土日祝日を除く）</li>
                    <li><strong class="text-gray-900 dark:text-white">回答期限:</strong> ご連絡から7営業日以内</li>
                </ul>
            </section>
        </div>
    </main>
    
    <!-- フッター -->
    <footer class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 mt-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p>© 2025 My Teacher. All rights reserved.</p>
                    <p class="mt-1">最終更新日: 2025年12月16日</p>
                </div>
                <div class="flex space-x-6">
                    <a href="{{ url('/') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] transition-colors">
                        トップ
                    </a>
                    <a href="{{ route('terms-of-service') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] transition-colors">
                        利用規約
                    </a>
                    <a href="mailto:famicoapp@gmail.com" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] transition-colors">
                        お問い合わせ
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
