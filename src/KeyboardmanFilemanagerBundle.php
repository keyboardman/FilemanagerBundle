<?php
namespace Keyboardman\FilemanagerBundle;

use Keyboardman\FilemanagerBundle\DependencyInjection\KeyboardmanFilemanagerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class KeyboardmanFilemanagerBundle extends AbstractBundle 
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new KeyboardmanFilemanagerExtension();
    }


}