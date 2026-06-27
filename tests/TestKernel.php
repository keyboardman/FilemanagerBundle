<?php

namespace Keyboardman\FilemanagerBundle\Tests;

use Keyboardman\FilemanagerBundle\KeyboardmanFilemanagerBundle;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new KeyboardmanFilemanagerBundle();
        yield new FlysystemBundle();
    }

    protected function configureContainer(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'test_secret',
            'router' => ['utf8' => true],
            'test' => true,
            'php_errors' => ['log' => true],
        ]);

        $container->loadFromExtension('keyboardman_filemanager', [
            'disks' => [
                'default' => [
                    'label' => 'Default',
                    'storage' => [
                        'adapter' => 'local',
                        'options' => [
                            'directory' => '%kernel.cache_dir%/test_uploads',
                        ],
                    ],
                ],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../src/Controller/', 'attribute');
    }
}
