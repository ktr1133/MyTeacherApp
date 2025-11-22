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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50);
            $table->string('app_name', 50);
            $table->text('question');
            $table->text('answer');
            $table->integer('display_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            
            $table->index('category');
            $table->index('app_name');
            $table->index('is_published');
            $table->index('display_order');
        });
        
        DB::statement("ALTER TABLE faqs ADD CONSTRAINT check_category CHECK (category IN ('getting_started', 'tasks', 'group_tasks', 'ai_features', 'avatars', 'tokens', 'account', 'troubleshooting', 'other'))");
        DB::statement("ALTER TABLE faqs ADD CONSTRAINT check_app_name CHECK (app_name IN ('myteacher', 'app2', 'app3'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
