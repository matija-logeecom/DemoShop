<?php

namespace DemoShop\Business\Service;

interface UserServiceInterface
{
    public function authenticate(string $username, string $password);
}