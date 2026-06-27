<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use AsyncAws\Core\Exception\Http\ClientException;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\FilesystemOperator;

final class AsyncAwsS3MediaRangeReader implements MediaRangeReaderInterface
{
    public function supports(FilesystemOperator $filesystem): bool
    {
        return null !== FlysystemAdapterExtractor::extractAsyncAwsS3Adapter($filesystem);
    }

    public function readRange(FilesystemOperator $filesystem, string $path, int $start, int $length): callable
    {
        $adapter = FlysystemAdapterExtractor::extractAsyncAwsS3Adapter($filesystem);
        if (null === $adapter) {
            throw MediaStreamException::readFailed($path);
        }

        $context = AsyncAwsS3Context::fromAdapter($adapter);
        $end = $start + $length - 1;

        try {
            $body = $context->client()->getObject([
                'Bucket' => $context->bucket(),
                'Key' => $context->objectKey($path),
                'Range' => sprintf('bytes=%d-%d', $start, $end),
            ])->getBody();
        } catch (ClientException $exception) {
            throw MediaStreamException::readFailed($path, $exception);
        }

        return function () use ($body): void {
            foreach ($body->getChunks() as $chunk) {
                echo $chunk;
            }
        };
    }
}
