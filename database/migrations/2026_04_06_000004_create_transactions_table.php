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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('related_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('direction');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->date('transaction_date');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->string('source')->default('manual');
            $table->string('status')->default('confirmed');
            $table->string('attachment_path')->nullable();
            $table->uuid('transfer_group_uuid')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'transaction_date']);
            $table->index(['team_id', 'type', 'transaction_date']);
            $table->index(['account_id', 'status']);
            $table->index('transfer_group_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
