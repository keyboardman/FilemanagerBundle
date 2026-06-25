<?php

namespace Keyboardman\FilemanagerBundle\Tests\Functional;

use Keyboardman\FilemanagerBundle\Controller\ApiController;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ApiUploadChunkTest extends KernelTestCase
{
    public function testChunkedUploadSuccess(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(ApiController::class);
        $uploadId = 'dddddddd-eeee-ffff-aaaa-bbbbbbbbbbbb';
        $content = 'part-one-part-two-final';
        $chunks = ['part-one-', 'part-two-', 'final'];

        foreach ($chunks as $index => $chunkContent) {
            $path = $this->createTempFile($chunkContent, 'chunk'.$index);

            $request = Request::create('/api/filemanager/upload-chunk', 'POST', [
                'uploadId' => $uploadId,
                'chunkIndex' => (string) $index,
                'totalChunks' => (string) count($chunks),
                'totalSize' => (string) strlen($content),
                'filename' => 'large-video.mp4',
                'filesystem' => 'default',
                'path' => '/',
            ], [], [
                'chunk' => new UploadedFile($path, 'chunk'.$index, 'application/octet-stream', null, true),
            ]);

            $response = $controller->uploadChunk($request);

            $this->assertSame(200, $response->getStatusCode());
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($data['success']);

            if ($index < count($chunks) - 1) {
                $this->assertArrayNotHasKey('path', $data);
            } else {
                $this->assertSame('large-video.mp4', $data['name']);
                $this->assertSame('large-video.mp4', $data['path']);
                $this->assertSame(strlen($content), $data['size']);
            }
        }

        /** @var DiskManager $diskManager */
        $diskManager = static::getContainer()->get(DiskManager::class);
        $filesystem = $diskManager->disk('default')->filesystem();
        $this->assertTrue($filesystem->fileExists('large-video.mp4'));
        $this->assertSame($content, $filesystem->read('large-video.mp4'));
    }

    public function testChunkedUploadInvalidParams(): void
    {
        self::bootKernel();
        $path = $this->createTempFile('data', 'chunk');

        $request = Request::create('/api/filemanager/upload-chunk', 'POST', [
            'uploadId' => 'invalid-id',
            'chunkIndex' => '0',
            'totalChunks' => '1',
            'totalSize' => '4',
            'filename' => 'file.jpg',
            'filesystem' => 'default',
            'path' => '/',
        ], [], [
            'chunk' => new UploadedFile($path, 'chunk', 'application/octet-stream', null, true),
        ]);

        $response = self::getContainer()->get(ApiController::class)->uploadChunk($request);

        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('upload invalide', strtolower($data['error']));
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function createTempFile(string $content, string $filename): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('chunk_', true).'_'.$filename;
        file_put_contents($path, $content);

        return $path;
    }
}
