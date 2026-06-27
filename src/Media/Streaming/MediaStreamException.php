<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

final class MediaStreamException extends \RuntimeException
{
    public static function readFailed(string $path, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Unable to read media stream for "%s".', $path), 0, $previous);
    }
}
