<?php

namespace LouisSicard\AzureAuth\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AzureAuthController extends Controller
{

  public function redirectAction(Request $request) {
    return $this->redirect($request->get('redirect'));
  }

  public function logoutAction(Request $request) {
    return $this->redirect('https://login.windows.net/common/oauth2/logout');
  }

}