<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class GoogleDriveService
{
    private $client;
    private $service;
    private $folderId;

    public function __construct()
    {
        $this->client = new Client();

        // Service Account authentication
        $serviceAccountFile = storage_path('app/google-service-account.json');

        if (!file_exists($serviceAccountFile)) {
            throw new \Exception('Google service account file not found: ' . $serviceAccountFile);
        }

        $this->client->setAuthConfig($serviceAccountFile);
        $this->client->setScopes([Drive::DRIVE]);

        $this->service = new Drive($this->client);
        $this->folderId = config('services.google_drive.folder_id', '1KQWlg9P99xPSoJ43RABMpm1mZPQN0MzF');
    }

    /**
     * Upload file ke Google Drive
     */
    public function uploadFile($file, $serviceType)
    {
        try {
            Log::info('=== GOOGLE DRIVE UPLOAD START ===', [
                'service_type' => $serviceType,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);

            // ... existing code ...

            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $fileContent,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart'
            ]);

            $fileId = $uploadedFile->getId();
            $fileName = $uploadedFile->getName();

            // TAMBAH LOG INI
            Log::info('✅ Google Drive upload successful', [
                'file_id' => $fileId,
                'file_name' => $fileName,
                'service_type' => $serviceType
            ]);

            // PASTIKAN RETURN YANG BENAR
            $result = [
                'success' => true,
                'file_id' => $fileId,
                'name' => $fileName,
                'view_url' => "https://drive.google.com/file/d/{$fileId}/view",
                'preview_url' => "https://drive.google.com/file/d/{$fileId}/preview",
                'download_url' => "https://drive.google.com/uc?export=download&id={$fileId}",
                'direct_link' => "https://drive.google.com/open?id={$fileId}",
                'thumbnail_url' => null
            ];

            Log::info('Google Drive upload result:', $result);

            return $result;
        } catch (\Exception $e) {
            Log::error('❌ Google Drive upload failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create folder if not exists
     */
    private function createFolder(string $folderName): string
    {
        try {
            // Capitalize folder name
            $folderName = ucfirst(strtolower($folderName));

            // Check if folder exists
            $query = "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and parents in '{$this->folderId}' and trashed=false";
            $results = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name)'
            ]);

            if (count($results->getFiles()) > 0) {
                $existingFolder = $results->getFiles()[0];
                Log::info('📁 Using existing folder', [
                    'folder_name' => $folderName,
                    'folder_id' => $existingFolder->getId()
                ]);
                return $existingFolder->getId();
            }

            // Create new folder
            $folderMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$this->folderId]
            ]);

            $folder = $this->service->files->create($folderMetadata, [
                'fields' => 'id,name'
            ]);

            Log::info('📁 Created new folder', [
                'folder_name' => $folderName,
                'folder_id' => $folder->id
            ]);

            return $folder->id;
        } catch (\Exception $e) {
            Log::error('❌ Failed to create/find folder', [
                'error' => $e->getMessage(),
                'folder_name' => $folderName
            ]);

            // Fallback to main folder
            return $this->folderId;
        }
    }

    /**
     * Set file permissions untuk public access
     */
    private function setFilePermissions(string $fileId): void
    {
        try {
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ]);

            $this->service->permissions->create($fileId, $permission);

            Log::info('🔓 File permissions set successfully', ['file_id' => $fileId]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Failed to set file permissions', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test connection
     */
    public function testConnection(): bool
    {
        try {
            // Try to list files in main folder
            $this->service->files->listFiles([
                'q' => "parents in '{$this->folderId}'",
                'pageSize' => 1,
                'fields' => 'files(id,name)'
            ]);

            Log::info('✅ Google Drive connection test successful');
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Google Drive connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete file from Google Drive
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $this->service->files->delete($fileId);

            Log::info('🗑️ File deleted successfully', ['file_id' => $fileId]);
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Failed to delete file from Google Drive', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
