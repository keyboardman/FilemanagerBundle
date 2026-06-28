<?php

namespace Keyboardman\FilemanagerBundle\Disk;

use Keyboardman\FilemanagerBundle\Media\Streaming\FlysystemAdapterExtractor;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Gestionnaire central des disques Flysystem : listage, upload, suppression et URLs publiques.
 */
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

    /**
     * @param iterable<Disk> $disks
     */
    public function __construct(
        iterable $disks,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        foreach ($disks as $disk) {
            $this->disks[$disk->getName()] = $disk;
        }
    }

    /**
     * Retourne un disque par son nom.
     *
     * @throws \InvalidArgumentException Si le disque est introuvable
     */
    public function disk(string $name): Disk
    {
        if (!isset($this->disks[$name])) {
            throw new \InvalidArgumentException("Disk '$name' not found");
        }

        return $this->disks[$name];
    }

    /**
     * @return array<string, Disk>
     */
    public function all(): array
    {
        return $this->disks;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->disks);
    }

    /** Indique si un disque est enregistré sous le nom donné. */
    public function has(string $name): bool
    {
        return isset($this->disks[$name]);
    }

    /**
     * Liste le contenu d'un répertoire avec filtrage média et tri.
     *
     * @param string      $filesystem Identifiant du disque
     * @param string      $path       Chemin du répertoire
     * @param string|null $media      Filtre par type média (image, audio, video)
     * @param string      $sort       Ordre de tri (name_asc ou name_desc)
     *
     * @return list<array<string, mixed>>
     *
     * @throws \RuntimeException En cas d'erreur Flysystem
     */
    public function list(string $filesystem, string $path, ?string $media = null, string $sort = 'name_asc'): array
    {
        $fs = $this->disk($filesystem)->filesystem();

        $items = [];

        try {
            foreach ($this->iterateListing($fs, $path, false) as $item) {
                $itemPath = $item->path();

                // ✅ Ignorer fichiers et dossiers cachés
                if ($this->isHidden($itemPath)) {
                    continue;
                }

                $allowedMedia = ['image', 'audio', 'video'];

                if ($item instanceof FileAttributes) {
                    $mimeType = $this->resolveMimeType($filesystem, $itemPath, $item->mimeType());

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
                } else {
                    continue;
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

    /**
     * @return iterable<\League\Flysystem\StorageAttributes>
     */
    private function iterateListing(\League\Flysystem\FilesystemOperator $filesystem, string $path, bool $deep): iterable
    {
        $s3Lister = SafeAsyncAwsS3Lister::tryFromAdapter(
            FlysystemAdapterExtractor::extractAsyncAwsS3Adapter($filesystem),
        );

        $listing = null !== $s3Lister
            ? $s3Lister->listContents($path, $deep)
            : $filesystem->listContents($path, $deep);

        $seenPaths = [];

        foreach ($listing as $item) {
            $itemPath = $item->path();
            if (isset($seenPaths[$itemPath])) {
                break;
            }

            $seenPaths[$itemPath] = true;
            yield $item;
        }
    }

    /** Vérifie si un répertoire existe sur le disque donné. */
    public function directoryExists(string $filesystem, string $path): bool
    {
        $normalizedPath = trim($path, '/');
        if ('' === $normalizedPath) {
            return true;
        }

        $fs = $this->disk($filesystem)->filesystem();
        $s3Lister = SafeAsyncAwsS3Lister::tryFromAdapter(
            FlysystemAdapterExtractor::extractAsyncAwsS3Adapter($fs),
        );

        if (null !== $s3Lister) {
            return $s3Lister->directoryExists($normalizedPath);
        }

        return $fs->directoryExists($normalizedPath);
    }

    /**
     * Upload un fichier local vers le disque.
     *
     * @param string      $filesystem    Identifiant du disque
     * @param string      $path          Répertoire de destination
     * @param string      $localFilePath Chemin du fichier source sur le disque local
     * @param string|null $newFilename   Nom de fichier cible (basename par défaut)
     *
     * @return string Chemin final du fichier sur le disque
     *
     * @throws \InvalidArgumentException Si le fichier source est invalide
     * @throws \RuntimeException         En cas d'erreur Flysystem
     */
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
        $targetPath = ltrim(rtrim($path, '/').'/'.$filename, '/');

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

    /**
     * Crée un sous-répertoire dans le chemin parent donné.
     *
     * @return string Chemin du nouveau répertoire (avec slash final)
     *
     * @throws \InvalidArgumentException Si le nom est invalide
     * @throws \RuntimeException         Si le répertoire existe déjà ou en cas d'erreur Flysystem
     */
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

    /**
     * Supprime un fichier du disque.
     *
     * @throws \InvalidArgumentException Si le chemin est invalide
     * @throws \RuntimeException         Si le fichier est introuvable ou en cas d'erreur Flysystem
     */
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

    /**
     * Supprime un répertoire vide.
     *
     * @throws \InvalidArgumentException Si le chemin est invalide
     * @throws \RuntimeException         Si le répertoire n'est pas vide ou est introuvable
     */
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

            foreach ($this->iterateListing($fs, $normalizedPath, false) as $_item) {
                throw new \RuntimeException('Le dossier doit être vide pour être supprimé.');
            }

            $fs->deleteDirectory($normalizedPath);
        } catch (FilesystemException $e) {
            throw new \RuntimeException('Erreur lors de la suppression du dossier : '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Retourne l'URL publique d'un fichier (directe, signée ou via proxy).
     *
     * @param bool $absolute Génère une URL absolue si true
     */
    public function publicUrl(string $filesystem, string $path, bool $absolute = false): string
    {
        $disk = $this->disk($filesystem);
        $path = ltrim($path, '/');

        if (!$disk->usesProxyMedia() && ($base = $disk->getDefaultUri())) {
            $url = rtrim($base, '/').'/'.$path;
            if (!$this->isMediaProxyUrl($url)) {
                return $url;
            }
        }

        return $this->mediaProxyUrl($filesystem, $path, $absolute);
    }

    /**
     * URL S3 directe ou signée pour servir le média (redirect MediaController).
     * Retourne null si le disk est configuré pour proxifier le flux (proxy_media).
     */
    public function resolveDirectMediaUrl(string $filesystem, string $path): ?string
    {
        $disk = $this->disk($filesystem);
        $path = ltrim($path, '/');

        if ($disk->usesProxyMedia()) {
            return null;
        }

        if ($disk->usesSignedUrls() || FlysystemAdapterExtractor::supportsTemporaryUrls($disk->filesystem())) {
            $signedUrl = $this->generateSignedUrl($disk, $path);
            if (null !== $signedUrl) {
                return $signedUrl;
            }
        }

        if ($base = $disk->getDefaultUri()) {
            $url = rtrim($base, '/').'/'.$path;
            if (!$this->isMediaProxyUrl($url)) {
                return $url;
            }
        }

        return null;
    }

    private function mediaProxyUrl(string $filesystem, string $path, bool $absolute): string
    {
        return $this->urlGenerator->generate('keyboardman_filemanager_media', [
            'filesystem' => $filesystem,
            'path' => ltrim($path, '/'),
        ], $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    private function isMediaProxyUrl(string $url): bool
    {
        return str_contains($url, '/kbd/filemanager/media/');
    }

    private function generateSignedUrl(Disk $disk, string $path): ?string
    {
        $filesystem = $disk->filesystem();
        if (!$filesystem instanceof Filesystem) {
            return null;
        }

        try {
            $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d seconds', $disk->getSignedUrlTtl()));

            return $filesystem->temporaryUrl($path, $expiresAt);
        } catch (UnableToGenerateTemporaryUrl) {
            return null;
        }
    }

    /**
     * Résout le type MIME d'un fichier (détecté, extension ou Flysystem).
     */
    public function resolveMimeType(string $filesystem, string $path, ?string $detectedMimeType = null): string
    {
        if (is_string($detectedMimeType) && '' !== $detectedMimeType) {
            return $detectedMimeType;
        }

        $fromExtension = $this->mimeTypeFromExtension($path);
        if (null !== $fromExtension) {
            return $fromExtension;
        }

        try {
            $mimeType = $this->disk($filesystem)->filesystem()->mimeType(ltrim($path, '/'));
            if ('' !== $mimeType) {
                return $mimeType;
            }
        } catch (FilesystemException) {
        }

        return 'application/octet-stream';
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
