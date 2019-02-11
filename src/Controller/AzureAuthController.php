<?php

namespace LouisSicard\AzureAuth\Controller;


use Base64Url\Base64Url;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AzureAuthController extends Controller
{

  public function loginAction(Request $request) {
    $code = $request->get('code');
    $error = $request->get('error');
    $config = $this->container->getParameter('azure_auth_config');

    //I'm coming from Azure OAuth
    if($code != null && $error == null) {
      $r = $this->getUrlResponse($config['token_url'], array(
        'grant_type' => 'authorization_code',
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code' => $code,
        'redirect_uri' => $this->container->get('router')->generate('azure_auth_login', [], Router::ABSOLUTE_URL),
        'resource' => $config['client_id']
      ), 'POST', array('Content-Type' => 'application/x-www-form-urlencoded'));
      $data = json_decode($r, true);
      $token_parts = explode('.', $data['id_token']);
      $userUniqueMail = null;
      $firstName = NULL;
      $lastName = NULL;
      foreach ($token_parts as $tok) {
        if (!empty($tok)) {
          $token = json_decode(Base64Url::decode($tok), true);
          if ($token != null && isset($token['unique_name'])) {
            $userUniqueMail = $token['unique_name'];
            $firstName = isset($token['given_name']) ? $token['given_name'] : "";
            $lastName = isset($token['family_name']) ? $token['family_name'] : "";
          }
        }
      }
      if ($userUniqueMail != null) {
        $user = $this->getAzureUser($userUniqueMail, $firstName, $lastName);
        $token = new UsernamePasswordToken(
          $user,
          $user->getPassword(),
          'main',
          $user->getRoles()
        );

        $this->container->get('security.token_storage')->setToken($token);

        $this->container->get('session')->set('_security_main', serialize($token));

        return $this->redirect($request->get('state'));
      }
      else {
        throw new \RuntimeException("No mail returned by Azure with token " . $data['id_token']);
      }
    }
    elseif($error != null) { //There is an error
      throw new \RuntimeException($error);
    }
    else { //I'm not logged in. I need to be sent to Azure OAuth services
      $redirectUrl = $this->container->get('router')->generate('azure_auth_login', [], Router::ABSOLUTE_URL);

      if($request->get('redirect') != null) {
        $finalUrl = $this->container->get('router')->generate($request->get('redirect'), [], Router::ABSOLUTE_URL);
      }
      else {
        $homepageRoute = $this->container->get('router')->match('/');
        if($homepageRoute != NULL && isset($homepageRoute['_route'])) {
          $finalUrl = $this->container->get('router')->generate($homepageRoute['_route'], [], Router::ABSOLUTE_URL);
        }
      }
      $url = $config['auth_url'] . '?client_id=' . urlencode($config['client_id']) . '&response_type=code&redirect_uri=' . urlencode($redirectUrl) . '&state=' . urlencode(isset($finalUrl) ? $finalUrl : '');
      return $this->redirect($url);
    }
  }

  public function logoutAction(Request $request) {
    $this->container->get('security.token_storage')->setToken(null);
    $request->getSession()->invalidate();
    return $this->redirect('https://login.windows.net/common/oauth2/logout');
  }

  /**
   * @param string $email
   * @return UserInterface
   */
  private function getAzureUser($email, $firstName, $lastName) {
    //Let's find which user class is compatible with our user provider
    $classes = get_declared_classes();
    $config = $this->container->getParameter('azure_auth_config');
    $userProvider = $this->container->get($config['user_provider_id']);
    $userClass = $userProvider->getSupportedClass();
    $user = $userProvider->loadUserByUsername($email);
    if($user == NULL) {
      /** @var AzureUser $user */
      $user = new $userClass();
      $user->setUsername($email);
      $user->setFisrtName($firstName);
      $user->setLastName($lastName);
      $user->setEmail($email);
      $userProvider->persist($user);
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