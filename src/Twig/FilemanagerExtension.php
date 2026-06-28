<?php

declare(strict_types=1);

namespace Keyboardman\FilemanagerBundle\Twig;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension Twig fournissant le filtre resolve_url pour les URLs publiques des fichiers.
 */
class FilemanagerExtension extends AbstractExtension
{
    public function __construct(private readonly DiskManager $diskManager)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('resolve_url', [$this, 'resolveUrl']),
        ];
    }

    /** Résout l'URL publique d'un fichier sur le disque donné. */
    public function resolveUrl(string $path, string $filesystem, bool $absolute = false): string
    {
        return $this->diskManager->publicUrl($filesystem, $path, $absolute);
    }
}
