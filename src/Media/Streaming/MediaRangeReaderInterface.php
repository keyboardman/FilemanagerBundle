<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use League\Flysystem\FilesystemOperator;

/**
 * Contrat pour la lecture partielle (byte range) d'un fichier média.
 */
interface MediaRangeReaderInterface
{
    /** Indique si ce lecteur prend en charge le filesystem donné. */
    public function supports(FilesystemOperator $filesystem): bool;

    /**
     * @return callable(): void Outputs the requested byte range
     *
     * @throws MediaStreamException
     */
    public function readRange(FilesystemOperator $filesystem, string $path, int $start, int $length): callable;
}
