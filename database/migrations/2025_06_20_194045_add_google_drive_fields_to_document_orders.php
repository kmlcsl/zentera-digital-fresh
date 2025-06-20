<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('document_orders', function (Blueprint $table) {
            // Google Drive fields
            $table->string('google_drive_file_id')->nullable()->after('document_path');
            $table->text('google_drive_view_url')->nullable()->after('google_drive_file_id');
            $table->text('google_drive_preview_url')->nullable()->after('google_drive_view_url');
            $table->text('google_drive_download_url')->nullable()->after('google_drive_preview_url');
            $table->text('google_drive_direct_link')->nullable()->after('google_drive_download_url');
            $table->text('google_drive_thumbnail_url')->nullable()->after('google_drive_direct_link');

            // Storage info
            $table->boolean('is_google_drive')->default(false)->after('google_drive_thumbnail_url');
            $table->enum('storage_type', ['local', 'google_drive'])->default('local')->after('is_google_drive');

            // Indexes for performance
            $table->index('google_drive_file_id');
            $table->index('is_google_drive');
            $table->index('storage_type');
        });
    }

    public function down()
    {
        Schema::table('document_orders', function (Blueprint $table) {
            $table->dropIndex(['google_drive_file_id']);
            $table->dropIndex(['is_google_drive']);
            $table->dropIndex(['storage_type']);

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
