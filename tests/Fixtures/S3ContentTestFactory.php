<?php

namespace Keyboardman\FilemanagerBundle\Tests\Fixtures;

use AsyncAws\S3\S3Client;
use Keyboardman\FilemanagerBundle\Disk\Disk;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class S3ContentTestFactory
{
    public const DISK_NAME = 'webtv';

    public static function isConfigured(): bool
    {
        return '' !== self::env('S3_CONTENT_ENDPOINT', '')
            && '' !== self::env('S3_CONTENT_ACCESS_KEY', '')
            && '' !== self::env('S3_CONTENT_SECRET_KEY', '')
            && '' !== self::env('S3_CONTENT_BUCKET', '');
    }

    public static function createS3Client(): S3Client
    {
        self::assertConfigured();

        $verifySsl = self::envBool('S3_CONTENT_SSL_VERIFY', true);

        return new S3Client([
            'region' => self::env('S3_CONTENT_REGION', 'us-east-1'),
            'endpoint' => self::env('S3_CONTENT_ENDPOINT'),
            'pathStyleEndpoint' => 'true',
            'accessKeyId' => self::env('S3_CONTENT_ACCESS_KEY'),
            'accessKeySecret' => self::env('S3_CONTENT_SECRET_KEY'),
        ], null, HttpClient::create([
            'verify_peer' => $verifySsl,
            'verify_host' => $verifySsl,
        ]));
    }

    public static function createFilesystem(): Filesystem
    {
        return new Filesystem(new AsyncAwsS3Adapter(
            self::createS3Client(),
            self::env('S3_CONTENT_BUCKET'),
            self::env('S3_CONTENT_PREFIX', ''),
        ));
    }

    public static function createDisk(): Disk
    {
        return new Disk(self::DISK_NAME, 'WEBTV', self::createFilesystem(), [
            'proxy_media' => false,
        ]);
    }

    public static function createDiskManager(?UrlGeneratorInterface $urlGenerator = null): DiskManager
    {
        $urlGenerator ??= self::createStubUrlGenerator();

        return new DiskManager([self::createDisk()], $urlGenerator);
    }

    private static function createStubUrlGenerator(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
                return '/kbd/filemanager/media/'.$parameters['filesystem'].'/'.$parameters['path'];
            }

            public function setContext(\Symfony\Component\Routing\RequestContext $context): void
            {
            }

            public function getContext(): \Symfony\Component\Routing\RequestContext
            {
                return new \Symfony\Component\Routing\RequestContext();
            }
        };
    }

    private static function assertConfigured(): void
    {
        if (!self::isConfigured()) {
            throw new \RuntimeException('S3 content storage is not configured. Set S3_CONTENT_* variables or S3_CONTENT_ENV_FILE.');
        }
    }

    private static function env(string $name, string $default = ''): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if (false === $value || null === $value || '' === $value) {
            return $default;
        }

        return trim((string) $value, " \t\"'");
    }

    private static function envBool(string $name, bool $default): bool
    {
        $value = self::env($name, '');
        if ('' === $value) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
