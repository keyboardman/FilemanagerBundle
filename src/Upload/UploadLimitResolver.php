<?php

namespace Keyboardman\FilemanagerBundle\Upload;

/**
 * Calcule les limites d'upload fragmenté en fonction des directives PHP ini.
 */
class UploadLimitResolver
{
    private const SAFETY_RATIO = 0.85;

    private const MIN_CHUNK_SIZE = 262144; // 256 Ko

    /**
     * Ajuste la taille et le seuil des fragments selon upload_max_filesize et post_max_size.
     *
     * @return array{chunk_size: int, chunk_threshold: int}
     */
    public function resolve(int $configuredChunkSize, int $configuredChunkThreshold): array
    {
        $maxSafeChunk = $this->maxSafeChunkSize();

        $chunkSize = min($configuredChunkSize, $maxSafeChunk);
        $chunkThreshold = min($configuredChunkThreshold, $maxSafeChunk);

        return [
            'chunk_size' => max(self::MIN_CHUNK_SIZE, $chunkSize),
            'chunk_threshold' => max(self::MIN_CHUNK_SIZE, $chunkThreshold),
        ];
    }

    /** Retourne la taille maximale sûre d'un fragment en octets. */
    public function maxSafeChunkSize(): int
    {
        $uploadMax = $this->parseIniSize(ini_get('upload_max_filesize') ?: '2M');
        $postMax = $this->parseIniSize(ini_get('post_max_size') ?: '8M');
        $limit = min($uploadMax, $postMax);

        if (PHP_INT_MAX === $limit) {
            return 5 * 1024 * 1024;
        }

        return max(self::MIN_CHUNK_SIZE, (int) floor($limit * self::SAFETY_RATIO));
    }

    private function parseIniSize(string $value): int
    {
        $value = trim($value);

        if ('' === $value || '-1' === $value) {
            return PHP_INT_MAX;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        return match ($last) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
