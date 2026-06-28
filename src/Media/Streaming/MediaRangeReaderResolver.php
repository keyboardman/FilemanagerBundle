<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use League\Flysystem\FilesystemOperator;

/**
 * Sélectionne le lecteur de range adapté au type d'adaptateur Flysystem.
 */
final class MediaRangeReaderResolver
{
    /**
     * @param iterable<MediaRangeReaderInterface> $readers Ordered by priority (most specific first)
     */
    public function __construct(private readonly iterable $readers)
    {
    }

    /**
     * Retourne un callable qui émet les octets demandés.
     *
     * @throws MediaStreamException Si aucun lecteur ne peut lire le fichier
     */
    public function readRange(FilesystemOperator $filesystem, string $path, int $start, int $length): callable
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($filesystem)) {
                return $reader->readRange($filesystem, $path, $start, $length);
            }
        }

        throw MediaStreamException::readFailed($path);
    }
}
