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
            // SQLite rebuilds the table when dropping columns. Remove any
            // legacy index that refers to a column which no longer exists
            // before that rebuild starts.
            $columns = Schema::getColumnListing('participants');
            foreach (DB::select("PRAGMA index_list('participants')") as $index) {
                $name = $index->name ?? null;
                if (!$name || str_starts_with($name, 'sqlite_autoindex_')) {
                    continue;
                }

                $indexedColumns = DB::select("PRAGMA index_info('" . str_replace("'", "''", $name) . "')");
                $stale = collect($indexedColumns)->contains(
                    fn ($column) => !in_array($column->name ?? null, $columns, true)
                );

                if ($stale) {
                    DB::statement('DROP INDEX IF EXISTS "' . str_replace('"', '""', $name) . '"');
                }
            }
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
