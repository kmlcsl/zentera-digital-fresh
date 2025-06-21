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
            // HANYA Payment proof Google Drive fields
            $table->string('payment_proof_google_drive_file_id')->nullable()->after('payment_proof');
            $table->text('payment_proof_google_drive_view_url')->nullable()->after('payment_proof_google_drive_file_id');
            $table->text('payment_proof_google_drive_preview_url')->nullable()->after('payment_proof_google_drive_view_url');
            $table->text('payment_proof_google_drive_download_url')->nullable()->after('payment_proof_google_drive_preview_url');
            $table->text('payment_proof_google_drive_direct_link')->nullable()->after('payment_proof_google_drive_download_url');
            $table->text('payment_proof_google_drive_thumbnail_url')->nullable()->after('payment_proof_google_drive_direct_link');
            $table->boolean('payment_proof_is_google_drive')->default(false)->after('payment_proof_google_drive_thumbnail_url');
            $table->string('payment_proof_storage_type')->default('local')->after('payment_proof_is_google_drive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_proof_google_drive_file_id',
                'payment_proof_google_drive_view_url',
                'payment_proof_google_drive_preview_url',
                'payment_proof_google_drive_download_url',
                'payment_proof_google_drive_direct_link',
                'payment_proof_google_drive_thumbnail_url',
                'payment_proof_is_google_drive',
                'payment_proof_storage_type'
            ]);
        });
    }
};
