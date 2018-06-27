<?php

namespace LouisSicard\AzureAuth\Service;

use Symfony\Component\DependencyInjection\Container;

class TestService
{

  /**
   * @var Container
   */
  private $container;

  /**
   * @var string
   */
  private $authUrl;

  public function __construct(Container $container, $authUrl) {
    $this->container = $container;
    $this->authUrl = $authUrl;
  }

  public function testMe() {
    print 'This is the auth url ==> ' . $this->authUrl . PHP_EOL;
  }

}