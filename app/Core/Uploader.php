<?php

namespace App\Core;

/**
 * Universal File and Image Upload Handler
 * 
 * Safe, reusable file uploader supporting MIME-type validation,
 * size checks, random name generation, and automatic old-file cleanup.
 */
class Uploader
{
    /**
     * Default allowed image MIME types and extensions
     */
    public const IMAGE_TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];

    /**
     * Default allowed document MIME types and extensions
     */
    public const DOC_TYPES = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt'  => 'text/plain',
    ];

    /**
     * Upload an incoming file safely
     *
     * @param array $file $_FILES['input_name'] array
     * @param string $destinationDir Target directory relative to project root (e.g. 'uploads/avatars')
     * @param array $allowedTypes Map of extension => mime type (default: IMAGE_TYPES)
     * @param int $maxSizeBytes Max file size in bytes (default: 5MB)
     * @return array Result array with 'success' => bool, plus details or error message
     */
    public static function upload(
        array $file,
        string $destinationDir = 'uploads',
        array $allowedTypes = self::IMAGE_TYPES,
        int $maxSizeBytes = 5242880 // 5MB
    ): array {
        // 1. Verify standard PHP upload error codes
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Invalid file upload parameters.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No file was uploaded.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'Uploaded file exceeds the maximum allowed file size.'];
            default:
                return ['success' => false, 'error' => 'Unknown upload error occurred (code ' . $file['error'] . ').'];
        }

        // 2. Validate file size
        if ($file['size'] > $maxSizeBytes) {
            $maxMb = round($maxSizeBytes / (1024 * 1024), 2);
            return ['success' => false, 'error' => "File exceeds the maximum limit of {$maxMb}MB."];
        }

        // 3. Inspect actual file MIME type via finfo (never rely on $_FILES['type'])
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Check if extension exists in allowed types and MIME matches
        if (!array_key_exists($ext, $allowedTypes) || !in_array($realMime, $allowedTypes, true)) {
            $allowedList = implode(', ', array_keys($allowedTypes));
            return [
                'success' => false,
                'error'   => "Invalid file format. Allowed formats: {$allowedList} (detected: {$realMime})."
            ];
        }

        // 4. Prepare target upload directory
        $rootPath = dirname(__DIR__, 2);
        $cleanTargetDir = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $destinationDir), DIRECTORY_SEPARATOR);
        $absoluteTargetDir = $rootPath . DIRECTORY_SEPARATOR . $cleanTargetDir;

        if (!is_dir($absoluteTargetDir)) {
            if (!mkdir($absoluteTargetDir, 0755, true) && !is_dir($absoluteTargetDir)) {
                return ['success' => false, 'error' => 'Failed to create upload destination directory.'];
            }
        }

        // 5. Generate secure cryptographically random filename
        $safeName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
        $destinationPath = $absoluteTargetDir . DIRECTORY_SEPARATOR . $safeName;
        $relativeUrl = '/' . str_replace('\\', '/', $cleanTargetDir) . '/' . $safeName;

        // 6. Move the file from temp storage
        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file to destination.'];
        }

        return [
            'success'       => true,
            'filename'      => $safeName,
            'original_name' => htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'),
            'path'          => $destinationPath,
            'relative_path' => str_replace('\\', '/', $cleanTargetDir) . '/' . $safeName,
            'url'           => $relativeUrl,
            'size'          => $file['size'],
            'mime'          => $realMime,
        ];
    }

    /**
     * Delete an existing file safely from project storage
     *
     * @param string|null $relativePath Relative path to file (e.g. 'uploads/avatars/xyz.jpg')
     * @return bool
     */
    public static function delete(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $rootPath = dirname(__DIR__, 2);
        $normalized = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $absolutePath = $rootPath . DIRECTORY_SEPARATOR . $normalized;

        if (file_exists($absolutePath) && is_file($absolutePath)) {
            return @unlink($absolutePath);
        }

        return false;
    }
}
