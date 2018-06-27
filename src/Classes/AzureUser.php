<?php

namespace LouisSicard\AzureAuth\Classes;


use Symfony\Component\Security\Core\User\UserInterface;

class AzureUser implements UserInterface
{

  private $username;
  private $email;
  private $roles;

  public function __construct($username, $email, $roles) {
    $this->username = $username;
    $this->email = $email;
    $this->roles = $roles;
  }

  public function getRoles()
  {
    return $this->roles;
  }

  public function getPassword()
  {
    return "";
  }

  public function getSalt()
  {
    return "";
  }

  public function getUsername()
  {
    return $this->username;
  }

  public function getEmail() {
    return $this->email;
  }

  public function eraseCredentials()
  {

  }

}