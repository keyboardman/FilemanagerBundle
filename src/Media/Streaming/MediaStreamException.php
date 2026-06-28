<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

/**
 * Exception levée lors d'un échec de lecture de flux média.
 */
final class MediaStreamException extends \RuntimeException
{
    /** Crée une exception pour un échec de lecture du chemin donné. */
    public static function readFailed(string $path, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Unable to read media stream for "%s".', $path), 0, $previous);
    }
}
