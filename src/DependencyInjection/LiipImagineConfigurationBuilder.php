<?php

declare(strict_types=1);

namespace Keyboardman\FilemanagerBundle\DependencyInjection;

/**
 * Construit la configuration LiipImagine à partir des disks filemanager.
 */
final class LiipImagineConfigurationBuilder
{
    public const CACHE_RESOLVER = 'filemanager_thumbs';

    public const CACHE_PREFIX = 'media/cache/filemanager';

    public static function loaderName(string $diskName): string
    {
        return 'filemanager_'.$diskName;
    }

    public static function filterSetName(string $diskName): string
    {
        return 'filemanager_thumb_'.$diskName;
    }

    /**
     * @param array<string, array<string, mixed>> $disks
     * @param array{max_size: int, quality: int}  $thumbnail
     *
     * @return array<string, mixed>
     */
    public static function build(array $disks, array $thumbnail): array
    {
        if ([] === $disks) {
            return [];
        }

        $loaders = [];
        $filterSets = ['cache' => null];

        foreach (array_keys($disks) as $diskName) {
            $loaderName = self::loaderName($diskName);

            $loaders[$loaderName] = [
                'flysystem' => [
                    'filesystem_service' => KeyboardmanFilemanagerExtension::storageServiceId($diskName),
                ],
            ];

            $maxSize = $thumbnail['max_size'];
            $filterSets[self::filterSetName($diskName)] = [
                'quality' => $thumbnail['quality'],
                'data_loader' => $loaderName,
                'cache' => self::CACHE_RESOLVER,
                'filters' => [
                    'thumbnail' => [
                        'size' => [$maxSize, $maxSize],
                        'mode' => 'inset',
                    ],
                ],
            ];
        }

        return [
            'driver' => 'gd',
            'loaders' => $loaders,
            'resolvers' => [
                self::CACHE_RESOLVER => [
                    'web_path' => [
                        'web_root' => '%kernel.project_dir%/public',
                        'cache_prefix' => self::CACHE_PREFIX,
                    ],
                ],
            ],
            'filter_sets' => $filterSets,
        ];
    }
}
