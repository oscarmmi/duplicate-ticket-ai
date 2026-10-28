<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('duplicate_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matched_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->decimal('score', 6, 4)->unsigned();
            $table->boolean('llm_confirmed')->default(false);
            $table->json('reasons');
            $table->timestamps();
            $table->unique(['duplicate_check_id', 'matched_ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_matches');
    }
};
