<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->morphs('model');
            $table->nullableMorphs('changer');
            $table->unsignedInteger('version');
            
            // Use jsonb for PostgreSQL, json for others
            if (config('database.default') === 'pgsql') {
                $table->jsonb('snapshot');
            } else {
                $table->json('snapshot');
            }

            // Indexes for performance
            $table->index(['model_type', 'model_id', 'version']);
            $table->unique(['model_type', 'model_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
