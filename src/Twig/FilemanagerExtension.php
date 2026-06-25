<?php

declare(strict_types=1);

namespace Keyboardman\FilemanagerBundle\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FilemanagerExtension extends AbstractExtension
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('resolve_url', [$this, 'resolveUrl']),
        ];
    }

    public function resolveUrl(string $path, string $filesystem, bool $absolute = false): string
    {
        return $this->urlGenerator->generate(
            'keyboardman_filemanager_media',
            [
                'filesystem' => $filesystem,
                'path' => ltrim($path, '/'),
            ],
            $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }
}
