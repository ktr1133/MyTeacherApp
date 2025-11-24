<?php

namespace Database\Seeders;

use App\Models\AppUpdate;
use App\Models\Faq;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ポータルサイト用のサンプルデータSeeder
 * 
 * メンテナンス情報、FAQ、更新履歴のサンプルデータを投入します。
 */
class PortalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者ユーザーを取得または作成
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => bcrypt('password'),
            ]
        );

        $this->seedMaintenances($admin);
        $this->seedFaqs();
        $this->seedAppUpdates();

        $this->command->info('✅ ポータルサイトのサンプルデータを投入しました');
    }

    /**
     * メンテナンス情報のサンプルデータを投入
     */
    private function seedMaintenances(User $admin): void
    {
        $maintenances = [
            [
                'title' => '【完了】データベースメンテナンス',
                'description' => 'データベースのパフォーマンス向上のため、インデックスの最適化を実施しました。',
                'status' => 'completed',
                'scheduled_at' => now()->subDays(7)->setTime(2, 0),
                'started_at' => now()->subDays(7)->setTime(2, 5),
                'completed_at' => now()->subDays(7)->setTime(3, 30),
                'affected_apps' => ['myteacher'],
                'created_by' => $admin->id,
            ],
            [
                'title' => '【完了】セキュリティアップデート',
                'description' => 'セキュリティパッチの適用を行いました。ユーザーデータの安全性が向上しています。',
                'status' => 'completed',
                'scheduled_at' => now()->subDays(14)->setTime(3, 0),
                'started_at' => now()->subDays(14)->setTime(3, 10),
                'completed_at' => now()->subDays(14)->setTime(4, 0),
                'affected_apps' => ['myteacher'],
                'created_by' => $admin->id,
            ],
            [
                'title' => '【予定】定期メンテナンス (12月)',
                'description' => "12月の定期メンテナンスを実施いたします。\n\n【作業内容】\n- データベースバックアップ\n- サーバーOS更新\n- キャッシュクリア\n\n※メンテナンス中は一時的にサービスがご利用いただけなくなります。",
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(10)->setTime(2, 0),
                'started_at' => null,
                'completed_at' => null,
                'affected_apps' => ['myteacher'],
                'created_by' => $admin->id,
            ],
        ];

        foreach ($maintenances as $maintenance) {
            Maintenance::create($maintenance);
        }

        $this->command->info('  - メンテナンス情報: ' . count($maintenances) . '件');
    }

    /**
     * FAQのサンプルデータを投入
     */
    private function seedFaqs(): void
    {
        $faqs = [
            // はじめに
            [
                'category' => 'getting_started',
                'app_name' => 'myteacher',
                'question' => 'アカウントを作成するにはどうすればよいですか?',
                'answer' => "トップページの「Free Start」ボタンをクリックし、メールアドレスとパスワードを入力して登録してください。\n\n登録後、確認メールが送信されますので、メール内のリンクをクリックしてアカウントを有効化してください。",
                'display_order' => 1,
                'is_published' => true,
            ],
            [
                'category' => 'getting_started',
                'app_name' => 'myteacher',
                'question' => 'パスワードを忘れてしまいました',
                'answer' => "ログインページの「パスワードをお忘れですか?」リンクをクリックしてください。\n\n登録されているメールアドレスを入力すると、パスワードリセット用のリンクが送信されます。",
                'display_order' => 2,
                'is_published' => true,
            ],

            // タスク管理
            [
                'category' => 'tasks',
                'app_name' => 'myteacher',
                'question' => 'タスクの優先度はどのように設定しますか?',
                'answer' => "タスク作成・編集画面で「優先度」の項目から選択できます。\n\n優先度は以下の3段階です:\n- 高: 緊急性の高いタスク\n- 中: 通常のタスク\n- 低: 余裕のあるタスク",
                'display_order' => 3,
                'is_published' => true,
            ],
            [
                'category' => 'tasks',
                'app_name' => 'myteacher',
                'question' => '完了したタスクはどこで確認できますか?',
                'answer' => "ダッシュボードの「完了済み」タブから確認できます。\n\n完了したタスクは30日間保存され、その後自動的にアーカイブされます。",
                'display_order' => 4,
                'is_published' => true,
            ],

            // グループタスク
            [
                'category' => 'group_tasks',
                'app_name' => 'myteacher',
                'question' => 'グループタスクとは何ですか?',
                'answer' => "複数のユーザーに同時にタスクを割り当てる機能です。\n\n教師が生徒全員に同じ課題を出す場合などに便利です。各メンバーは個別にタスクを進行・完了できます。",
                'display_order' => 5,
                'is_published' => true,
            ],
            [
                'category' => 'group_tasks',
                'app_name' => 'myteacher',
                'question' => 'グループタスクの承認とは?',
                'answer' => "グループタスク作成時に「承認が必要」を設定すると、作成者が各メンバーのタスクを個別に承認する必要があります。\n\n承認されるまでタスクは「承認待ち」状態となります。",
                'display_order' => 6,
                'is_published' => true,
            ],

            // AI機能
            [
                'category' => 'ai_features',
                'app_name' => 'myteacher',
                'question' => 'AIタスク分解とは何ですか?',
                'answer' => "GPT-4o-miniを使用して、大きなタスクを小さなステップに自動分解する機能です。\n\n例えば「Webアプリを作る」というタスクを、「要件定義」「設計」「実装」などの具体的なステップに分解できます。",
                'display_order' => 7,
                'is_published' => true,
            ],
            [
                'category' => 'ai_features',
                'app_name' => 'myteacher',
                'question' => 'AI機能を使うとトークンを消費しますか?',
                'answer' => "はい、AI機能の利用にはトークンが必要です。\n\n毎月100万トークンの無料枠が提供されます。不足する場合は追加購入も可能です。",
                'display_order' => 8,
                'is_published' => true,
            ],

            // 教師アバター
            [
                'category' => 'avatars',
                'app_name' => 'myteacher',
                'question' => '教師アバターとは何ですか?',
                'answer' => "Stable Diffusionで生成されるAI教師キャラクターです。\n\nタスク完了時などに励ましのコメントを表示してくれます。自分だけのオリジナル教師を作成できます。",
                'display_order' => 9,
                'is_published' => true,
            ],
            [
                'category' => 'avatars',
                'app_name' => 'myteacher',
                'question' => 'アバターの生成にはどのくらい時間がかかりますか?',
                'answer' => "通常1〜3分程度で完了します。\n\n生成中は他の作業を進めていただけます。完了時に通知が届きます。",
                'display_order' => 10,
                'is_published' => true,
            ],

            // トークン
            [
                'category' => 'tokens',
                'app_name' => 'myteacher',
                'question' => 'トークンはどうやって獲得しますか?',
                'answer' => "トークンの獲得方法:\n\n1. 毎月1日に100万トークンの無料枠が付与\n2. タスク完了時の報酬\n3. 追加購入 (Stripe決済)\n\n無料枠だけでも多くの機能をご利用いただけます。",
                'display_order' => 11,
                'is_published' => true,
            ],
            [
                'category' => 'tokens',
                'app_name' => 'myteacher',
                'question' => 'トークン残高はどこで確認できますか?',
                'answer' => "ダッシュボードの右上に常時表示されています。\n\n詳細な履歴は「トークン履歴」ページで確認できます。",
                'display_order' => 12,
                'is_published' => true,
            ],

            // トラブルシューティング
            [
                'category' => 'troubleshooting',
                'app_name' => 'myteacher',
                'question' => 'タスクが保存できません',
                'answer' => "以下をご確認ください:\n\n1. タイトルが入力されているか\n2. 期限が正しい形式か\n3. ネットワーク接続が安定しているか\n\n問題が解決しない場合は、お問い合わせフォームからご連絡ください。",
                'display_order' => 13,
                'is_published' => true,
            ],
            [
                'category' => 'troubleshooting',
                'app_name' => 'myteacher',
                'question' => 'アバターが表示されません',
                'answer' => "以下をお試しください:\n\n1. ページを再読み込み\n2. ブラウザのキャッシュをクリア\n3. 別のブラウザで確認\n\nそれでも表示されない場合は、画像生成が完了していない可能性があります。通知をご確認ください。",
                'display_order' => 14,
                'is_published' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }

        $this->command->info('  - FAQ: ' . count($faqs) . '件');
    }

    /**
     * アプリ更新履歴のサンプルデータを投入
     */
    private function seedAppUpdates(): void
    {
        $updates = [
            [
                'app_name' => 'myteacher',
                'version' => '2.1.0',
                'title' => 'ポータルサイト機能追加',
                'description' => '使い方ガイド、FAQ、お問い合わせ機能を含むポータルサイトを追加しました。',
                'changes' => [
                    '使い方ガイドページの追加',
                    'FAQ機能 (検索・フィルター対応)',
                    'お問い合わせフォーム',
                    'メンテナンス情報表示',
                    '更新履歴タイムライン',
                ],
                'is_major' => true,
                'released_at' => now(),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '2.0.0',
                'title' => '教師アバター機能リリース',
                'description' => 'Stable Diffusionを使用したAI教師アバター生成機能を追加しました。',
                'changes' => [
                    'アバター自動生成機能',
                    '8種類の画像生成 (表情×ポーズ)',
                    'イベント連動コメント表示',
                    'アバター編集・削除機能',
                    '透過処理オプション',
                ],
                'is_major' => true,
                'released_at' => now()->subDays(30),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.5.2',
                'title' => 'パフォーマンス改善',
                'description' => 'タスク一覧の表示速度を改善しました。',
                'changes' => [
                    'N+1クエリ問題の解決',
                    'キャッシュ機構の導入',
                    'データベースインデックス最適化',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(45),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.5.1',
                'title' => 'バグ修正',
                'description' => 'グループタスクの承認フローに関するバグを修正しました。',
                'changes' => [
                    '承認待ちタスクが正しく表示されない問題を修正',
                    '承認通知が送信されない問題を修正',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(50),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.5.0',
                'title' => 'グループタスク機能強化',
                'description' => 'グループタスクの承認フローとスケジュール機能を追加しました。',
                'changes' => [
                    '承認フロー機能',
                    'スケジュールタスク自動生成',
                    '祝日対応',
                    'ランダム割当機能',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(60),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.4.0',
                'title' => 'AIタスク分解機能追加',
                'description' => 'GPT-4o-miniを使用したタスク自動分解機能を追加しました。',
                'changes' => [
                    'AI自動分解機能',
                    '分解結果の編集機能',
                    'トークン消費システム',
                    '事前見積もり機能',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(75),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.3.0',
                'title' => 'トークンシステム導入',
                'description' => 'タスク報酬とAI機能利用のためのトークンシステムを導入しました。',
                'changes' => [
                    'トークン残高管理',
                    'トークン履歴表示',
                    'Stripe決済統合',
                    '月次無料枠 (100万トークン)',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(90),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.2.0',
                'title' => 'グループタスク機能リリース',
                'description' => '複数ユーザーへの同時タスク割当機能を追加しました。',
                'changes' => [
                    'グループタスク作成',
                    'メンバー選択機能',
                    '進捗確認機能',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(120),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.1.0',
                'title' => 'タスク管理機能強化',
                'description' => 'タスクのタグ付け、フィルター機能を追加しました。',
                'changes' => [
                    'タグ機能',
                    'フィルター・ソート機能',
                    '優先度表示の改善',
                ],
                'is_major' => false,
                'released_at' => now()->subDays(150),
            ],
            [
                'app_name' => 'myteacher',
                'version' => '1.0.0',
                'title' => 'MyTeacher正式リリース',
                'description' => 'AIタスク管理アプリ「MyTeacher」を正式リリースしました。',
                'changes' => [
                    'タスク作成・編集・削除',
                    'ダッシュボード',
                    'ユーザー認証',
                    'ダークモード対応',
                ],
                'is_major' => true,
                'released_at' => now()->subDays(180),
            ],
        ];

        foreach ($updates as $update) {
            AppUpdate::create($update);
        }

        $this->command->info('  - アプリ更新履歴: ' . count($updates) . '件');
    }
}

