<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index()->comment('キュー名');
            $table->longText('payload')->comment('ジョブペイロード');
            $table->unsignedTinyInteger('attempts')->comment('実行試行回数');
            $table->unsignedInteger('reserved_at')->nullable()->comment('予約済み時刻');
            $table->unsignedInteger('available_at')->comment('利用可能時刻');
            $table->unsignedInteger('created_at')->comment('作成時刻');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('バッチID');
            $table->string('name')->comment('バッチ名');
            $table->integer('total_jobs')->comment('総ジョブ数');
            $table->integer('pending_jobs')->comment('保留中ジョブ数');
            $table->integer('failed_jobs')->comment('失敗ジョブ数');
            $table->longText('failed_job_ids')->comment('失敗ジョブIDリスト');
            $table->mediumText('options')->nullable()->comment('オプション');
            $table->integer('cancelled_at')->nullable()->comment('キャンセル時刻');
            $table->integer('created_at')->comment('作成時刻');
            $table->integer('finished_at')->nullable()->comment('完了時刻');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->comment('ジョブUUID');
            $table->text('connection')->comment('接続名');
            $table->text('queue')->comment('キュー名');
            $table->longText('payload')->comment('ジョブペイロード');
            $table->longText('exception')->comment('例外情報');
            $table->timestamp('failed_at')->useCurrent()->comment('失敗時刻');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
