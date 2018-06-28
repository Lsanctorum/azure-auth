<?php

namespace LouisSicard\AzureAuth\Classes;


use Symfony\Component\Security\Core\User\UserInterface;

interface AzureUser extends UserInterface
{

  function setUsername(string $username);
  function setFisrtName(string $firstName);
  function setLastName(string $lastName);
  function setEmail(string $email);

}