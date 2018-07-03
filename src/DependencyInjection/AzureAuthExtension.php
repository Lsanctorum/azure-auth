<?php

namespace LouisSicard\AzureAuth\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AzureAuthExtension extends Extension
{
  public function load(array $configs, ContainerBuilder $container)
  {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);

    $container->setParameter('azure_auth_config', $config);

    $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
    $loader->load('services.yml');


  }

}