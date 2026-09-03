<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a free-text description column to Spatie's roles/permissions
     * tables, matching the "Описание" field from docs/PHASE-1-SPEC.md's
     * Roles form. Spatie's own schema (name/guard_name) is otherwise
     * left untouched — `name` doubles as the slug-like identifier used
     * in permission checks, so no separate slug column is added.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
