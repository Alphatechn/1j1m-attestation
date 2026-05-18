<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('civility', 20)->nullable()->after('periode_id');
            $table->string('city')->nullable()->after('email');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('training_group')->nullable()->after('whatsapp');
            $table->text('classmates_contacts')->nullable()->after('training_group');
            $table->text('course_delivery_explanation')->nullable()->after('classmates_contacts');
            $table->text('technical_skills')->nullable()->after('course_delivery_explanation');
            $table->text('public_speaking_description')->nullable()->after('technical_skills');
            $table->text('ecommerce_steps')->nullable()->after('public_speaking_description');
            $table->text('knowledge_use_plan')->nullable()->after('ecommerce_steps');
            $table->text('coaches')->nullable()->after('knowledge_use_plan');
            $table->text('homework_screenshot_paths')->nullable()->after('coaches');
            $table->enum('validation_status', ['pending', 'validated', 'rejected'])->default('validated')->after('is_active');
            $table->timestamp('submitted_at')->nullable()->after('validation_status');
            $table->timestamp('validated_at')->nullable()->after('submitted_at');
            $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->onDelete('set null');

            $table->index('validation_status');
            $table->index('training_group');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropIndex(['validation_status']);
            $table->dropIndex(['training_group']);
            $table->dropColumn([
                'civility',
                'city',
                'whatsapp',
                'training_group',
                'classmates_contacts',
                'course_delivery_explanation',
                'technical_skills',
                'public_speaking_description',
                'ecommerce_steps',
                'knowledge_use_plan',
                'coaches',
                'homework_screenshot_paths',
                'validation_status',
                'submitted_at',
                'validated_at',
                'validated_by',
            ]);
        });
    }
};
