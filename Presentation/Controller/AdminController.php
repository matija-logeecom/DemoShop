<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Business\Model\Admin;
use DemoShop\Infrastructure\Response\RedirectionResponse;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Business\Service\AdminServiceInterface;

/*
 * Stores logic for handling Admin requests
 */

class AdminController
{
    private AdminServiceInterface $userService;

    /**
     * Constructs Admin Controller instance
     *
     * @param AdminServiceInterface $userService
     */
    public function __construct(AdminServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function adminPage(): Response
    {
        $path = VIEWS_PATH . '/admin.phtml';

        return new HtmlResponse($path);
    }

    /**
     * Makes login page response
     *
     * @param Request $request
     *
     * @return Response
     */
    public function loginPage(Request $request): Response
    {
        $path = VIEWS_PATH . '/login.phtml';

        return new HtmlResponse($path, variables: [
            'username' => $request->getRouteParam('username'),
            'usernameError' => $request->getRouteParam('usernameError'),
            'passwordError' => $request->getRouteParam('passwordError'),
        ]);
    }

    /**
     * Sends Login info to service
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sendLoginInfo(Request $request): Response
    {
        $errors['username'] = '';
        $errors['password'] = '';

        $username = trim($request->getBody()['username']) ?? '';
        $password = trim($request->getBody()['password']) ?? '';
        $validUsername = $this->userService->isValidUsername($username, $errors);
        $validPassword = $this->userService->isValidPassword($password, $errors);

        if (!$validUsername || !$validPassword) {
            $request->setRouteParams([
                'username' => $username,
                'usernameError' => $errors['username'],
                'passwordError' => $errors['password'],
            ]);

            return $this->loginPage($request);
        }

        $admin = new Admin($username, $password);
        if (!$this->userService->authenticate($admin)) {
            $request->setRouteParams([
                'username' => $username,
                'passwordError' => 'Username and password do not match.',
            ]);

            return $this->loginPage($request);
        }

        SessionManager::getInstance()->set('adminLoggedIn', true);
        return new RedirectionResponse('/admin');
    }
}
