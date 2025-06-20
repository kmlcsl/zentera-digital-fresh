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
        Schema::table('document_orders', function (Blueprint $table) {
            // Add Google Drive related columns
            $table->string('google_drive_file_id')->nullable()->after('document_path');
            $table->text('google_drive_view_url')->nullable()->after('google_drive_file_id');
            $table->text('google_drive_preview_url')->nullable()->after('google_drive_view_url');
            $table->text('google_drive_download_url')->nullable()->after('google_drive_preview_url');
            $table->text('google_drive_direct_link')->nullable()->after('google_drive_download_url');
            $table->text('google_drive_thumbnail_url')->nullable()->after('google_drive_direct_link');
            $table->boolean('is_google_drive')->default(false)->after('google_drive_thumbnail_url');
            $table->enum('storage_type', ['local', 'google_drive'])->default('local')->after('is_google_drive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_orders', function (Blueprint $table) {
            $table->dropColumn([
                'google_drive_file_id',
                'google_drive_view_url',
                'google_drive_preview_url',
                'google_drive_download_url',
                'google_drive_direct_link',
                'google_drive_thumbnail_url',
                'is_google_drive',
                'storage_type'
            ]);
        });
    }
};
