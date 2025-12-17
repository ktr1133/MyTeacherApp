<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>利用規約 - My Teacher</title>
    <meta name="description" content="My Teacherの利用規約。サービス内容、禁止事項、有料サービス、免責事項について説明します。">
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
                <a href="{{ route('privacy-policy') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] dark:hover:text-[#59B9C6] transition-colors">
                    プライバシーポリシー
                </a>
            </nav>
        </div>
    </header>
    
    <!-- メインコンテンツ -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-16">
        <!-- タイトル -->
        <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-[#59B9C6] to-purple-600 bg-clip-text text-transparent">
            利用規約
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-8">
            最終更新日: 2025年12月16日
        </p>
        
        <!-- 前文 -->
        <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500">
            <p class="text-blue-800 dark:text-blue-300 text-sm">
                この利用規約（以下「本規約」）は、個人（以下「当方」）が運営するMy Teacher（以下「本サービス」）のご利用条件を定めるものです。本サービスをご利用いただくには、本規約に同意いただく必要があります。
            </p>
        </div>
        
        <!-- 目次 -->
        <nav class="mb-8 p-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 no-print">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">目次</h2>
            <ol class="space-y-2 text-sm">
                <li><a href="#definitions" class="text-[#59B9C6] hover:underline">第1条 定義</a></li>
                <li><a href="#agreement" class="text-[#59B9C6] hover:underline">第2条 本規約への同意</a></li>
                <li><a href="#registration" class="text-[#59B9C6] hover:underline">第3条 アカウント登録</a></li>
                <li><a href="#account-management" class="text-[#59B9C6] hover:underline">第4条 アカウント管理</a></li>
                <li><a href="#services" class="text-[#59B9C6] hover:underline">第5条 サービス内容</a></li>
                <li><a href="#prohibited" class="text-[#59B9C6] hover:underline">第6条 禁止事項</a></li>
                <li><a href="#paid-services" class="text-[#59B9C6] hover:underline">第7条 有料サービス</a></li>
                <li><a href="#intellectual-property" class="text-[#59B9C6] hover:underline">第8条 知的財産権</a></li>
                <li><a href="#disclaimer" class="text-[#59B9C6] hover:underline">第9条 免責事項</a></li>
                <li><a href="#termination" class="text-[#59B9C6] hover:underline">第10条 サービスの停止・解約</a></li>
                <li><a href="#changes" class="text-[#59B9C6] hover:underline">第11条 規約の変更</a></li>
                <li><a href="#governing-law" class="text-[#59B9C6] hover:underline">第12条 準拠法・管轄裁判所</a></li>
                <li><a href="#contact" class="text-[#59B9C6] hover:underline">第13条 お問い合わせ</a></li>
            </ol>
        </nav>
        
        <!-- 本文 -->
        <div class="prose dark:prose-invert max-w-none">
            <!-- 第1条 定義 -->
            <section id="definitions" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第1条（定義）</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-3">本規約において、以下の用語は以下の意味を有します:</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong class="text-gray-900 dark:text-white">本サービス:</strong> 当方が提供するAI支援タスク管理・教育支援アプリケーション「My Teacher」（Webアプリおよびモバイルアプリ）</li>
                    <li><strong class="text-gray-900 dark:text-white">ユーザー:</strong> 本サービスにアカウント登録し、本規約に同意した個人</li>
                    <li><strong class="text-gray-900 dark:text-white">コンテンツ:</strong> ユーザーが本サービスに投稿・アップロードしたテキスト、画像、その他のデータ</li>
                    <li><strong class="text-gray-900 dark:text-white">AI生成コンテンツ:</strong> OpenAI、Replicate等のAIサービスを利用して生成されたテキスト、画像</li>
                    <li><strong class="text-gray-900 dark:text-white">トークン:</strong> 本サービス内で使用する仮想通貨（AI機能利用に必要）</li>
                </ol>
            </section>
            
            <!-- 第2条 本規約への同意 -->
            <section id="agreement" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第2条（本規約への同意）</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li>ユーザーは、本規約に同意の上、本サービスをご利用いただきます。</li>
                    <li>ユーザーが未成年者である場合、保護者の同意を得た上でご利用ください。</li>
                    <li>本規約に同意いただけない場合、本サービスをご利用いただけません。</li>
                </ol>
            </section>
            
            <!-- 第3条 アカウント登録 -->
            <section id="registration" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第3条（アカウント登録）</h2>
                <ol class="list-decimal list-inside space-y-3 text-gray-700 dark:text-gray-300 ml-4">
                    <li>ユーザーは、当方が指定する方法でアカウント登録を行います。</li>
                    <li>登録時に提供いただく情報（メールアドレス、ユーザー名、パスワード等）は、正確かつ最新のものである必要があります。</li>
                    <li>以下に該当する場合、当方はアカウント登録を承認しない、または登録後にアカウントを削除する場合があります:
                        <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                            <li>登録情報に虚偽、誤記、記入漏れがある場合</li>
                            <li>過去に本規約違反によりアカウントを削除されたことがある場合</li>
                            <li>未成年者が保護者の同意なく登録した場合</li>
                            <li>その他、当方が不適切と判断した場合</li>
                        </ul>
                    </li>
                </ol>
            </section>
            
            <!-- 第4条 アカウント管理 -->
            <section id="account-management" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第4条（アカウント管理）</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li>ユーザーは、自己のアカウント情報（メールアドレス、パスワード等）を適切に管理する責任を負います。</li>
                    <li>ユーザーは、パスワードを第三者に開示・共有してはなりません。</li>
                    <li>アカウントの不正利用が発覚した場合、ユーザーは直ちに当方に通知し、当方の指示に従ってください。</li>
                    <li>アカウントの不正利用により生じた損害について、当方は一切の責任を負いません。</li>
                </ol>
            </section>
            
            <!-- 第5条 サービス内容 -->
            <section id="services" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第5条（サービス内容）</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-3">本サービスは、以下の機能を提供します:</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong class="text-gray-900 dark:text-white">タスク管理機能:</strong> タスクの作成、編集、削除、完了管理</li>
                    <li><strong class="text-gray-900 dark:text-white">AIタスク分解機能:</strong> OpenAI APIを利用した自動タスク分解</li>
                    <li><strong class="text-gray-900 dark:text-white">AI教師アバター機能:</strong> Replicate APIを利用したキャラクター画像生成</li>
                    <li><strong class="text-gray-900 dark:text-white">グループタスク機能:</strong> 複数ユーザーへの同時タスク割当、承認フロー</li>
                    <li><strong class="text-gray-900 dark:text-white">レポート機能:</strong> タスク完了状況の統計・可視化</li>
                    <li><strong class="text-gray-900 dark:text-white">トークンシステム:</strong> AI機能利用に必要なトークンの購入・管理</li>
                    <li><strong class="text-gray-900 dark:text-white">プッシュ通知:</strong> タスク期限、承認依頼等の通知</li>
                </ol>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                    ※機能の追加・変更・廃止は、当方の裁量により行います。
                </p>
            </section>
            
            <!-- 第6条 禁止事項 -->
            <section id="prohibited" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第6条（禁止事項）</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-3">ユーザーは、本サービスの利用にあたり、以下の行為を行ってはなりません:</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li><strong class="text-gray-900 dark:text-white">不正アクセス:</strong> 第三者のアカウントへの不正ログイン、パスワードクラッキング</li>
                    <li><strong class="text-gray-900 dark:text-white">システムへの攻撃:</strong> DDoS攻撃、SQLインジェクション、クロスサイトスクリプティング等</li>
                    <li><strong class="text-gray-900 dark:text-white">不正利用:</strong> APIの過度な呼び出し、自動化ツールによるスクレイピング</li>
                    <li><strong class="text-gray-900 dark:text-white">誹謗中傷:</strong> 第三者を誹謗中傷するコンテンツの投稿</li>
                    <li><strong class="text-gray-900 dark:text-white">著作権侵害:</strong> 第三者の著作物を無断で投稿・アップロード</li>
                    <li><strong class="text-gray-900 dark:text-white">個人情報漏洩:</strong> 第三者の個人情報を無断で投稿・公開</li>
                    <li><strong class="text-gray-900 dark:text-white">犯罪行為:</strong> 違法行為、犯罪行為を助長するコンテンツの投稿</li>
                    <li><strong class="text-gray-900 dark:text-white">営利目的利用:</strong> 当方の許可なく、本サービスを営利目的で利用すること</li>
                    <li><strong class="text-gray-900 dark:text-white">アカウント譲渡:</strong> 自己のアカウントを第三者に譲渡・貸与・売買すること</li>
                    <li><strong class="text-gray-900 dark:text-white">その他:</strong> 上記に準じる行為、または当方が不適切と判断する行為</li>
                </ol>
                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mt-4">
                    <p class="text-red-800 dark:text-red-300 text-sm">
                        <strong>違反が確認された場合、当方は事前通知なくアカウントを削除する場合があります。</strong>
                    </p>
                </div>
            </section>
            
            <!-- 第7条 有料サービス -->
            <section id="paid-services" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第7条（有料サービス）</h2>
                
                <h3 class="text-xl font-semibold mt-4 mb-3 text-gray-900 dark:text-white">7.1 トークン購入</h3>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li>ユーザーは、AI機能利用に必要なトークンを購入できます。</li>
                    <li>決済は、Stripe（米国）を通じて行われます。クレジットカード情報はStripeが管理し、当方は保存しません。</li>
                    <li>トークン価格は、当方が定める価格表に従います。価格変更時は事前に通知します。</li>
                </ol>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">7.2 返金ポリシー</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">以下の場合を除き、購入済みトークンの返金には応じません:</p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li>当方のシステム障害により、購入したトークンが正常に付与されなかった場合</li>
                    <li>決済エラーにより二重課金が発生した場合</li>
                </ul>
                <p class="text-gray-700 dark:text-gray-300 mt-3">
                    返金を希望される場合は、お問い合わせ窓口（<a href="mailto:famicoapp@gmail.com" class="text-[#59B9C6] hover:underline">famicoapp@gmail.com</a>）までご連絡ください。
                </p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">7.3 無料トークン</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    当方は、ユーザーに対して月次で無料トークンを付与する場合があります。無料トークンの付与条件・数量は、当方の裁量により変更できます。
                </p>
            </section>
            
            <!-- 第8条 知的財産権 -->
            <section id="intellectual-property" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第8条（知的財産権）</h2>
                
                <h3 class="text-xl font-semibold mt-4 mb-3 text-gray-900 dark:text-white">8.1 本サービスの知的財産権</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    本サービスに関する知的財産権（著作権、商標権、特許権等）は、当方または正当な権利者に帰属します。
                </p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">8.2 ユーザーコンテンツの知的財産権</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">
                    ユーザーが本サービスに投稿したコンテンツの著作権は、ユーザーに帰属します。ただし、ユーザーは当方に対し、以下の権利を許諾するものとします:
                </p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li>本サービス提供のための複製、翻案、公衆送信</li>
                    <li>AI機能向上のための利用（個人識別情報は除く）</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">8.3 AI生成コンテンツの知的財産権</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    AI生成されたアバター画像等の著作権は、ユーザーに帰属します。ただし、生成プロンプト、生成設定等は当方が保有します。
                </p>
            </section>
            
            <!-- 第9条 免責事項 -->
            <section id="disclaimer" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第9条（免責事項）</h2>
                
                <ol class="space-y-4">
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">1. サービスの中断・停止</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-2">
                            当方は、以下の場合にサービスを中断・停止する場合があります。この場合、当方は一切の責任を負いません:
                        </p>
                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                            <li>システムメンテナンス、緊急メンテナンス</li>
                            <li>サーバー障害、ネットワーク障害</li>
                            <li>天災、停電、戦争、テロ、感染症等の不可抗力</li>
                            <li>その他、当方の責によらない事由</li>
                        </ul>
                    </li>
                    
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">2. AI生成結果の精度</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            当方は、AI生成されたタスク分解結果、アバター画像等の精度、正確性、有用性を保証しません。生成結果の利用はユーザーの自己責任とします。
                        </p>
                    </li>
                    
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">3. データ損失</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            当方は、システム障害、不正アクセス等によりユーザーデータが損失した場合でも、一切の責任を負いません。ユーザーは重要なデータをバックアップしてください。
                        </p>
                    </li>
                    
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">4. 第三者サービス</h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            本サービスは、OpenAI、Replicate、Stripe、Firebase等の第三者サービスを利用しています。これらのサービスの障害・不具合により生じた損害について、当方は一切の責任を負いません。
                        </p>
                    </li>
                    
                    <li>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">5. 損害賠償の制限</h3>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4">
                            <p class="text-yellow-800 dark:text-yellow-300 text-sm">
                                当方の責に帰すべき事由により損害が発生した場合でも、当方の賠償責任は <strong>直近3ヶ月間にユーザーが支払った利用料金の総額</strong> を上限とします。
                            </p>
                        </div>
                    </li>
                </ol>
            </section>
            
            <!-- 第10条 サービスの停止・解約 -->
            <section id="termination" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第10条（サービスの停止・解約）</h2>
                
                <h3 class="text-xl font-semibold mt-4 mb-3 text-gray-900 dark:text-white">10.1 当方による停止</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">
                    当方は、以下の場合にユーザーへの事前通知なく、本サービスの全部または一部を停止・終了する場合があります:
                </p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li>本規約違反が確認された場合</li>
                    <li>長期間（180日以上）ログインがない場合</li>
                    <li>決済不正、不正利用が確認された場合</li>
                    <li>その他、当方が不適切と判断した場合</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-900 dark:text-white">10.2 ユーザーによる解約</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-3">
                    ユーザーは、いつでもアカウント削除により本サービスを解約できます。アカウント削除後、以下が適用されます:
                </p>
                <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                    <li>全てのデータは90日後に物理削除されます。</li>
                    <li>購入済みトークンの返金はありません。</li>
                    <li>削除後の復旧はできません。</li>
                </ul>
            </section>
            
            <!-- 第11条 規約の変更 -->
            <section id="changes" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第11条（規約の変更）</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li>当方は、本規約を変更する場合があります。</li>
                    <li>変更時は、以下の方法で通知します:
                        <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                            <li>アプリ内通知</li>
                            <li>登録メールアドレスへのメール送信</li>
                            <li>本ページ冒頭の「最終更新日」を更新</li>
                        </ul>
                    </li>
                    <li>重要な変更の場合、変更内容に同意いただけない場合はサービス利用を停止してください。</li>
                    <li>変更後もサービスを継続利用された場合、変更後の規約に同意したものとみなします。</li>
                </ol>
            </section>
            
            <!-- 第12条 準拠法・管轄裁判所 -->
            <section id="governing-law" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第12条（準拠法・管轄裁判所）</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                    <li>本規約の準拠法は <strong class="text-[#59B9C6]">日本法</strong> とします。</li>
                    <li>本サービスに関する紛争が生じた場合、<strong class="text-[#59B9C6]">東京地方裁判所</strong> を第一審の専属的合意管轄裁判所とします。</li>
                </ol>
            </section>
            
            <!-- 第13条 お問い合わせ -->
            <section id="contact" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white border-b-2 border-[#59B9C6] pb-2">第13条（お問い合わせ）</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4">本規約に関するご質問、ご意見は、以下の窓口までご連絡ください:</p>
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
                    <a href="{{ route('privacy-policy') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-[#59B9C6] transition-colors">
                        プライバシーポリシー
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
