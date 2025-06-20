<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private $client;
    private $drive;

    public function __construct()
    {
        $this->initializeClient();
    }

    private function initializeClient()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName('Laravel Google Drive');
            $this->client->setScopes([Drive::DRIVE_FILE]);

            // For Vercel deployment - use environment variables
            $serviceAccountJson = env('GOOGLE_SERVICE_ACCOUNT_JSON');

            if (!$serviceAccountJson) {
                throw new \Exception("GOOGLE_SERVICE_ACCOUNT_JSON environment variable not found. Please set it in Vercel dashboard.");
            }

            // Decode base64 encoded JSON from environment
            $decodedJson = base64_decode($serviceAccountJson);
            $serviceAccountData = json_decode($decodedJson, true);

            if (!$serviceAccountData) {
                throw new \Exception("Invalid GOOGLE_SERVICE_ACCOUNT_JSON format. Please check the base64 encoding.");
            }

            $this->client->setAuthConfig($serviceAccountData);
            $this->drive = new Drive($this->client);

            Log::info('Google Drive service initialized successfully via environment variables');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Drive service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function uploadFile($filePath, $fileName, $mimeType = null)
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }

            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $fileName,
                'parents' => [env('GOOGLE_DRIVE_FOLDER_ID', '1KQWlg9P99xPSoJ43RABMpm1mZPQN0MzF')]
            ]);

            $content = file_get_contents($filePath);
            $mimeType = $mimeType ?: mime_content_type($filePath);

            $file = $this->drive->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Set file permissions to be viewable by anyone with the link
            $permission = new \Google\Service\Drive\Permission([
                'role' => 'reader',
                'type' => 'anyone'
            ]);

            $this->drive->permissions->create($file->id, $permission);

            Log::info("File uploaded to Google Drive successfully", [
                'file_id' => $file->id,
                'original_name' => $fileName
            ]);

            return $file->id;
        } catch (\Exception $e) {
            Log::error('Failed to upload file to Google Drive: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'file_name' => $fileName
            ]);
            throw $e;
        }
    }

    public function deleteFile($fileId)
    {
        try {
            $this->drive->files->delete($fileId);
            Log::info("File deleted from Google Drive", ['file_id' => $fileId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete file from Google Drive: ' . $e->getMessage(), [
                'file_id' => $fileId
            ]);
            return false;
        }
    }

    public function getFileInfo($fileId)
    {
        try {
            return $this->drive->files->get($fileId, [
                'fields' => 'id, name, mimeType, size, createdTime, modifiedTime'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get file info from Google Drive: ' . $e->getMessage(), [
                'file_id' => $fileId
            ]);
            throw $e;
        }
    }

    public function generateUrls($fileId)
    {
        return [
            'view_url' => "https://drive.google.com/file/d/{$fileId}/view",
            'preview_url' => "https://drive.google.com/file/d/{$fileId}/preview",
            'download_url' => "https://drive.google.com/uc?export=download&id={$fileId}",
            'direct_link' => "https://drive.google.com/open?id={$fileId}",
            'thumbnail_url' => "https://drive.google.com/thumbnail?id={$fileId}&sz=s220"
        ];
    }
}
