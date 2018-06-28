<?php

namespace LouisSicard\AzureAuth\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AzureAuthLoader extends Loader
{
  private $isLoaded = false;

  public function load($resource, $type = null)
  {
    if(true === $this->isLoaded) {
      throw new \RuntimeException('Do not add the "azure_auth" loader twice');
    }

    $routes = new RouteCollection();

    $routes->add(
      'azure_auth_redirect',
      new Route(
        '/azure_auth_redirect',
        array(
          '_controller' => 'LouisSicard\AzureAuth\Controller\AzureAuthController::redirectAction',
        ),
        [])
    );

    $routes->add(
      'azure_auth_logout',
      new Route(
        '/azure_auth_logout',
        array(
          '_controller' => 'LouisSicard\AzureAuth\Controller\AzureAuthController::logoutAction',
        ),
        [])
    );

    $routes->add(
      'logout',
      new Route(
        '/logout',
        array(
          '_controller' => 'LouisSicard\AzureAuth\Controller\AzureAuthController::logoutAction',
        ),
        [])
    );

    $this->isLoaded = true;

    return $routes;
  }

  public function supports($resource, $type = null)
  {
    return 'azure_auth' === $type;
  }

}