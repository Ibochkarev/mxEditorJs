<?php

declare(strict_types=1);

namespace MxEditorJs\Service;

use MODX\Revolution\modX;
use MODX\Revolution\Sources\modMediaSource;

class MediaUploader
{
    private modX $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function upload(array $file, int $resourceId): array
    {
        $this->validateFile($file);

        $mediaSourceId = (int)$this->modx->getOption('mxeditorjs.image_mediasource', null, 1);
        $template = $this->modx->getOption(
            'mxeditorjs.image_upload_path',
            null,
            'images/resources/{resource_id}/'
        );

        return $this->uploadToPath($file, $resourceId, $mediaSourceId, $template);
    }

    private const ALLOWED_IMAGE_MIME = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    private const ALLOWED_FILE_EXT = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', '7z',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
    ];

    public function uploadFile(array $file, int $resourceId): array
    {
        $this->validateFileForAttach($file);

        $mediaSourceId = (int)$this->modx->getOption('mxeditorjs.image_mediasource', null, 1);
        $template = $this->modx->getOption(
            'mxeditorjs.file_upload_path',
            null,
            'files/resources/{resource_id}/'
        );

        return $this->uploadToPath($file, $resourceId, $mediaSourceId, $template);
    }

    private function uploadToPath(
        array $file,
        int $resourceId,
        int $mediaSourceId,
        string $pathTemplate
    ): array {
        /** @var modMediaSource|null $source */
        $source = $this->modx->getObject(modMediaSource::class, $mediaSourceId);
        if (!$source) {
            throw new \RuntimeException('Media source not found: ' . $mediaSourceId);
        }

        $source->initialize();

        $uploadPath = str_replace('{resource_id}', (string)$resourceId, $pathTemplate);
        $uploadPath = rtrim($uploadPath, '/') . '/';

        $this->ensureDirectoryExists($source, $uploadPath);

        $safeName = $this->sanitizeFilename($file['name']);
        $safeName = $this->makeUniqueName($source, $uploadPath, $safeName);

        $result = $source->uploadObjectsToContainer($uploadPath, [
            [
                'name' => $safeName,
                'tmp_name' => $file['tmp_name'],
                'error' => $file['error'],
                'size' => $file['size'],
                'type' => $file['type'],
            ],
        ]);

        if (!$result) {
            $errors = $source->getErrors();
            $message = !empty($errors) ? implode('; ', $errors) : 'Upload failed';
            throw new \RuntimeException($message);
        }

        $baseUrl = $source->getBaseUrl();
        $fileUrl = rtrim($baseUrl, '/') . '/' . ltrim($uploadPath, '/') . $safeName;

        return [
            'success' => 1,
            'file' => [
                'url' => $fileUrl,
                'name' => $file['name'],
                'size' => $file['size'],
            ],
        ];
    }

    private function validateFileForAttach(array $file): void
    {
        $this->validateUploadedFile($file);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_FILE_EXT, true)) {
            throw new \RuntimeException('File type not allowed: ' . $ext);
        }
    }

    private function validateFile(array $file): void
    {
        $this->validateUploadedFile($file);

        $allowedStr = $this->modx->getOption(
            'mxeditorjs.allowed_image_types',
            null,
            'jpg,jpeg,png,gif,webp,svg'
        );
        $allowed = array_map('trim', explode(',', strtolower($allowedStr)));

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('File type not allowed: ' . $ext);
        }

        $this->validateMimeType($file['tmp_name']);
    }

    private function validateUploadedFile(array $file): void
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No valid file uploaded');
        }

        $maxSize = (int)$this->modx->getOption('mxeditorjs.max_upload_size', null, 5242880);
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('File exceeds maximum upload size');
        }
    }

    private function validateMimeType(string $tmpPath): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new \RuntimeException('Unable to detect MIME type');
        }

        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if ($mimeType === false) {
            throw new \RuntimeException('Unable to detect file MIME type');
        }

        if (!in_array($mimeType, self::ALLOWED_IMAGE_MIME, true)) {
            throw new \RuntimeException('Invalid file MIME type: ' . $mimeType);
        }
    }

    private function ensureDirectoryExists(modMediaSource $source, string $path): void
    {
        $basePath = $source->getBasePath();
        $fullPath = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    private function sanitizeFilename(string $name): string
    {
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $clean = preg_replace('/[^\w\-]/', '-', $baseName);
        $clean = preg_replace('/-+/', '-', $clean);
        $clean = trim($clean, '-');

        if (empty($clean)) {
            $clean = 'upload-' . time();
        }

        if (empty($ext)) {
            $ext = 'jpg';
        }

        return $clean . '.' . $ext;
    }

    private function makeUniqueName(modMediaSource $source, string $path, string $name): string
    {
        $basePath = $source->getBasePath();
        $dir = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        $candidate = $name;
        $counter = 1;

        while (file_exists($dir . $candidate)) {
            $candidate = $baseName . '-' . $counter . '.' . $ext;
            $counter++;

            if ($counter > 100) {
                $candidate = $baseName . '-' . uniqid() . '.' . $ext;
                break;
            }
        }

        return $candidate;
    }

    public function browse(int $resourceId, string $type = 'image', string $subPath = ''): array
    {
        $mediaSourceId = $type === 'image'
            ? (int)$this->modx->getOption('mxeditorjs.image_mediasource', null, 1)
            : (int)$this->modx->getOption('mxeditorjs.file_mediasource', null, 1);

        /** @var modMediaSource|null $source */
        $source = $this->modx->getObject(modMediaSource::class, $mediaSourceId);
        if (!$source) {
            throw new \RuntimeException('Media source not found: ' . $mediaSourceId);
        }

        $source->initialize();

        $basePath = $source->getBasePath();
        $baseUrl = $source->getBaseUrl();

        $requestRoot = ($subPath === '/' || $subPath === '__root__');
        if ($requestRoot) {
            $browsePath = '';
        } elseif ($subPath !== '') {
            $browsePath = $subPath;
        } else {
            $template = $type === 'image'
                ? $this->modx->getOption('mxeditorjs.image_upload_path', null, 'images/resources/{resource_id}/')
                : $this->modx->getOption('mxeditorjs.file_upload_path', null, 'files/resources/{resource_id}/');
            $browsePath = str_replace('{resource_id}', (string)$resourceId, $template);
        }

        $fullPath = $browsePath === ''
            ? rtrim($basePath, '/') . '/'
            : rtrim($basePath, '/') . '/' . ltrim($browsePath, '/') . '/';

        $files = [];
        $folders = [];

        if (!is_dir($fullPath)) {
            return ['files' => [], 'folders' => [], 'path' => $browsePath];
        }

        $allowedImageExt = array_map('trim', explode(',', strtolower(
            $this->modx->getOption('mxeditorjs.allowed_image_types', null, 'jpg,jpeg,png,gif,webp,svg')
        )));

        $items = scandir($fullPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . $item;
            $relativePath = $browsePath === '' ? $item : ltrim($browsePath, '/') . '/' . $item;
            $itemUrl = rtrim($baseUrl, '/') . '/' . $relativePath;

            if (is_dir($itemPath)) {
                $folderPath = $browsePath === '' ? $item : rtrim($browsePath, '/') . '/' . $item;
                $folders[] = [
                    'name' => $item,
                    'path' => $folderPath,
                    'type' => 'folder',
                ];
                continue;
            }

            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            if ($type === 'image' && !in_array($ext, $allowedImageExt, true)) {
                continue;
            }

            if ($type !== 'image' && !in_array($ext, self::ALLOWED_FILE_EXT, true)) {
                continue;
            }

            $stat = stat($itemPath);
            $files[] = [
                'name' => $item,
                'url' => $itemUrl,
                'size' => $stat['size'] ?? 0,
                'modified' => $stat['mtime'] ?? 0,
                'type' => 'file',
                'extension' => $ext,
                'isImage' => in_array($ext, $allowedImageExt, true),
            ];
        }

        usort($files, fn($a, $b) => $b['modified'] <=> $a['modified']);
        usort($folders, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'files' => $files,
            'folders' => $folders,
            'path' => $browsePath,
            'parentPath' => $this->getParentPath($browsePath),
        ];
    }

    private function getParentPath(string $path): ?string
    {
        $path = rtrim($path, '/');
        if ($path === '') {
            return null;
        }
        $parent = dirname($path);
        if ($parent === '.' || $parent === '/') {
            return '__root__';
        }
        return $parent;
    }
}
