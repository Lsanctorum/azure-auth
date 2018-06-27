<?php

namespace LouisSicard\AzureAuth\EventListener;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class AzureAuthRequestListener
{

  /** @var  Container */
  private $container;

  /** @var array */
  private $azureConfig;

  public function __construct(Container $container, $azureConfig) {
    $this->container = $container;
    $this->azureConfig = $azureConfig;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    $event->getResponse()->headers->set('Louis-Custom-Header', 'This is me at ' . date('d/m/Y H:i:s'));
  }

  private function isUserLoggedIn(){
    if($this->container->get('session')->get('_security_main') != null){
      $token = unserialize($this->container->get('session')->get('_security_main'));
      if($token != null && $token->getUser() instanceof UserInterface){
        return true;
      }
    }
    return false;
  }

  private function getUrlResponse($url, $params, $method = 'GET', $headers = array())
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if($params != NULL) {
      $data = '';
      foreach($params as $k => $v){
        if($data != '')
          $data .= '&';
        $data .= $k . '=' . urlencode($v);
      }
    }
    if($method == 'GET') {
      curl_setopt($ch, CURLOPT_URL, $url . ($params != null ? '?' . $data : ''));
    }
    else {
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      if($params != null){
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      }
    }
    if(!empty($headers)) {
      foreach($headers as $k => $v) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($k . ': ' . $v));
      }
    }
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
  }

}