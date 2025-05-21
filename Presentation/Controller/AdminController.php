<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Response\RedirectionResponse;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Business\Service\UserServiceInterface;
use DemoShop\Business\Model\Admin;
class AdminController
{
    private UserServiceInterface $userService;
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
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

    public function sendLoginInfo(Request $request): Response
    {
        $errors['username'] = '';
        $errors['password'] = '';

        $username = trim($request->getBody()['username']) ?? '';
        $password = trim($request->getBody()['password']) ?? '';
        $validUsername = $this->isValidUsername($username, $errors);
        $validPassword = $this->isValidPassword($password, $errors);

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

        return new RedirectionResponse('/');
    }
    private function isValidUsername(string $username, array &$errors): bool
    {
        if (empty($username)) {
            $errors['username'] = 'Username cannot be empty';

            return false;
        }

        return true;
    }

    private function isValidPassword(string $password, array &$errors): bool
    {
        if (empty($password)) {
            $errors['password'] = 'Password cannot be empty';

            return false;
        }

        $lowerFlag = 0;
        $upperFlag = 0;
        $specialFlag = 0;
        $numberFlag = 0;

        foreach (str_split($password) as $char) {
            if (ctype_upper($char)) {
                $upperFlag = 1;
            }

            if (ctype_lower($char)) {
                $lowerFlag = 1;
            }

            if (ctype_digit($char)) {
                $numberFlag = 1;
            }

            if ($char === '!' || $char === '_' || $char === '#' || $char === '$' || $char === '-') {
                $specialFlag = 1;
            }
        }

        if (!($lowerFlag && $upperFlag && $numberFlag && $specialFlag)) {
            $errors['password'] = 'Password must contain at least one upper, one lower,
             one number and one special character';

            return false;
        }

        return true;
    }
}