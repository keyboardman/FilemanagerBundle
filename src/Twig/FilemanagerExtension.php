<?php

declare(strict_types=1);

namespace Keyboardman\FilemanagerBundle\Twig;

use Keyboardman\FilemanagerBundle\DependencyInjection\LiipImagineConfigurationBuilder;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension Twig fournissant les filtres resolve_url et imagine_thumbnail.
 */
class FilemanagerExtension extends AbstractExtension
{
    public function __construct(
        private readonly DiskManager $diskManager,
        private readonly ?CacheManager $cacheManager = null,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('resolve_url', [$this, 'resolveUrl']),
            new TwigFilter('imagine_thumbnail', [$this, 'imagineThumbnail']),
        ];
    }

    /** Résout l'URL publique d'un fichier sur le disque donné. */
    public function resolveUrl(string $path, string $filesystem, bool $absolute = false): string
    {
        return $this->diskManager->publicUrl($filesystem, $path, $absolute);
    }

    /** Résout l'URL miniature LiipImagine, ou l'URL média originale en fallback. */
    public function imagineThumbnail(string $path, string $filesystem, bool $absolute = false): string
    {
        if (null === $this->cacheManager) {
            return $this->resolveUrl($path, $filesystem, $absolute);
        }

        $referenceType = $absolute
            ? UrlGeneratorInterface::ABSOLUTE_URL
            : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->cacheManager->getBrowserPath(
            $path,
            LiipImagineConfigurationBuilder::filterSetName($filesystem),
            [],
            null,
            $referenceType,
        );
    }
}
