<?php

namespace Keyboardman\FilemanagerBundle\Tests\Functional;

use Keyboardman\FilemanagerBundle\Controller\ApiController;
use Keyboardman\FilemanagerBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ApiUploadTest extends KernelTestCase
{
    public function testMonolithicUploadSuccess(): void
    {
        self::bootKernel();
        $path = $this->createTempFile("\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01", 'photo.jpg');

        $request = Request::create('/api/filemanager/upload', 'POST', [
            'filesystem' => 'default',
            'path' => '/',
        ], [], [
            'file' => new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true),
        ]);

        $response = self::getContainer()->get(ApiController::class)->upload($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('photo.jpg', $data['name']);
        $this->assertSame('photo.jpg', $data['path']);
        $this->assertSame('image/jpeg', $data['mimeType']);
        $this->assertSame(12, $data['size']);
        $this->assertArrayHasKey('url', $data);
    }

    public function testMonolithicUploadWithoutFile(): void
    {
        self::bootKernel();

        $request = Request::create('/api/filemanager/upload', 'POST', [
            'filesystem' => 'default',
            'path' => '/',
        ]);

        $response = self::getContainer()->get(ApiController::class)->upload($request);

        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('No file uploaded', $data['error']);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function createTempFile(string $content, string $filename): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('upload_', true).'_'.$filename;
        file_put_contents($path, $content);

        return $path;
    }
}
