<?php

namespace Keyboardman\FilemanagerBundle\Tests\Disk;

use AsyncAws\S3\S3Client;
use Keyboardman\FilemanagerBundle\Disk\SafeAsyncAwsS3Lister;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathPrefixer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SafeAsyncAwsS3ListerTest extends TestCase
{
    public function testListContentsExcludesHiddenObjectsAndCommonPrefixes(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
  <Name>test-bucket</Name>
  <Prefix></Prefix>
  <KeyCount>4</KeyCount>
  <MaxKeys>1000</MaxKeys>
  <IsTruncated>false</IsTruncated>
  <Contents>
    <Key>.DS_Store</Key>
    <LastModified>2024-01-01T00:00:00.000Z</LastModified>
    <ETag>"abc"</ETag>
    <Size>100</Size>
    <StorageClass>STANDARD</StorageClass>
  </Contents>
  <Contents>
    <Key>photo.jpg</Key>
    <LastModified>2024-01-01T00:00:00.000Z</LastModified>
    <ETag>"def"</ETag>
    <Size>2048</Size>
    <StorageClass>STANDARD</StorageClass>
  </Contents>
  <CommonPrefixes>
    <Prefix>.hidden/</Prefix>
  </CommonPrefixes>
  <CommonPrefixes>
    <Prefix>videos/</Prefix>
  </CommonPrefixes>
</ListBucketResult>
XML;

        $client = new S3Client([
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'region' => 'eu-west-1',
        ], null, new MockHttpClient(new MockResponse($xml, ['http_code' => 200])));

        $lister = new SafeAsyncAwsS3Lister($client, 'test-bucket', new PathPrefixer(''));

        $items = iterator_to_array($lister->listContents('', false));

        $this->assertCount(2, $items);
        $this->assertInstanceOf(FileAttributes::class, $items[0]);
        $this->assertSame('photo.jpg', $items[0]->path());
        $this->assertInstanceOf(DirectoryAttributes::class, $items[1]);
        $this->assertSame('videos', $items[1]->path());
    }
}
