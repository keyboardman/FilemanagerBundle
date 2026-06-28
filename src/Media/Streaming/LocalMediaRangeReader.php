<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

/**
 * Lecteur de range pour les fichiers locaux via fseek sur le flux Flysystem.
 */
final class LocalMediaRangeReader implements MediaRangeReaderInterface
{
    public function supports(FilesystemOperator $filesystem): bool
    {
        return FlysystemAdapterExtractor::isLocalAdapter($filesystem);
    }

    public function readRange(FilesystemOperator $filesystem, string $path, int $start, int $length): callable
    {
        return function () use ($filesystem, $path, $start, $length): void {
            try {
                $stream = $filesystem->readStream($path);
            } catch (FilesystemException $exception) {
                throw MediaStreamException::readFailed($path, $exception);
            }

            if (!is_resource($stream)) {
                throw MediaStreamException::readFailed($path);
            }

            try {
                if ($start > 0 && 0 !== fseek($stream, $start)) {
                    throw MediaStreamException::readFailed($path);
                }

                $remaining = $length;
                while ($remaining > 0 && !feof($stream)) {
                    $chunk = fread($stream, min(8192, $remaining));
                    if (false === $chunk) {
                        break;
                    }

                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
            } finally {
                fclose($stream);
            }
        };
    }
}
