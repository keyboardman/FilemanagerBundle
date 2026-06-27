<?php

namespace Keyboardman\FilemanagerBundle\Tests\Media\Streaming;

use AsyncAws\S3\S3Client;
use Keyboardman\FilemanagerBundle\Media\Streaming\AsyncAwsS3MediaRangeReader;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AsyncAwsS3MediaRangeReaderTest extends TestCase
{
    public function testReadRangeUsesS3GetObjectWithRangeHeader(): void
    {
        $capturedRange = null;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedRange) {
            foreach ($options['headers'] ?? [] as $header) {
                if (str_starts_with($header, 'Range:')) {
                    $capturedRange = trim(substr($header, strlen('Range:')));
                }
            }

            return new MockResponse('4567');
        });

        $client = new S3Client([
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'region' => 'eu-west-1',
        ], null, $httpClient);

        $filesystem = new Filesystem(new AsyncAwsS3Adapter($client, 'test-bucket', 'uploads'));
        $reader = new AsyncAwsS3MediaRangeReader();

        $this->assertTrue($reader->supports($filesystem));

        ob_start();
        ($reader->readRange($filesystem, 'video.mp4', 4, 4))();
        $output = ob_get_clean();

        $this->assertSame('4567', $output);
        $this->assertSame('bytes=4-7', $capturedRange);
    }

    public function testSupportsReturnsFalseForNonS3Filesystem(): void
    {
        $client = new S3Client([
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'region' => 'eu-west-1',
        ], null, new MockHttpClient());

        $filesystem = new Filesystem(new AsyncAwsS3Adapter($client, 'bucket', ''));
        $reader = new AsyncAwsS3MediaRangeReader();

        $this->assertTrue($reader->supports($filesystem));

        $localFilesystem = new Filesystem(new \League\Flysystem\Local\LocalFilesystemAdapter(sys_get_temp_dir()));
        $this->assertFalse($reader->supports($localFilesystem));
    }
}
