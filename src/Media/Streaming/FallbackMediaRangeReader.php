<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

/**
 * Reads from the beginning of the stream and skips bytes — slow for large offsets but works on non-seekable adapters.
 */
final class FallbackMediaRangeReader implements MediaRangeReaderInterface
{
    public function supports(FilesystemOperator $filesystem): bool
    {
        return true;
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
                $skipped = 0;
                while ($skipped < $start && !feof($stream)) {
                    $toSkip = min(8192, $start - $skipped);
                    $chunk = fread($stream, $toSkip);
                    if (false === $chunk || '' === $chunk) {
                        break;
                    }

                    $skipped += strlen($chunk);
                }

                if ($skipped < $start) {
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
