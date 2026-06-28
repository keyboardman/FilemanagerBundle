<?php

namespace Keyboardman\FilemanagerBundle\Disk;

use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\AwsObject;
use Keyboardman\FilemanagerBundle\Media\Streaming\AsyncAwsS3Context;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;

/**
 * Contournement des bugs de pagination S3 (QNAP et compatibles) et de directoryExists Flysystem
 * qui ignore les CommonPrefixes sans objet direct.
 */
final class SafeAsyncAwsS3Lister
{
    private const PAGE_SIZE = 1000;

    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly PathPrefixer $prefixer,
    ) {
    }

    /**
     * Crée une instance à partir d'un adaptateur AsyncAws S3, ou null si non applicable.
     */
    public static function tryFromAdapter(?AsyncAwsS3Adapter $adapter): ?self
    {
        if (null === $adapter) {
            return null;
        }

        $context = AsyncAwsS3Context::fromAdapter($adapter);
        $reflection = new \ReflectionClass($adapter);
        $property = $reflection->getProperty('prefixer');
        $property->setAccessible(true);

        return new self(
            $context->client(),
            $context->bucket(),
            $property->getValue($adapter),
        );
    }

    /**
     * Vérifie l'existence d'un répertoire via l'API S3 (gère les CommonPrefixes).
     */
    public function directoryExists(string $path): bool
    {
        $normalizedPath = trim($path, '/');
        if ('' === $normalizedPath) {
            return true;
        }

        $result = $this->client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => $this->listingPrefix($normalizedPath),
            'Delimiter' => '/',
            'MaxKeys' => 1,
        ]);

        if ($result->getKeyCount() > 0) {
            return true;
        }

        foreach ($result->getCommonPrefixes(true) as $_prefix) {
            return true;
        }

        $markerResult = $this->client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => trim($this->prefixer->prefixPath($normalizedPath), '/'),
            'MaxKeys' => 1,
        ]);

        $markerKey = $this->listingPrefix($normalizedPath);
        foreach ($markerResult->getContents(true) as $object) {
            if (($object->getKey() ?? '') === $markerKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return iterable<StorageAttributes>
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $normalizedPath = trim($path, '/');
        $options = [
            'Bucket' => $this->bucket,
            'Prefix' => $this->listingPrefix($normalizedPath),
            'MaxKeys' => self::PAGE_SIZE,
        ];

        if (!$deep) {
            $options['Delimiter'] = '/';
        }

        $seenPaths = [];
        $seenTokens = [];
        $continuationToken = null;

        do {
            $request = $options;
            if (null !== $continuationToken) {
                if (isset($seenTokens[$continuationToken])) {
                    break;
                }
                $seenTokens[$continuationToken] = true;
                $request['ContinuationToken'] = $continuationToken;
            }

            $result = $this->client->listObjectsV2($request);

            foreach ($result->getContents(true) as $object) {
                $key = $object->getKey() ?? '';
                if ('' === $key || isset($seenPaths[$key])) {
                    continue;
                }
                $seenPaths[$key] = true;

                $itemPath = $this->prefixer->stripPrefix($key);
                if ($itemPath === $normalizedPath) {
                    continue;
                }

                yield $this->mapAwsObject($object, $itemPath);
            }

            foreach ($result->getCommonPrefixes(true) as $commonPrefix) {
                $prefix = $commonPrefix->getPrefix() ?? '';
                if ('' === $prefix || isset($seenPaths[$prefix])) {
                    continue;
                }
                $seenPaths[$prefix] = true;

                $itemPath = rtrim($this->prefixer->stripPrefix($prefix), '/');
                if ($itemPath === $normalizedPath) {
                    continue;
                }

                yield new DirectoryAttributes($itemPath);
            }

            if (true !== $result->getIsTruncated()) {
                break;
            }

            $continuationToken = $result->getNextContinuationToken();
        } while (null !== $continuationToken);
    }

    private function listingPrefix(string $path): string
    {
        $prefix = trim($this->prefixer->prefixPath($path), '/');

        return '' === $prefix ? '' : $prefix.'/';
    }

    private function mapAwsObject(AwsObject $object, string $path): StorageAttributes
    {
        if (str_ends_with($path, '/')) {
            return new DirectoryAttributes(rtrim($path, '/'));
        }

        $lastModified = null;
        $dateTime = $object->getLastModified();
        if ($dateTime instanceof \DateTimeInterface) {
            $lastModified = $dateTime->getTimestamp();
        }

        $size = $object->getSize();

        return new FileAttributes(
            $path,
            null !== $size ? (int) $size : null,
            null,
            $lastModified,
        );
    }
}
