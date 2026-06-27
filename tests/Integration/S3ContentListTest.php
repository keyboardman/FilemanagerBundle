<?php

namespace Keyboardman\FilemanagerBundle\Tests\Integration;

use Keyboardman\FilemanagerBundle\Tests\Fixtures\S3ContentTestFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test d'intégration contre le bucket S3 réel (QNAP / compatible S3).
 *
 *   S3_CONTENT_ENV_FILE=/chemin/vers/.env.local composer test -- --group s3
 *
 * @group s3
 */
class S3ContentListTest extends TestCase
{
    public function testListWebtvBucketRoot(): void
    {
        if (!S3ContentTestFactory::isConfigured()) {
            self::markTestSkipped('Variables S3_CONTENT_* absentes. Définissez-les ou S3_CONTENT_ENV_FILE.');
        }

        $diskManager = S3ContentTestFactory::createDiskManager();
        $items = $diskManager->list(S3ContentTestFactory::DISK_NAME, '', null, 'name_asc');

        $this->assertIsArray($items);
        $this->assertGreaterThan(0, \count($items), 'Le bucket WEBTV devrait contenir des médias à la racine.');

        $names = array_map(static fn (array $item): string => $item['name'], $items);
        $uniqueNames = array_unique($names);
        $this->assertSame(\count($names), \count($uniqueNames), 'La liste ne doit pas contenir de doublons (pagination S3 défectueuse).');

        fwrite(STDERR, sprintf("\n[WEBTV root] %d élément(s) : %s\n", \count($names), implode(', ', $names)));

        foreach ($items as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('type', $item);
            $this->assertContains($item['type'], ['file', 'dir']);
        }
    }

    public function testListObjectsV2FirstPageOnly(): void
    {
        if (!S3ContentTestFactory::isConfigured()) {
            self::markTestSkipped('Variables S3_CONTENT_* absentes.');
        }

        $client = S3ContentTestFactory::createS3Client();
        $result = $client->listObjectsV2([
            'Bucket' => getenv('S3_CONTENT_BUCKET'),
            'Delimiter' => '/',
            'MaxKeys' => 100,
        ]);

        $keys = [];
        foreach ($result->getContents(true) as $object) {
            $keys[] = $object->getKey();
        }

        $this->assertNotEmpty($keys);
        fwrite(STDERR, sprintf("\n[S3 API page 1] %d objet(s)\n", \count($keys)));
    }
}
