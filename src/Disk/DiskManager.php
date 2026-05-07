<?php

namespace Keyboardman\FilemanagerBundle\Disk;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;

class DiskManager
{
    /** @var Disk[] */
    private array $disks = [];

    private const HIDDEN_PREFIX = '.';

    private const EXTENSION_MIME = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'bmp' => 'image/bmp', 'ico' => 'image/x-icon',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4',
        'aac' => 'audio/aac', 'flac' => 'audio/flac',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'avi' => 'video/x-msvideo', 'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska', 'm4v' => 'video/x-m4v',
        'txt' => 'text/plain', 'json' => 'application/json', 'pdf' => 'application/pdf',
    ];

    public function __construct(iterable $disks)
    {
        foreach ($disks as $disk) {
            $this->disks[$disk->getName()] = $disk;
        }
    }

    public function disk(string $name): Disk
    {
        if (!isset($this->disks[$name])) {
            throw new \InvalidArgumentException("Disk '$name' not found");
        }

        return $this->disks[$name];
    }

    public function all(): array
    {
        return $this->disks;
    }

    public function names(): array
    {
        return array_keys($this->disks);
    }

    public function has(string $name): bool
    {
        return isset($this->disks[$name]);
    }

    public function list(string $filesystem, string $path, ?string $media = null, string $sort = 'name_asc'): array
    {
        $fs = $this->disk($filesystem)->filesystem();

        $items = [];

        try {
            foreach ($fs->listContents($path, false) as $item) {
                \assert($item instanceof StorageAttributes);

                $itemPath = $item->path();

                // ✅ Ignorer fichiers et dossiers cachés
                if ($this->isHidden($itemPath)) {
                    continue;
                }

                $allowedMedia = ['image', 'audio', 'video'];

                if ($item instanceof FileAttributes) {
                    $mimeType = $item->mimeType() ?? $this->mimeTypeFromExtension($itemPath);

                    // Déterminer le type de média
                    if (str_starts_with($mimeType, 'image/')) {
                        $type = 'image';
                    } elseif (str_starts_with($mimeType, 'audio/')) {
                        $type = 'audio';
                    } elseif (str_starts_with($mimeType, 'video/')) {
                        $type = 'video';
                    } else {
                        $type = 'other';
                    }

                    // Filtrer si un type média est défini
                    if ($media && $media !== $type) {
                        continue;
                    } elseif (!$media && !in_array($type, $allowedMedia, true)) {
                        continue;
                    }

                    $file = [
                        'path' => $itemPath,
                        'name' => basename($itemPath),
                        'type' => 'file',
                        'mimeType' => $mimeType,
                        'mediaType' => $type, // ajouté pour Twig/JS
                        'size' => $item->fileSize(),
                    ];
                } elseif ($item instanceof DirectoryAttributes) {
                    $dirPath = rtrim($itemPath, '/').'/';
                    $file = [
                        'path' => $dirPath,
                        'name' => basename(rtrim($dirPath, '/')),
                        'type' => 'dir',
                    ];
                }

                $items[] = $file;
            }
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf("Unable to list contents of '%s' on disk '%s'", $path, $filesystem), previous: $e);
        }

        // Tri par nom
        usort($items, function ($a, $b) use ($sort) {
            $nameA = strtolower($a['name']);
            $nameB = strtolower($b['name']);

            return 'name_desc' === $sort ? strcmp($nameB, $nameA) : strcmp($nameA, $nameB);
        });

        return $items;
    }

    public function upload(
        string $filesystem,
        string $path,
        string $localFilePath,
        ?string $newFilename = null,
    ): string {
        $disk = $this->disk($filesystem);
        $fs = $disk->filesystem();

        if (!file_exists($localFilePath) || !is_file($localFilePath)) {
            throw new \InvalidArgumentException("File '$localFilePath' does not exist or is not a file.");
        }

        $filename = $newFilename ?? basename($localFilePath);
        $targetPath = rtrim($path, '/').'/'.$filename;

        // Déterminer le mime type
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeType = self::EXTENSION_MIME[$ext] ?? mime_content_type($localFilePath);

        try {
            $stream = fopen($localFilePath, 'rb');
            if (false === $stream) {
                throw new \RuntimeException("Failed to open file '$localFilePath' for reading.");
            }

            $fs->writeStream($targetPath, $stream, [
                'mimetype' => $mimeType,
            ]);

            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf("Unable to upload file '%s' to '%s' on disk '%s'", $localFilePath, $targetPath, $filesystem), previous: $e);
        }

        return $targetPath;
    }

    /**
     * Renommer un fichier ou dossier.
     */
    public function rename(string $filesystem, string $oldPath, string $newName): bool
    {
        try {
            $disk = $this->disk($filesystem);
            $fs = $disk->filesystem();

            // Normaliser les chemins
            $oldPath = trim($oldPath, '/');

            if (!$fs->fileExists($oldPath) && !$fs->directoryExists($oldPath)) {
                throw new \RuntimeException('Le fichier ou dossier n\'existe pas.');
            }

            // Récupérer le dossier parent
            $directory = pathinfo($oldPath, PATHINFO_DIRNAME);
            $directory = '.' === $directory ? '' : $directory;

            $this->assertValidDirectoryName($newName);

            $newPath = ltrim($directory.'/'.$newName, '/');

            // Vérifier si le nouveau nom existe déjà
            if ($fs->fileExists($newPath) || $fs->directoryExists($newPath)) {
                throw new \RuntimeException('Un fichier avec ce nom existe déjà.');
            }

            // Déplacement = rename en Flysystem
            $fs->move($oldPath, $newPath);

            return true;
        } catch (FilesystemException $e) {
            throw new \RuntimeException('Erreur lors du renommage : '.$e->getMessage());
        }
    }

    public function createDirectory(string $filesystem, string $parentPath, string $directoryName): string
    {
        $this->assertValidDirectoryName($directoryName);

        try {
            $disk = $this->disk($filesystem);
            $fs = $disk->filesystem();

            $parentPath = trim($parentPath, '/');
            $newPath = ltrim(trim($parentPath.'/'.$directoryName, '/'), '/');

            if ('' === $newPath) {
                throw new \InvalidArgumentException('Le chemin du dossier est invalide.');
            }

            if ($fs->fileExists($newPath) || $fs->directoryExists($newPath)) {
                throw new \RuntimeException('Un fichier ou dossier avec ce nom existe déjà.');
            }

            $fs->createDirectory($newPath);

            return rtrim($newPath, '/').'/';
        } catch (FilesystemException $e) {
            throw new \RuntimeException('Erreur lors de la création du dossier : '.$e->getMessage(), previous: $e);
        }
    }

    public function deleteFile(string $filesystem, string $path): void
    {
        $normalizedPath = trim($path, '/');
        if ('' === $normalizedPath) {
            throw new \InvalidArgumentException('Le chemin du fichier est invalide.');
        }

        try {
            $fs = $this->disk($filesystem)->filesystem();

            if (!$fs->fileExists($normalizedPath)) {
                throw new \RuntimeException('Le fichier cible est introuvable.');
            }

            $fs->delete($normalizedPath);
        } catch (FilesystemException $e) {
            throw new \RuntimeException('Erreur lors de la suppression du fichier : '.$e->getMessage(), previous: $e);
        }
    }

    public function deleteEmptyDirectory(string $filesystem, string $path): void
    {
        $normalizedPath = trim($path, '/');
        if ('' === $normalizedPath) {
            throw new \InvalidArgumentException('Le chemin du dossier est invalide.');
        }

        try {
            $fs = $this->disk($filesystem)->filesystem();

            if (!$fs->directoryExists($normalizedPath)) {
                throw new \RuntimeException('Le dossier cible est introuvable.');
            }

            foreach ($fs->listContents($normalizedPath, false) as $_item) {
                throw new \RuntimeException('Le dossier doit être vide pour être supprimé.');
            }

            $fs->deleteDirectory($normalizedPath);
        } catch (FilesystemException $e) {
            throw new \RuntimeException('Erreur lors de la suppression du dossier : '.$e->getMessage(), previous: $e);
        }
    }

    public function publicUrl(string $filesystem, string $path): string
    {
        $disk = $this->disk($filesystem);

        $fs = $disk->filesystem();

        $path = ltrim($path, '/');

        // ✅ 1. priorité : config du disk
        if ($base = $disk->getDefaultUri()) {
            return rtrim($base, '/').'/'.$path;
        }

        if (method_exists($fs, 'publicUrl')) {
            try {
                return $fs->publicUrl($path);
            } catch (\Throwable) {
            }
        }

        // Fallback simple : servir depuis public/
        return '/'.$path;
    }

    private function mimeTypeFromExtension(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        return self::EXTENSION_MIME[$ext] ?? null;
    }

    private function isHidden(string $key): bool
    {
        $basename = basename($key);

        return '' !== $basename && str_starts_with($basename, self::HIDDEN_PREFIX);
    }

    private function assertValidDirectoryName(string $name): void
    {
        $trimmed = trim($name);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException('Le nom du dossier ne peut pas être vide.');
        }

        if (in_array($trimmed, ['.', '..'], true)) {
            throw new \InvalidArgumentException('Le nom du dossier est invalide.');
        }

        if (str_contains($trimmed, '/')
            || str_contains($trimmed, '\\')
            || preg_match('/[\x00-\x1F]/', $trimmed)) {
            throw new \InvalidArgumentException('Le nom du dossier contient des caractères interdits.');
        }
    }
}
