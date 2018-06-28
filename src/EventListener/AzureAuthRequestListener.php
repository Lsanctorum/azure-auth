<?php

namespace LouisSicard\AzureAuth\EventListener;

use LouisSicard\AzureAuth\Classes\AzureUser;
use LouisSicard\AzureAuth\Classes\AzureUserProvider;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AzureAuthRequestListener
{

  /** @var  Container */
  private $container;

  /** @var  Router */
  private $router;

  /** @var array */
  private $azureConfig;

  /** @var  AzureUserProvider */
  private $userProvider;

  public function __construct(Container $container, Router $router, $azureConfig, AzureUserProvider $userProvider) {
    $this->container = $container;
    $this->router = $router;
    $this->azureConfig = $azureConfig;
    $this->userProvider = $userProvider;
  }

  public function onKernelRequest(GetResponseEvent $event)
  {
    if ($event->getRequest()->get('_route') == 'azure_auth_redirect') {
      $code = $event->getRequest()->get('code');
      $error = $event->getRequest()->get('error');
      if ($code != null && $error == null) {
        $r = $this->getUrlResponse($this->azureConfig['token_url'], array(
          'grant_type' => 'authorization_code',
          'client_id' => $this->azureConfig['client_id'],
          'client_secret' => $this->azureConfig['client_secret'],
          'code' => $code,
          'redirect_uri' => $this->router->generate('azure_auth_redirect', array('redirect' => $event->getRequest()->get('redirect')), Router::ABSOLUTE_URL),
          'resource' => $this->azureConfig['client_id']
        ), 'POST', array('Content-Type' => 'application/x-www-form-urlencoded'));
        $data = json_decode($r, true);
        $token_parts = explode('.', $data['id_token']);
        $userUniqueMail = null;
        $firstName = NULL;
        $lastName = NULL;
        foreach ($token_parts as $tok) {
          if (!empty($tok)) {
            $token = json_decode(base64_decode($tok), true);
            if ($token != null && isset($token['unique_name'])) {
              $userUniqueMail = $token['unique_name'];
              $firstName = isset($token['given_name']) ? $token['given_name'] : "";
              $lastName = isset($token['family_name']) ? $token['family_name'] : "";
            }
          }
        }
        if ($userUniqueMail != null) {
          $user = $this->getUser($userUniqueMail, $firstName, $lastName);
          $token = new UsernamePasswordToken(
            $user,
            $user->getPassword(),
            'main',
            $user->getRoles()
          );

          $this->container->get('security.token_storage')->setToken($token);

          $this->container->get('session')->set('_security_main', serialize($token));

          $event->setResponse(new RedirectResponse($event->getRequest()->get('state')));
          return;
        }
      }
    }
    else {
      if(!$this->isUserLoggedIn()){
        $route = $event->getRequest()->get('_route');
        if($route == NULL)
          return;
        $excludedRoutes = ['azure_auth_redirect', 'azure_auth_logout'];
        if(!in_array($route, $excludedRoutes)) {
          $redirect = $this->router->generate('azure_auth_redirect', [], Router::ABSOLUTE_URL);
          $url = $this->azureConfig['auth_url'] . '?client_id=' . urlencode($this->azureConfig['client_id']) . '&response_type=code&redirect_uri=' . urlencode($redirect) . '&state=' . urlencode($this->router->generate($route, $event->getRequest()->request->all(), Router::ABSOLUTE_URL));
          $event->setResponse(new RedirectResponse($url));
        }
      }

    }
  }

  /**
   * @param string $email
   * @return UserInterface
   */
  private function getUser($email, $firstName, $lastName) {
    //Let's find which user class is compatible with our user provider
    $classes = get_declared_classes();
    $userClass = NULL;
    foreach($classes as $class) {
      $interfaces = class_implements($class);
      if($interfaces && in_array(AzureUser::class, $interfaces) && $this->userProvider->supportsClass($class)) {
        $userClass = $class;
        break;
      }
    }
    if($userClass == NULL) {
      throw new \RuntimeException('No user class compatible with user provider "' . get_class($this->userProvider) .'" found.');
    }
    $user = $this->userProvider->loadUserByUsername($email);
    if($user == NULL) {
      /** @var AzureUser $user */
      $user = new $class();
      $user->setUsername($email);
      $user->setFisrtName($firstName);
      $user->setLastName($lastName);
      $user->setEmail($email);
      $this->userProvider->persist($user);
      return $user;
    }
    else {
      return $user;
    }
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