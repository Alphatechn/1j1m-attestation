<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->foreignId('periode_id')->constrained('periodes')->onDelete('cascade');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('attestation_number')->unique();
            $table->string('qr_token')->unique();
            $table->date('issue_date');
            $table->text('content_text')->nullable();
            $table->enum('status', ['pending', 'sent'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->string('email_status')->nullable(); // success, failed, pending
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('attestation_number');
            $table->index('qr_token');
            $table->index('participant_id');
            $table->index('periode_id');
            $table->index('status');
            $table->index('issue_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
