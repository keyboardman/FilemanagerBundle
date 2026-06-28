<?php

namespace Keyboardman\FilemanagerBundle\Disk;

/**
 * Détection des entrées cachées (nom commençant par un point).
 */
final class HiddenPath
{
    private const HIDDEN_PREFIX = '.';

    public static function isHidden(string $path): bool
    {
        $basename = basename(trim($path, '/'));

        return '' !== $basename && str_starts_with($basename, self::HIDDEN_PREFIX);
    }
}
