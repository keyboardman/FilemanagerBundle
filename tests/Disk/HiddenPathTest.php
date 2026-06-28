<?php

namespace Keyboardman\FilemanagerBundle\Tests\Disk;

use Keyboardman\FilemanagerBundle\Disk\HiddenPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HiddenPathTest extends TestCase
{
    #[DataProvider('hiddenPathProvider')]
    public function testIsHidden(string $path, bool $expected): void
    {
        $this->assertSame($expected, HiddenPath::isHidden($path));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hiddenPathProvider(): iterable
    {
        yield 'fichier caché à la racine' => ['.DS_Store', true];
        yield 'fichier caché avec trailing slash' => ['.DS_Store/', true];
        yield 'fichier caché dans un sous-dossier' => ['photos/.hidden.jpg', true];
        yield 'dossier caché' => ['.metadata', true];
        yield 'dossier caché avec trailing slash' => ['.metadata/', true];
        yield 'fichier visible à la racine' => ['photo.jpg', false];
        yield 'fichier visible dans un sous-dossier' => ['photos/vacances.jpg', false];
        yield 'dossier visible' => ['videos', false];
        yield 'chemin vide' => ['', false];
    }
}
