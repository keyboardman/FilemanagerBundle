<?php

namespace Keyboardman\FilemanagerBundle\Upload;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Gère les uploads fragmentés (chunked) avec assemblage et envoi vers Flysystem.
 */
class ChunkUploadManager
{
    private readonly string $tempBase;

    public function __construct(
        private readonly DiskManager $diskManager,
        private readonly UploadLimitResolver $uploadLimitResolver,
        ?string $tempBase = null,
    ) {
        $this->tempBase = $tempBase ?? sys_get_temp_dir().'/keyboardman-filemanager-chunks';
    }

    /**
     * Reçoit un fragment d'upload et assemble le fichier complet au dernier fragment.
     *
     * @return array<string, mixed> Résultat avec success, uploadId et éventuellement path/url
     *
     * @throws \InvalidArgumentException Si les paramètres ou la séquence sont invalides
     * @throws \RuntimeException         En cas d'erreur d'assemblage ou d'écriture
     */
    public function receiveChunk(
        string $uploadId,
        int $chunkIndex,
        int $totalChunks,
        int $totalSize,
        string $filename,
        string $filesystem,
        string $path,
        UploadedFile $chunk,
    ): array {
        $this->assertValidUploadId($uploadId);
        $this->assertValidFilename($filename);

        if ($chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            throw new \InvalidArgumentException('Index de fragment invalide.');
        }

        if ($totalSize < 1) {
            throw new \InvalidArgumentException('Taille totale invalide.');
        }

        $maxChunkSize = $this->uploadLimitResolver->maxSafeChunkSize();
        $chunkSize = $chunk->getSize();
        if (false !== $chunkSize && $chunkSize > $maxChunkSize) {
            throw new \InvalidArgumentException(sprintf('Le fragment dépasse la taille maximale autorisée (%d octets).', $maxChunkSize));
        }

        $uploadDir = $this->uploadDir($uploadId);
        $metadata = $this->readMetadata($uploadDir);

        if (null === $metadata) {
            if (0 !== $chunkIndex) {
                throw new \InvalidArgumentException('Le premier fragment doit avoir l\'index 0.');
            }

            $metadata = [
                'filename' => $filename,
                'filesystem' => $filesystem,
                'path' => $path,
                'totalChunks' => $totalChunks,
                'totalSize' => $totalSize,
            ];
            $this->ensureUploadDir($uploadDir);
            $this->writeMetadata($uploadDir, $metadata);
        } else {
            $this->assertMetadataMatches($metadata, $filename, $filesystem, $path, $totalChunks, $totalSize);
        }

        $receivedChunks = $this->countReceivedChunks($uploadDir);

        if ($chunkIndex > $receivedChunks) {
            throw new \InvalidArgumentException(sprintf('Fragment %d reçu hors séquence (attendu: %d).', $chunkIndex, $receivedChunks));
        }

        if ($chunkIndex === $receivedChunks) {
            $chunkPath = $this->chunkPath($uploadDir, $chunkIndex);
            $chunk->move(dirname($chunkPath), basename($chunkPath));
        }

        if ($chunkIndex !== $totalChunks - 1) {
            return [
                'success' => true,
                'uploadId' => $uploadId,
                'chunkIndex' => $chunkIndex,
                'received' => true,
            ];
        }

        if ($this->countReceivedChunks($uploadDir) !== $totalChunks) {
            throw new \InvalidArgumentException('Tous les fragments n\'ont pas été reçus.');
        }

        $assembledPath = $this->assembleChunks($uploadDir, $totalChunks);

        try {
            $targetPath = $this->diskManager->upload(
                $metadata['filesystem'],
                $metadata['path'],
                $assembledPath,
                $metadata['filename']
            );

            $size = filesize($assembledPath) ?: 0;
            $mimeType = mime_content_type($assembledPath) ?: 'application/octet-stream';

            return [
                'success' => true,
                'uploadId' => $uploadId,
                'chunkIndex' => $chunkIndex,
                'received' => true,
                'path' => $targetPath,
                'name' => $metadata['filename'],
                'mimeType' => $mimeType,
                'size' => $size,
                'url' => $this->diskManager->publicUrl($metadata['filesystem'], $targetPath),
            ];
        } finally {
            $this->cleanup($uploadDir);
        }
    }

    private function assertValidUploadId(string $uploadId): void
    {
        if (!preg_match('/^[a-f0-9-]{36}$/i', $uploadId)) {
            throw new \InvalidArgumentException('Identifiant d\'upload invalide.');
        }
    }

    private function assertValidFilename(string $filename): void
    {
        if ('' === trim($filename) || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new \InvalidArgumentException('Nom de fichier invalide.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function assertMetadataMatches(
        array $metadata,
        string $filename,
        string $filesystem,
        string $path,
        int $totalChunks,
        int $totalSize,
    ): void {
        if (
            $metadata['filename'] !== $filename
            || $metadata['filesystem'] !== $filesystem
            || $metadata['path'] !== $path
            || $metadata['totalChunks'] !== $totalChunks
            || $metadata['totalSize'] !== $totalSize
        ) {
            throw new \InvalidArgumentException('Les métadonnées du fragment ne correspondent pas à l\'upload en cours.');
        }
    }

    private function uploadDir(string $uploadId): string
    {
        return $this->tempBase.'/'.$uploadId;
    }

    private function ensureUploadDir(string $uploadDir): void
    {
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0700, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException('Impossible de créer le répertoire temporaire d\'upload.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readMetadata(string $uploadDir): ?array
    {
        $metadataPath = $uploadDir.'/metadata.json';
        if (!is_file($metadataPath)) {
            return null;
        }

        $content = file_get_contents($metadataPath);
        if (false === $content) {
            throw new \RuntimeException('Impossible de lire les métadonnées d\'upload.');
        }

        $metadata = json_decode($content, true);
        if (!is_array($metadata)) {
            throw new \RuntimeException('Métadonnées d\'upload invalides.');
        }

        return $metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function writeMetadata(string $uploadDir, array $metadata): void
    {
        $metadataPath = $uploadDir.'/metadata.json';
        if (false === file_put_contents($metadataPath, json_encode($metadata, JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('Impossible d\'écrire les métadonnées d\'upload.');
        }
    }

    private function chunkPath(string $uploadDir, int $chunkIndex): string
    {
        return sprintf('%s/chunk_%06d', $uploadDir, $chunkIndex);
    }

    private function countReceivedChunks(string $uploadDir): int
    {
        if (!is_dir($uploadDir)) {
            return 0;
        }

        return count(glob($uploadDir.'/chunk_*') ?: []);
    }

    private function assembleChunks(string $uploadDir, int $totalChunks): string
    {
        $assembledPath = $uploadDir.'/assembled';
        $output = fopen($assembledPath, 'wb');
        if (false === $output) {
            throw new \RuntimeException('Impossible de créer le fichier assemblé.');
        }

        try {
            for ($i = 0; $i < $totalChunks; ++$i) {
                $chunkPath = $this->chunkPath($uploadDir, $i);
                if (!is_file($chunkPath)) {
                    throw new \RuntimeException(sprintf('Fragment manquant: %d.', $i));
                }

                $input = fopen($chunkPath, 'rb');
                if (false === $input) {
                    throw new \RuntimeException(sprintf('Impossible de lire le fragment %d.', $i));
                }

                stream_copy_to_stream($input, $output);
                fclose($input);
            }
        } finally {
            fclose($output);
        }

        return $assembledPath;
    }

    private function cleanup(string $uploadDir): void
    {
        if (!is_dir($uploadDir)) {
            return;
        }

        foreach (glob($uploadDir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($uploadDir);
    }
}
