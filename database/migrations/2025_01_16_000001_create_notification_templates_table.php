<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 通知テンプレートテーブルのマイグレーション
 * 
 * 管理者が作成する通知のマスターデータを管理。
 * 中間テーブル（user_notifications）を介してユーザーごとの既読状態を管理する。
 * 
 * @package App\Database\Migrations
 */
return new class extends Migration
{
    /**
     * マイグレーション実行
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            
            // 送信者（管理者）
            $table->foreignId('sender_id')
                ->comment('送信者のユーザーID（管理者）')
                ->constrained('users')
                ->cascadeOnDelete();
            
            // 発信元（system or admin）
            $table->enum('source', ['system', 'admin'])
                ->default('admin')
                ->comment('通知の発信元'); // system=システム自動生成, admin=管理者作成
            
            // 通知種別
            $table->string('type', 50)
                ->comment('通知種別'); // （config/const.php の notification_types 参照）
            
            // 優先度
            $table->enum('priority', ['info', 'normal', 'important'])
                ->default('normal')
                ->comment('優先度'); // info=情報, normal=通常, important=重要
            
            // 通知内容
            $table->string('title')
                ->comment('通知タイトル');
            $table->text('message')
                ->comment('通知本文');
            
            // 追加データ（JSON）
            $table->json('data')
                ->nullable()
                ->comment('追加データ'); // 任意の追加情報をJSON形式で格納
            
            // アクション
            $table->string('action_url')
                ->nullable()
                ->comment('アクションボタンのリンク先URL');
            $table->string('action_text')
                ->nullable()
                ->comment('アクションボタンのテキスト');
            
            // 公式ページURL（スラッグ）
            $table->string('official_page_slug')
                ->nullable()
                ->unique()
                ->comment('公式ページのスラッグ'); // 例: 2025-winter-update
            
            // 対象ユーザー指定方法
            $table->enum('target_type', ['all', 'users', 'groups'])
                ->default('all')
                ->comment('配信対象'); // all=全ユーザー, users=特定ユーザー, groups=特定グループ
            
            // 対象ID（user_ids または group_ids の配列）
            $table->json('target_ids')
                ->nullable()
                ->comment('配信対象のIDリスト'); // JSON配列
            
            // 公開期間
            $table->timestamp('publish_at')
                ->nullable()
                ->comment('公開開始日時');
            $table->timestamp('expire_at')
                ->nullable()
                ->comment('公開終了日時');
            
            // 編集履歴
            $table->foreignId('updated_by')
                ->nullable()
                ->comment('最終編集者のユーザーID')
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes(); // ソフトデリート
            
            // インデックス
            $table->index(['source', 'publish_at', 'expire_at'], 'idx_source_publish_expire');
            $table->index('official_page_slug', 'idx_official_page_slug');
        });
    }

    /**
     * マイグレーションのロールバック
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};