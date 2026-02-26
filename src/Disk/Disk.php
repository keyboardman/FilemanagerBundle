<?php

namespace Keyboardman\FilemanagerBundle\Disk;

use League\Flysystem\FilesystemOperator;

class Disk
{
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
}
