<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS participants_first_name_last_name_index');
            DB::statement('DROP INDEX IF EXISTS participants_matricule_unique');
        }

        if (!Schema::hasColumn('participants', 'homework_screenshot_paths')) {
            Schema::table('participants', function (Blueprint $table) {
                $table->text('homework_screenshot_paths')->nullable();
            });
        }

        Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'matricule') && DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropUnique(['matricule']);
            }

            $columns = array_filter([
                Schema::hasColumn('participants', 'matricule') ? 'matricule' : null,
                Schema::hasColumn('participants', 'organisation') ? 'organisation' : null,
                Schema::hasColumn('participants', 'fonction') ? 'fonction' : null,
                Schema::hasColumn('participants', 'homework_screenshot_path') ? 'homework_screenshot_path' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('matricule')->nullable()->unique()->after('phone');
            $table->string('organisation')->nullable()->after('matricule');
            $table->string('fonction')->nullable()->after('organisation');
            $table->string('homework_screenshot_path')->nullable()->after('coaches');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('homework_screenshot_paths');
        });
    }
};
