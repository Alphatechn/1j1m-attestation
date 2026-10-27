<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite' || !Schema::hasTable('participants')) {
            return;
        }

        $columns = Schema::getColumnListing('participants');
        $indexes = DB::select("PRAGMA index_list('participants')");

        foreach ($indexes as $index) {
            $name = $index->name ?? null;
            if (!$name || str_starts_with($name, 'sqlite_autoindex_')) {
                continue;
            }

            $indexedColumns = DB::select("PRAGMA index_info('" . str_replace("'", "''", $name) . "')");
            $hasMissingColumn = collect($indexedColumns)->contains(
                fn ($column) => !in_array($column->name ?? null, $columns, true)
            );

            if ($hasMissingColumn) {
                DB::statement('DROP INDEX IF EXISTS "' . str_replace('"', '""', $name) . '"');
            }
        }
    }

    public function down(): void
    {
        // Stale indexes are intentionally not recreated.
    }
};
