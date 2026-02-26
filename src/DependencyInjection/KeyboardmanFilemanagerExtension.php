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

        foreach ($config['disks'] as $name => $diskConfig) {
            $container
                ->register("keyboardman_filemanager.disk.{$name}", Disk::class)
                ->setPublic(false)
                ->setLazy(true)
                ->addArgument($name)
                ->addArgument($diskConfig['label'])
                ->addArgument(new Reference($diskConfig['storage']))
                ->addArgument($diskConfig)
                ->addTag('keyboardman_filemanager.disk')
            ;
        }

        $rawTokens = $container->getParameterBag()->resolveValue('%env(FILEMANAGER_TOKENS)%');
        $tokens = is_string($rawTokens) ? array_map('trim', explode(',', $rawTokens)) : [];

        if (is_string($tokens)) {
            $tokens = array_map('trim', explode(',', $tokens));
        }

        if (!empty($tokens)) {
            $container->setParameter('keyboardman_filemanager.iframe.tokens', $tokens);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('services.yaml');
    }
}
