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
        Schema::create('app_updates', function (Blueprint $table) {
            $table->id();
            $table->string('app_name', 50);
            $table->string('version', 50);
            $table->string('title');
            $table->text('description');
            $table->json('changes')->nullable();
            $table->timestamp('released_at');
            $table->boolean('is_major')->default(false);
            $table->timestamps();
            
            $table->unique(['app_name', 'version']);
            $table->index('app_name');
            $table->index('released_at');
        });
        
        DB::statement("ALTER TABLE app_updates ADD CONSTRAINT check_app_name CHECK (app_name IN ('myteacher', 'app2', 'app3'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_updates');
    }
};
