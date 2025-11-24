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
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->string('app_name', 50);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('admin_note')->nullable();
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
