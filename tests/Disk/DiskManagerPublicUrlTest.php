<?php

namespace Keyboardman\FilemanagerBundle\Tests\Disk;

use AsyncAws\S3\S3Client;
use Keyboardman\FilemanagerBundle\Disk\Disk;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\Twig\FilemanagerExtension;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DiskManagerPublicUrlTest extends TestCase
{
    public function testSignedUrlUsesTemporaryUrlGenerator(): void
    {
        $client = new S3Client([
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'region' => 'eu-west-1',
        ], null, new MockHttpClient());

        $filesystem = new Filesystem(new AsyncAwsS3Adapter($client, 'media-bucket', 'uploads'));
        $disk = new Disk('s3', 'S3', $filesystem, [
            'signed_urls' => true,
            'signed_url_ttl' => 120,
        ]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $manager = new DiskManager([$disk], $urlGenerator);

        $url = $manager->publicUrl('s3', 'clip.mp4');

        $this->assertStringContainsString('media-bucket', $url);
        $this->assertStringContainsString('clip.mp4', $url);
        $this->assertStringContainsString('X-Amz-Signature=', $url);
    }

    public function testAsyncAwsDiskUsesSignedUrlByDefaultWithoutProxy(): void
    {
        $client = new S3Client([
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'region' => 'eu-west-1',
        ], null, new MockHttpClient());

        $filesystem = new Filesystem(new AsyncAwsS3Adapter($client, 'media-bucket', 'uploads'));
        $disk = new Disk('s3', 'S3', $filesystem, []);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->never())->method('generate');

        $manager = new DiskManager([$disk], $urlGenerator);

        $url = $manager->publicUrl('s3', 'clip.mp4');

        $this->assertStringContainsString('X-Amz-Signature=', $url);
        $this->assertNotNull($manager->resolveDirectMediaUrl('s3', 'clip.mp4'));
    }

    public function testProxyMediaForcesSymfonyRoute(): void
    {
        $client = new S3Client([
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'region' => 'eu-west-1',
        ], null, new MockHttpClient());

        $filesystem = new Filesystem(new AsyncAwsS3Adapter($client, 'media-bucket', 'uploads'));
        $disk = new Disk('s3', 'S3', $filesystem, ['proxy_media' => true]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/kbd/filemanager/media/s3/clip.mp4');

        $manager = new DiskManager([$disk], $urlGenerator);

        $this->assertNull($manager->resolveDirectMediaUrl('s3', 'clip.mp4'));
        $this->assertSame('/kbd/filemanager/media/s3/clip.mp4', $manager->publicUrl('s3', 'clip.mp4'));
    }

    public function testResolveUrlTwigFilterMatchesDiskManager(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->with(
                'keyboardman_filemanager_media',
                ['filesystem' => 'default', 'path' => 'photo.jpg'],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            )
            ->willReturn('/kbd/filemanager/media/default/photo.jpg');

        $disk = new Disk('default', 'Default', new Filesystem(new \League\Flysystem\InMemory\InMemoryFilesystemAdapter()), []);
        $manager = new DiskManager([$disk], $urlGenerator);
        $extension = new FilemanagerExtension($manager);

        $this->assertSame(
            $manager->publicUrl('default', 'photo.jpg'),
            $extension->resolveUrl('photo.jpg', 'default'),
        );
    }
}
