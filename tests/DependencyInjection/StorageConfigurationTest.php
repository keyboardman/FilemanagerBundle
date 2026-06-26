<?php

namespace Keyboardman\FilemanagerBundle\Tests\DependencyInjection;

use Keyboardman\FilemanagerBundle\DependencyInjection\KeyboardmanFilemanagerExtension;
use Keyboardman\FilemanagerBundle\Disk\Disk;
use Keyboardman\FilemanagerBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StorageConfigurationTest extends KernelTestCase
{
    public function testDiskUsesAutoRegisteredFlysystemStorage(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->assertTrue($container->has(KeyboardmanFilemanagerExtension::storageServiceId('default')));

        $disk = $container->get('keyboardman_filemanager.disk.default');
        $this->assertInstanceOf(Disk::class, $disk);

        $filesystem = $disk->filesystem();
        $filesystem->write('probe.txt', 'ok');

        $this->assertTrue($filesystem->fileExists('probe.txt'));
        $this->assertSame('ok', $filesystem->read('probe.txt'));
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
