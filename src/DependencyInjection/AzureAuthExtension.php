<?php

namespace LouisSicard\AzureAuth\DependencyInjection;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AzureAuthExtension extends Extension
{
  public function load(array $configs, ContainerBuilder $container)
  {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);
    foreach($config as $k => $v) {
      $container->setParameter('azure_auth.' . $k, $v);
    }
  }

}