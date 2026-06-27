<?php

namespace Keyboardman\FilemanagerBundle\Tests\Functional;

use Keyboardman\FilemanagerBundle\Controller\MediaController;
use Keyboardman\FilemanagerBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class MediaServeTest extends KernelTestCase
{
    private string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->uploadDir = self::getContainer()->getParameter('kernel.cache_dir').'/test_uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    private function captureStreamedContent(\Symfony\Component\HttpFoundation\Response $response): string
    {
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        return false === $content ? '' : $content;
    }

    public function testFullFileResponseWithoutRange(): void
    {
        $content = '0123456789abcdef';
        file_put_contents($this->uploadDir.'/sample.bin', $content);

        $response = $this->serve('sample.bin');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertSame((string) strlen($content), $response->headers->get('Content-Length'));
        $this->assertSame($content, $this->captureStreamedContent($response));
    }

    public function testValidRangeReturnsPartialContent(): void
    {
        $content = '0123456789abcdef';
        file_put_contents($this->uploadDir.'/range.bin', $content);

        $request = Request::create('/kbd/filemanager/media/default/range.bin', 'GET');
        $request->headers->set('Range', 'bytes=4-7');

        $response = self::getContainer()->get(MediaController::class)->serve('default', 'range.bin', $request);

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes 4-7/'.strlen($content), $response->headers->get('Content-Range'));
        $this->assertSame('4', $response->headers->get('Content-Length'));
        $this->assertSame('4567', $this->captureStreamedContent($response));
    }

    public function testInvalidRangeReturns416(): void
    {
        $content = '0123456789';
        file_put_contents($this->uploadDir.'/invalid-range.bin', $content);

        $request = Request::create('/kbd/filemanager/media/default/invalid-range.bin', 'GET');
        $request->headers->set('Range', 'bytes=999-1000');

        $response = self::getContainer()->get(MediaController::class)->serve('default', 'invalid-range.bin', $request);

        $this->assertSame(416, $response->getStatusCode());
        $this->assertSame('bytes */'.strlen($content), $response->headers->get('Content-Range'));
    }

    public function testHeadRequestReturnsMetadataOnly(): void
    {
        $content = str_repeat('x', 100);
        file_put_contents($this->uploadDir.'/head.bin', $content);

        $request = Request::create('/kbd/filemanager/media/default/head.bin', 'HEAD');

        $response = self::getContainer()->get(MediaController::class)->serve('default', 'head.bin', $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('100', $response->headers->get('Content-Length'));
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertSame('', $response->getContent());
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function serve(string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/kbd/filemanager/media/default/'.$filename, 'GET');

        return self::getContainer()->get(MediaController::class)->serve('default', $filename, $request);
    }
}
