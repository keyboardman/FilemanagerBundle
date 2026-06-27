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

        return self::unwrapAsyncAwsS3Adapter($adapter);
    }

    public static function isLocalAdapter(FilesystemOperator $filesystem): bool
    {
        $adapter = self::extractAdapter($filesystem);

        return $adapter instanceof LocalFilesystemAdapter;
    }

    public static function supportsTemporaryUrls(FilesystemOperator $filesystem): bool
    {
        return null !== self::extractAsyncAwsS3Adapter($filesystem);
    }

    private static function unwrapAsyncAwsS3Adapter(?FilesystemAdapter $adapter): ?AsyncAwsS3Adapter
    {
        if ($adapter instanceof AsyncAwsS3Adapter) {
            return $adapter;
        }

        if (null === $adapter) {
            return null;
        }

        $reflection = new \ReflectionClass($adapter);
        foreach (['adapter', 'decorated', 'inner'] as $propertyName) {
            if (!$reflection->hasProperty($propertyName)) {
                continue;
            }

            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $inner = $property->getValue($adapter);

            if ($inner instanceof AsyncAwsS3Adapter) {
                return $inner;
            }

            if ($inner instanceof FilesystemAdapter) {
                $found = self::unwrapAsyncAwsS3Adapter($inner);
                if (null !== $found) {
                    return $found;
                }
            }
        }

        return null;
    }
}
