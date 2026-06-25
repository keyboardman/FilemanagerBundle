<?php

namespace Keyboardman\FilemanagerBundle\Tests;

use Keyboardman\FilemanagerBundle\Upload\UploadLimitResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadLimitResolverTest extends TestCase
{
    private UploadLimitResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new UploadLimitResolver();
    }

    public function testMaxSafeChunkSizeUsesMinimumOfIniLimits(): void
    {
        $previousUpload = ini_get('upload_max_filesize');
        $previousPost = ini_get('post_max_size');

        ini_set('upload_max_filesize', '2M');
        ini_set('post_max_size', '8M');

        try {
            $max = $this->resolver->maxSafeChunkSize();
            $expected = (int) floor(2 * 1024 * 1024 * 0.85);

            $this->assertSame(max(262144, $expected), $max);
        } finally {
            ini_set('upload_max_filesize', $previousUpload);
            ini_set('post_max_size', $previousPost);
        }
    }

    public function testMaxSafeChunkSizeWithUnlimitedIni(): void
    {
        $previousUpload = ini_get('upload_max_filesize');
        $previousPost = ini_get('post_max_size');

        ini_set('upload_max_filesize', '-1');
        ini_set('post_max_size', '-1');

        try {
            if ('-1' !== ini_get('upload_max_filesize') || '-1' !== ini_get('post_max_size')) {
                $this->markTestSkipped('Impossible de définir des limites PHP illimitées dans cet environnement.');
            }

            $this->assertSame(5 * 1024 * 1024, $this->resolver->maxSafeChunkSize());
        } finally {
            ini_set('upload_max_filesize', $previousUpload);
            ini_set('post_max_size', $previousPost);
        }
    }

    public function testResolveBoundsValuesToMaxSafeChunkAndMinimum(): void
    {
        $previousUpload = ini_get('upload_max_filesize');
        $previousPost = ini_get('post_max_size');

        ini_set('upload_max_filesize', '1M');
        ini_set('post_max_size', '1M');

        try {
            $resolved = $this->resolver->resolve(50 * 1024 * 1024, 50 * 1024 * 1024);
            $maxSafe = $this->resolver->maxSafeChunkSize();

            $this->assertSame($maxSafe, $resolved['chunk_size']);
            $this->assertSame($maxSafe, $resolved['chunk_threshold']);
            $this->assertGreaterThanOrEqual(262144, $resolved['chunk_size']);
            $this->assertGreaterThanOrEqual(262144, $resolved['chunk_threshold']);
        } finally {
            ini_set('upload_max_filesize', $previousUpload);
            ini_set('post_max_size', $previousPost);
        }
    }

    #[DataProvider('resolveKeepsConfiguredValuesWithinLimitsProvider')]
    public function testResolveKeepsConfiguredValuesWithinLimits(int $chunkSize, int $chunkThreshold): void
    {
        $previousUpload = ini_get('upload_max_filesize');
        $previousPost = ini_get('post_max_size');

        ini_set('upload_max_filesize', '20M');
        ini_set('post_max_size', '20M');

        try {
            $resolved = $this->resolver->resolve($chunkSize, $chunkThreshold);

            $this->assertSame(max(262144, min($chunkSize, $this->resolver->maxSafeChunkSize())), $resolved['chunk_size']);
            $this->assertSame(max(262144, min($chunkThreshold, $this->resolver->maxSafeChunkSize())), $resolved['chunk_threshold']);
        } finally {
            ini_set('upload_max_filesize', $previousUpload);
            ini_set('post_max_size', $previousPost);
        }
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function resolveKeepsConfiguredValuesWithinLimitsProvider(): iterable
    {
        yield 'small values' => [524288, 1048576];
        yield 'equal values' => [1048576, 1048576];
    }
}
