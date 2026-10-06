<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Per-week file uploads (Súbory) were never used and have been removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('media');

        Permission::whereIn('name', ['media.view', 'media.create', 'media.update', 'media.delete'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Súbory has been removed; restore it from git history instead.
    }
};
