<?php

namespace Keyboardman\FilemanagerBundle\Disk;

use League\Flysystem\FilesystemOperator;

/**
 * Représente un disque logique du filemanager avec sa configuration Flysystem.
 */
class Disk
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private string $name,
        private string $label,
        private FilesystemOperator $filesystem,
        private array $config,
    ) {
    }

    /** Retourne l'opérateur Flysystem associé à ce disque. */
    public function filesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    /** Retourne l'identifiant technique du disque. */
    public function getName(): string
    {
        return $this->name;
    }

    /** Retourne le libellé affiché dans l'interface. */
    public function getLabel(): string
    {
        return $this->label;
    }

    /** Retourne l'URL publique de base du disque, ou null si non configurée. */
    public function getDefaultUri(): ?string
    {
        return $this->config['default_uri'] ?? null;
    }

    /** Indique si les URLs présignées S3 sont activées pour ce disque. */
    public function usesSignedUrls(): bool
    {
        return (bool) ($this->config['signed_urls'] ?? false);
    }

    /** Retourne la durée de validité des URLs présignées en secondes. */
    public function getSignedUrlTtl(): int
    {
        return (int) ($this->config['signed_url_ttl'] ?? 3600);
    }

    /** Indique si les médias doivent transiter par le proxy Symfony. */
    public function usesProxyMedia(): bool
    {
        return (bool) ($this->config['proxy_media'] ?? false);
    }
}
