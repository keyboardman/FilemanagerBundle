<?php

declare(strict_types=1);

namespace Keyboardman\FilemanagerBundle\Twig;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

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

    public function resolveUrl(string $path, string $filesystem): string
    {
        return $this->diskManager->publicUrl($filesystem, $path);
    }
}