<?php

namespace LouisSicard\AzureAuth\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('azure_auth');

    $rootNode
      ->children()
      ->scalarNode('auth_url')->defaultValue('https://login.microsoftonline.com/AUTH_TOKEN/oauth2/authorize')->end()
      ->scalarNode('token_url')->defaultValue('https://login.microsoftonline.com/AUTH_TOKEN/oauth2/token')->end()
      ->scalarNode('client_id')->defaultValue('CLIENT_ID')->end()
      ->scalarNode('client_secret')->defaultValue('CLIENT_SECRET')->end()
      ->end()
      ->end();

    return $treeBuilder;
  }

}