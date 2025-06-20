<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'service_type',
        'service_name',
        'price',
        'document_path',
        'google_drive_file_id',
        'google_drive_view_url',
        'google_drive_preview_url',
        'google_drive_download_url',
        'google_drive_direct_link',
        'google_drive_thumbnail_url',
        'is_google_drive',
        'storage_type',
        'notes',
        'payment_status',
        'payment_proof',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'is_google_drive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function generateOrderNumber()
    {
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', now())->count();
        $orderNumber = str_pad($lastOrder + 1, 3, '0', STR_PAD_LEFT);

        return "DOC{$date}{$orderNumber}";
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu Pembayaran</span>',
            'paid' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Sedang Diproses</span>',
            'completed' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>',
        ];

        return $badges[$this->payment_status] ?? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
    }

    public function getServiceIconAttribute()
    {
        $icons = [
            'repair' => 'fas fa-tools',
            'plagiarism' => 'fas fa-search',
            'format' => 'fas fa-file-alt',
        ];

        return $icons[$this->service_type] ?? 'fas fa-file';
    }

    // Helper method untuk mendapatkan URL file yang benar
    public function getFileUrlAttribute()
    {
        if ($this->is_google_drive && $this->google_drive_view_url) {
            return $this->google_drive_view_url;
        }

        if ($this->document_path) {
            return Storage::url($this->document_path);
        }

        return null;
    }

    // Helper method untuk mendapatkan download URL
    public function getDownloadUrlAttribute()
    {
        if ($this->is_google_drive && $this->google_drive_download_url) {
            return $this->google_drive_download_url;
        }

        if ($this->document_path) {
            return Storage::url($this->document_path);
        }

        return null;
    }
}
