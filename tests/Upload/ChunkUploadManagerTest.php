<?php

namespace Keyboardman\FilemanagerBundle\Tests\Upload;

use Keyboardman\FilemanagerBundle\Disk\Disk;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\Upload\ChunkUploadManager;
use Keyboardman\FilemanagerBundle\Upload\UploadLimitResolver;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChunkUploadManagerTest extends TestCase
{
    private string $tempBase;

    private ChunkUploadManager $manager;

    protected function setUp(): void
    {
        $this->tempBase = sys_get_temp_dir().'/kbd-fm-chunk-test-'.uniqid('', true);
        mkdir($this->tempBase, 0700, true);

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $disk = new Disk('default', 'Default', $filesystem, []);
        $diskManager = new DiskManager([$disk], $this->createMock(UrlGeneratorInterface::class));
        $limitResolver = new UploadLimitResolver();

        $this->manager = new ChunkUploadManager($diskManager, $limitResolver, $this->tempBase);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempBase)) {
            $this->removeDirectory($this->tempBase);
        }
    }

    public function testReceiveChunksAndAssembleOnLastFragment(): void
    {
        $uploadId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $content = 'chunk-one-chunk-two';
        $chunkSize = strlen('chunk-one');
        $chunks = [substr($content, 0, $chunkSize), substr($content, $chunkSize)];

        $intermediate = $this->manager->receiveChunk(
            $uploadId,
            0,
            2,
            strlen($content),
            'video.mp4',
            'default',
            '/',
            $this->createUploadedFile($chunks[0], 'chunk0'),
        );

        $this->assertTrue($intermediate['success']);
        $this->assertTrue($intermediate['received']);
        $this->assertArrayNotHasKey('path', $intermediate);

        $final = $this->manager->receiveChunk(
            $uploadId,
            1,
            2,
            strlen($content),
            'video.mp4',
            'default',
            '/',
            $this->createUploadedFile($chunks[1], 'chunk1'),
        );

        $this->assertTrue($final['success']);
        $this->assertSame('video.mp4', $final['name']);
        $this->assertSame(strlen($content), $final['size']);
        $this->assertArrayHasKey('mimeType', $final);
        $this->assertSame('video.mp4', $final['path']);

        $this->assertDirectoryDoesNotExist($this->tempBase.'/'.$uploadId);
    }

    public function testRejectsInvalidUploadId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifiant d\'upload invalide.');

        $this->manager->receiveChunk(
            'not-a-uuid',
            0,
            1,
            10,
            'file.jpg',
            'default',
            '/',
            $this->createUploadedFile('data', 'chunk'),
        );
    }

    public function testRejectsOutOfSequenceChunk(): void
    {
        $uploadId = 'bbbbbbbb-cccc-dddd-eeee-ffffffffffff';

        $this->manager->receiveChunk(
            $uploadId,
            0,
            3,
            30,
            'file.jpg',
            'default',
            '/',
            $this->createUploadedFile('abcdefghij', 'chunk0'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('hors séquence');

        $this->manager->receiveChunk(
            $uploadId,
            2,
            3,
            30,
            'file.jpg',
            'default',
            '/',
            $this->createUploadedFile('klmnopqrst', 'chunk2'),
        );
    }

    public function testRejectsInconsistentMetadata(): void
    {
        $uploadId = 'cccccccc-dddd-eeee-ffff-000000000000';

        $this->manager->receiveChunk(
            $uploadId,
            0,
            2,
            20,
            'file.jpg',
            'default',
            '/',
            $this->createUploadedFile('abcdefghij', 'chunk0'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('métadonnées');

        $this->manager->receiveChunk(
            $uploadId,
            1,
            2,
            20,
            'other.jpg',
            'default',
            '/',
            $this->createUploadedFile('klmnopqrst', 'chunk1'),
        );
    }

    private function createUploadedFile(string $content, string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'chunk_upload_');
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, null, \UPLOAD_ERR_OK, true);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
