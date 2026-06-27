<?php

namespace Keyboardman\FilemanagerBundle\Disk;

use League\Flysystem\FilesystemOperator;

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

    public function filesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDefaultUri(): ?string
    {
        return $this->config['default_uri'] ?? null;
    }

    public function usesSignedUrls(): bool
    {
        return (bool) ($this->config['signed_urls'] ?? false);
    }

    public function getSignedUrlTtl(): int
    {
        return (int) ($this->config['signed_url_ttl'] ?? 3600);
    }

    public function usesProxyMedia(): bool
    {
        return (bool) ($this->config['proxy_media'] ?? false);
    }
}
