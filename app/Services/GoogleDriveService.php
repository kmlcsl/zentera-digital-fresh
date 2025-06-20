<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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

    public function uploadFile($file, $fileName = null, $mimeType = null)
    {
        try {
            // Handle different input types
            if ($file instanceof UploadedFile) {
                // Laravel UploadedFile object
                $content = $file->get();
                $fileName = $fileName ?: $file->getClientOriginalName();
                $mimeType = $mimeType ?: $file->getMimeType();

                Log::info('Processing UploadedFile', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]);
            } elseif (is_string($file) && file_exists($file)) {
                // File path string
                $content = file_get_contents($file);
                $fileName = $fileName ?: basename($file);
                $mimeType = $mimeType ?: mime_content_type($file);

                Log::info('Processing file path', [
                    'file_path' => $file,
                    'file_name' => $fileName,
                    'mime_type' => $mimeType
                ]);
            } else {
                throw new \Exception("Invalid file input. Expected UploadedFile object or valid file path.");
            }

            // Validate content
            if (empty($content)) {
                throw new \Exception("File content is empty or could not be read.");
            }

            // Create file metadata for Google Drive
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $fileName,
                'parents' => [env('GOOGLE_DRIVE_FOLDER_ID', '1KQWlg9P99xPSoJ43RABMpm1mZPQN0MzF')]
            ]);

            // Upload to Google Drive
            $driveFile = $this->drive->files->create($fileMetadata, [
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

            $this->drive->permissions->create($driveFile->id, $permission);

            Log::info("File uploaded to Google Drive successfully", [
                'file_id' => $driveFile->id,
                'original_name' => $fileName,
                'mime_type' => $mimeType,
                'size' => strlen($content)
            ]);

            return $driveFile->id;
        } catch (\Exception $e) {
            Log::error('Failed to upload file to Google Drive: ' . $e->getMessage(), [
                'file_type' => get_class($file),
                'file_name' => $fileName ?? 'unknown',
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
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
