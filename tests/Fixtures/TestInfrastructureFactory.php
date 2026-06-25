<?php

namespace Keyboardman\FilemanagerBundle\Tests\Fixtures;

use Keyboardman\FilemanagerBundle\Disk\Disk;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TestInfrastructureFactory
{
    public static function createFilesystem(): Filesystem
    {
        return new Filesystem(new InMemoryFilesystemAdapter());
    }

    public static function createDisk(Filesystem $filesystem): Disk
    {
        return new Disk('default', 'Default', $filesystem, [
            'default_uri' => 'https://example.test/uploads',
        ]);
    }

    public static function createDiskManager(Disk $disk, UrlGeneratorInterface $urlGenerator): DiskManager
    {
        return new DiskManager([$disk], $urlGenerator);
    }
}
