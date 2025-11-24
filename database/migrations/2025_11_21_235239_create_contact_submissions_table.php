<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('送信者名');
            $table->string('email')->comment('送信者メールアドレス');
            $table->string('subject')->comment('件名');
            $table->text('message')->comment('問い合わせ内容');
            $table->string('app_name', 50)->comment('対象アプリ名');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ログインユーザーID（任意）');
            $table->string('status', 50)->default('pending')->comment('対応状況: pending, in_progress, resolved');
            $table->text('admin_note')->nullable()->comment('管理者メモ');
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
        
        DB::statement("ALTER TABLE contact_submissions ADD CONSTRAINT check_app_name CHECK (app_name IN ('myteacher', 'app2', 'app3', 'general'))");
        DB::statement("ALTER TABLE contact_submissions ADD CONSTRAINT check_status CHECK (status IN ('pending', 'in_progress', 'resolved'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
