<?php

namespace Keyboardman\FilemanagerBundle\DependencyInjection;

use Keyboardman\FilemanagerBundle\Disk\Disk;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class KeyboardmanFilemanagerExtension extends Extension
{
    public function getAlias(): string
    {
        return 'keyboardman_filemanager';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    dirname(__DIR__, 2).'/dist' => 'keyboardman_filemanager',
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $storages = $this->buildFlysystemStorages($config['disks']);
        if ([] !== $storages) {
            $container->prependExtensionConfig('flysystem', ['storages' => $storages]);
        }

        $container->setParameter('keyboardman_filemanager.upload.chunk_size', $config['upload']['chunk_size']);
        $container->setParameter('keyboardman_filemanager.upload.chunk_threshold', $config['upload']['chunk_threshold']);

        foreach ($config['disks'] as $name => $diskConfig) {
            $container
                ->register("keyboardman_filemanager.disk.{$name}", Disk::class)
                ->setPublic(false)
                ->setLazy(true)
                ->addArgument($name)
                ->addArgument($diskConfig['label'])
                ->addArgument(new Reference(self::storageServiceId($name)))
                ->addArgument($this->diskOptions($diskConfig))
                ->addTag('keyboardman_filemanager.disk')
            ;
        }

        $rawTokens = $container->getParameterBag()->resolveValue('%env(FILEMANAGER_TOKENS)%');
        $tokens = is_string($rawTokens) ? array_map('trim', explode(',', $rawTokens)) : [];

        if (!empty($tokens)) {
            $container->setParameter('keyboardman_filemanager.iframe.tokens', $tokens);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('services.yaml');
    }

    public static function storageServiceId(string $diskName): string
    {
        return sprintf('keyboardman_filemanager.%s.storage', $diskName);
    }

    /**
     * @param array<string, array<string, mixed>> $disks
     *
     * @return array<string, mixed>
     */
    private function buildFlysystemStorages(array $disks): array
    {
        $storages = [];

        foreach ($disks as $name => $diskConfig) {
            if (!isset($diskConfig['storage']) || !is_array($diskConfig['storage'])) {
                continue;
            }

            $storages[self::storageServiceId($name)] = $diskConfig['storage'];
        }

        return $storages;
    }

    /**
     * @param array<string, mixed> $diskConfig
     *
     * @return array<string, mixed>
     */
    private function diskOptions(array $diskConfig): array
    {
        return [
            'visibility' => $diskConfig['visibility'] ?? 'public',
            'signed_urls' => $diskConfig['signed_urls'] ?? false,
            'default_uri' => $diskConfig['default_uri'] ?? null,
        ];
    }
}
