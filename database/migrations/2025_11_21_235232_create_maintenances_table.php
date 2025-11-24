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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->json('affected_apps');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->index('scheduled_at');
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
        
        DB::statement("ALTER TABLE maintenances ADD CONSTRAINT check_status CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
