<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use League\Flysystem\FilesystemOperator;

interface MediaRangeReaderInterface
{
    public function supports(FilesystemOperator $filesystem): bool;

    /**
     * @return callable(): void Outputs the requested byte range
     *
     * @throws MediaStreamException
     */
    public function readRange(FilesystemOperator $filesystem, string $path, int $start, int $length): callable;
}
