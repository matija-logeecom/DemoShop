<?php

namespace DemoShop\Business\Service;

use DemoShop\Presentation\Controller\AdminController;

class UserService implements UserServiceInterface
{
    private AdminController $controller;
    public function __construct(AdminController $controller)
    {
        $this->controller = $controller;
    }

    public function authenticate(string $username, string $password)
    {

    }


}