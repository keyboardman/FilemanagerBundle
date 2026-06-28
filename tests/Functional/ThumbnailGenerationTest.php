<?php

namespace Keyboardman\FilemanagerBundle\Tests\Functional;

use Keyboardman\FilemanagerBundle\DependencyInjection\LiipImagineConfigurationBuilder;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\Tests\TestKernel;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class ThumbnailGenerationTest extends KernelTestCase
{
    private string $uploadDir;

    private string $publicDir;

    protected function setUp(): void
    {
        if (!\extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for thumbnail generation.');
        }

        parent::setUp();
        self::bootKernel();

        $this->uploadDir = self::getContainer()->getParameter('kernel.cache_dir').'/test_uploads';
        $this->publicDir = self::getContainer()->getParameter('kernel.project_dir').'/public';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        if (!is_dir($this->publicDir)) {
            mkdir($this->publicDir, 0777, true);
        }
    }

    public function testThumbnailIsGeneratedAndCachedOnSecondRequest(): void
    {
        $this->writeTestImage('thumb-test.jpg');

        /** @var CacheManager $cacheManager */
        $cacheManager = self::getContainer()->get(CacheManager::class);
        $filter = LiipImagineConfigurationBuilder::filterSetName('default');

        $this->assertFalse($cacheManager->isStored('thumb-test.jpg', $filter));

        $kernel = self::getContainer()->get('http_kernel');
        $resolveUrl = $cacheManager->generateUrl('thumb-test.jpg', $filter, [], null, 0);

        $first = $kernel->handle(Request::create($resolveUrl));
        $this->assertContains($first->getStatusCode(), [200, 301, 302]);

        $this->assertTrue($cacheManager->isStored('thumb-test.jpg', $filter));

        $second = $kernel->handle(Request::create($resolveUrl));
        $this->assertContains($second->getStatusCode(), [200, 301, 302]);
    }

    public function testThumbnailCacheIsPurgedOnFileDelete(): void
    {
        $this->writeTestImage('purge-test.jpg');

        /** @var CacheManager $cacheManager */
        $cacheManager = self::getContainer()->get(CacheManager::class);
        $filter = LiipImagineConfigurationBuilder::filterSetName('default');
        $resolveUrl = $cacheManager->generateUrl('purge-test.jpg', $filter, [], null, 0);

        $kernel = self::getContainer()->get('http_kernel');
        $kernel->handle(Request::create($resolveUrl));

        $this->assertTrue($cacheManager->isStored('purge-test.jpg', $filter));

        self::getContainer()->get(DiskManager::class)->deleteFile('default', 'purge-test.jpg');

        $this->assertFalse($cacheManager->isStored('purge-test.jpg', $filter));
    }

    public function testThumbnailCacheIsPurgedOnRename(): void
    {
        $this->writeTestImage('rename-test.jpg');

        /** @var CacheManager $cacheManager */
        $cacheManager = self::getContainer()->get(CacheManager::class);
        $filter = LiipImagineConfigurationBuilder::filterSetName('default');
        $resolveUrl = $cacheManager->generateUrl('rename-test.jpg', $filter, [], null, 0);

        $kernel = self::getContainer()->get('http_kernel');
        $kernel->handle(Request::create($resolveUrl));

        $this->assertTrue($cacheManager->isStored('rename-test.jpg', $filter));

        self::getContainer()->get(DiskManager::class)->rename('default', 'rename-test.jpg', 'renamed.jpg');

        $this->assertFalse($cacheManager->isStored('rename-test.jpg', $filter));
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function writeTestImage(string $filename): void
    {
        $path = $this->uploadDir.'/'.$filename;
        $image = imagecreatetruecolor(40, 30);
        $color = imagecolorallocate($image, 200, 100, 50);
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }
}
