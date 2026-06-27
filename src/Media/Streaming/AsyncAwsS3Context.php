<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use AsyncAws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\PathPrefixer;

final class AsyncAwsS3Context
{
    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly PathPrefixer $prefixer,
    ) {
    }

    public static function fromAdapter(AsyncAwsS3Adapter $adapter): self
    {
        $reflection = new \ReflectionClass($adapter);

        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);

        $bucketProperty = $reflection->getProperty('bucket');
        $bucketProperty->setAccessible(true);

        $prefixerProperty = $reflection->getProperty('prefixer');
        $prefixerProperty->setAccessible(true);

        return new self(
            $clientProperty->getValue($adapter),
            $bucketProperty->getValue($adapter),
            $prefixerProperty->getValue($adapter),
        );
    }

    public function client(): S3Client
    {
        return $this->client;
    }

    public function bucket(): string
    {
        return $this->bucket;
    }

    public function objectKey(string $path): string
    {
        return $this->prefixer->prefixPath($path);
    }
}
