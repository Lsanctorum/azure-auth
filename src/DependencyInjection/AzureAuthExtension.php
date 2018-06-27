<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 27/06/2018
 * Time: 12:17
 */

namespace LouisSicard\AzureAuth\DependencyInjection;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AzureAuthExtension extends Extension
{
  public function load(array $configs, ContainerBuilder $container)
  {

    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);
  }

}