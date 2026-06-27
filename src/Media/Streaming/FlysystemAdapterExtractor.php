<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class FlysystemAdapterExtractor
{
    public static function extractAdapter(FilesystemOperator $filesystem): ?FilesystemAdapter
    {
        if (!$filesystem instanceof Filesystem) {
            return null;
        }

        $reflection = new \ReflectionClass($filesystem);
        if (!$reflection->hasProperty('adapter')) {
            return null;
        }

        $property = $reflection->getProperty('adapter');
        $property->setAccessible(true);
        $adapter = $property->getValue($filesystem);

        return $adapter instanceof FilesystemAdapter ? $adapter : null;
    }

    public static function extractAsyncAwsS3Adapter(FilesystemOperator $filesystem): ?AsyncAwsS3Adapter
    {
        $adapter = self::extractAdapter($filesystem);

        return $adapter instanceof AsyncAwsS3Adapter ? $adapter : null;
    }

    public static function isLocalAdapter(FilesystemOperator $filesystem): bool
    {
        $adapter = self::extractAdapter($filesystem);

        return $adapter instanceof LocalFilesystemAdapter;
    }
}
