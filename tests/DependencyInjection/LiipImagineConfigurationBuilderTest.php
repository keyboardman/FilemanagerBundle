<?php

namespace Keyboardman\FilemanagerBundle\Tests\DependencyInjection;

use Keyboardman\FilemanagerBundle\DependencyInjection\LiipImagineConfigurationBuilder;
use PHPUnit\Framework\TestCase;

class LiipImagineConfigurationBuilderTest extends TestCase
{
    public function testBuildsLoaderAndFilterSetPerDisk(): void
    {
        $config = LiipImagineConfigurationBuilder::build(
            [
                'default' => ['label' => 'Default', 'storage' => ['local' => ['directory' => '/tmp']]],
                'media' => ['label' => 'Media', 'storage' => ['local' => ['directory' => '/tmp/media']]],
            ],
            ['max_size' => 320, 'quality' => 82],
        );

        $this->assertSame('gd', $config['driver']);
        $this->assertArrayHasKey('filemanager_default', $config['loaders']);
        $this->assertArrayHasKey('filemanager_media', $config['loaders']);
        $this->assertSame(
            'keyboardman_filemanager.default.storage',
            $config['loaders']['filemanager_default']['flysystem']['filesystem_service'],
        );
        $this->assertArrayHasKey('filemanager_thumb_default', $config['filter_sets']);
        $this->assertArrayHasKey('filemanager_thumb_media', $config['filter_sets']);
        $this->assertSame(82, $config['filter_sets']['filemanager_thumb_default']['quality']);
        $this->assertSame(
            [320, 320],
            $config['filter_sets']['filemanager_thumb_default']['filters']['thumbnail']['size'],
        );
        $this->assertSame(
            'media/cache/filemanager',
            $config['resolvers']['filemanager_thumbs']['web_path']['cache_prefix'],
        );
    }

    public function testReturnsEmptyConfigWhenNoDisks(): void
    {
        $this->assertSame([], LiipImagineConfigurationBuilder::build([], ['max_size' => 320, 'quality' => 82]));
    }
}
