<?php

namespace LouisSicard\AzureAuth\Classes;


use Symfony\Component\Security\Core\User\UserInterface;

interface AzureUser extends UserInterface
{

  function setUsername($username);
  function setFisrtName($firstName);
  function setLastName($lastName);
  function setEmail($email);

}