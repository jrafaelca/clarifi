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
        Schema::create('ai_ingestion_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ai_ingestion_batches')->cascadeOnDelete();
            $table->string('suggestion_key');
            $table->string('kind', 24);
            $table->string('status', 24)->default('draft');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('source_excerpt')->nullable();
            $table->json('payload_json');
            $table->string('materialized_model_type')->nullable();
            $table->unsignedBigInteger('materialized_model_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'suggestion_key']);
            $table->index(['batch_id', 'kind', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_ingestion_suggestions');
    }
};
