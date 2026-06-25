<?php

namespace Keyboardman\FilemanagerBundle\Tests;

use Keyboardman\FilemanagerBundle\Controller\ApiController;
use Keyboardman\FilemanagerBundle\Disk\Disk;
use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\Tests\Fixtures\TestInfrastructureFactory;
use Keyboardman\FilemanagerBundle\Upload\ChunkUploadManager;
use Keyboardman\FilemanagerBundle\Upload\UploadLimitResolver;
use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
    }

    protected function configureContainer(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'test_secret',
            'router' => ['utf8' => true],
            'test' => true,
            'php_errors' => ['log' => true],
        ]);

        $container
            ->register('keyboardman.test.filesystem', Filesystem::class)
            ->setFactory([TestInfrastructureFactory::class, 'createFilesystem']);

        $container
            ->register('keyboardman.test.disk', Disk::class)
            ->setFactory([TestInfrastructureFactory::class, 'createDisk'])
            ->addArgument(new Reference('keyboardman.test.filesystem'));

        $container
            ->register(DiskManager::class)
            ->setFactory([TestInfrastructureFactory::class, 'createDiskManager'])
            ->addArgument(new Reference('keyboardman.test.disk'))
            ->addArgument(new Reference('router'))
            ->setPublic(true);

        $container->register(UploadLimitResolver::class)->setPublic(true);

        $container
            ->register(ChunkUploadManager::class)
            ->setArguments([
                new Reference(DiskManager::class),
                new Reference(UploadLimitResolver::class),
                '%kernel.cache_dir%/chunk_uploads',
            ])
            ->setPublic(true);

        $container
            ->register(ApiController::class)
            ->setArguments([
                new Reference(DiskManager::class),
                new Reference(ChunkUploadManager::class),
            ])
            ->addTag('controller.service_arguments')
            ->setPublic(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../src/Controller/', 'attribute');
    }
}
